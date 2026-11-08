<?php
defined('ABSPATH') || exit;
function obra_legal_posts(){return array_map(function($p){return ['id'=>$p->ID,'title'=>$p->post_title,'url'=>get_permalink($p)];},get_posts(['post_type'=>['page','post'],'post_status'=>'publish','numberposts'=>-1]));}
function obra_sanitize_legal($links){
 $out=[];foreach(['privacy','legal','cookies'] as $key){$v=is_array($links[$key]??null)?$links[$key]:[];$id=absint($v['post']??0);if($id && get_post_status($id)!=='publish')$id=0;
 $out[$key]=['mode'=>in_array($v['mode']??'', ['auto','post','url','text','none'],true)?$v['mode']:'auto','post'=>$id,'url'=>esc_url_raw($v['url']??''),'text'=>wp_kses_post($v['text']??'')];}return $out;
}
/** Published, public documents only; exact titles avoid matching articles about privacy. */
function obra_privacy_candidates(){
 $titles=['privacy policy','privacy notice','politica de privacidad','politicas de privacidad','politique de confidentialite','datenschutzerklarung','datenschutz','informativa sulla privacy','politica sulla privacy','politica de privacidade'];
 $query=['post_type'=>['page','post'],'post_status'=>'publish','has_password'=>false,'numberposts'=>-1,'suppress_filters'=>false,'orderby'=>'ID','order'=>'ASC'];
 if(function_exists('pll_current_language'))$query['lang']=pll_current_language('slug');
 $out=[];
 foreach(get_posts($query) as $post){
  $title=trim(preg_replace('/\s+/u',' ',strtolower(remove_accents(wp_strip_all_tags($post->post_title)))));
  if(in_array($title,$titles,true))$out[]=['post'=>(int)$post->ID,'url'=>get_permalink($post),'title'=>wp_strip_all_tags($post->post_title),'source'=>'content'];
 }
 return $out;
}
function obra_detect_legal(){
 $out=[];$id=(int)get_option('wp_page_for_privacy_policy');
 if($id&&get_post_status($id)==='publish'&&!get_post_field('post_password',$id))$out['privacy']=['post'=>$id,'url'=>get_permalink($id),'title'=>wp_strip_all_tags(get_post_field('post_title',$id)),'source'=>'wordpress'];
 $items=[];foreach(wp_get_nav_menus() as $menu)$items=array_merge($items,wp_get_nav_menu_items($menu->term_id)?:[]);
 foreach(get_posts(['post_type'=>'wp_navigation','post_status'=>'publish','numberposts'=>-1,'suppress_filters'=>false]) as $nav){$walk=function($blocks)use(&$walk,&$items){foreach($blocks as $b){if($b['blockName']==='core/navigation-link')$items[]=(object)['title'=>$b['attrs']['label']??'','url'=>$b['attrs']['url']??'','object_id'=>$b['attrs']['id']??0];$walk($b['innerBlocks']??[]);}};$walk(parse_blocks($nav->post_content));}
 foreach($items as $item){$title=remove_accents(strtolower(wp_strip_all_tags($item->title)));foreach(['privacy'=>'privacidad|privacy|confidentialite|datenschutz|privacidade','cookies'=>'cookie','legal'=>'aviso legal|legal notice|mentions legales|impressum'] as $key=>$pattern){if(!isset($out[$key])&&preg_match('/'.$pattern.'/i',$title)){$id=url_to_postid($item->url);if($id&&(get_post_status($id)!=='publish'||get_post_field('post_password',$id)))continue;$url=esc_url_raw($item->url);if(!$url)continue;$out[$key]=['post'=>$id,'url'=>$url,'title'=>wp_strip_all_tags($item->title),'source'=>'menu'];}}}
 if(empty($out['privacy'])){ $candidates=obra_privacy_candidates();if(count($candidates)===1)$out['privacy']=$candidates[0]; }
 return $out;
}
function obra_resolve_legal_settings($settings){
 $link=obra_sanitize_legal($settings['legal_links']??[])['privacy'];
 if($link['mode']==='auto'||($link['mode']==='text'&&trim(wp_strip_all_tags($link['text']))==='')){
  $found=obra_detect_legal()['privacy']??null;
  if($found&&$found['source']==='content'&&empty($settings['privacy_url']))$settings['legal_links']['privacy']=['mode'=>'post','post'=>$found['post'],'url'=>'','text'=>''];
 }
 return $settings;
}
function obra_legal_status(){
 check_ajax_referer('obra_save','nonce');if(!current_user_can('manage_options'))wp_send_json_error(null,403);
 wp_send_json_success(['detected'=>obra_detect_legal(),'posts'=>obra_legal_posts(),'candidates'=>count(obra_privacy_candidates())]);
}
add_action('wp_ajax_obra_legal_status','obra_legal_status');
function obra_effective_legal($s){$detected=obra_detect_legal();$out=[];foreach(obra_sanitize_legal($s['legal_links']??[]) as $key=>$v){if($v['mode']==='auto'||($v['mode']==='text'&&trim(wp_strip_all_tags($v['text']))===''))$v=$detected[$key]??($key==='privacy'&&!empty($s['privacy_url'])?['url'=>$s['privacy_url']]:[]);elseif($v['mode']==='post')$v=$v['post']?['post'=>$v['post'],'url'=>get_permalink($v['post'])]:[];elseif($v['mode']==='none')$v=[];elseif($v['mode']==='text')$v=['text'=>$v['text']];else $v=['url'=>$v['url']];if(!empty($v['url'])){$id=url_to_postid($v['url']);if($id&&get_post_status($id)==='publish')$v['post']=$id;}if(!empty($v['url'])||!empty($v['text']))$out[$key]=$v;}return $out;}
function obra_legal_footer($s){
 $links=obra_effective_legal($s);if(!$links)return;$lang=obra_language($s);$labels=['es'=>['privacy'=>'Política de privacidad','legal'=>'Aviso legal','cookies'=>'Cookies'],'en'=>['privacy'=>'Privacy policy','legal'=>'Legal notice','cookies'=>'Cookies'],'fr'=>['privacy'=>'Politique de confidentialité','legal'=>'Mentions légales','cookies'=>'Cookies'],'de'=>['privacy'=>'Datenschutz','legal'=>'Impressum','cookies'=>'Cookies'],'it'=>['privacy'=>'Informativa sulla privacy','legal'=>'Note legali','cookies'=>'Cookie'],'pt'=>['privacy'=>'Política de privacidade','legal'=>'Aviso legal','cookies'=>'Cookies']][$lang]??['privacy'=>'Privacy policy','legal'=>'Legal notice','cookies'=>'Cookies'];
 echo '<footer class="obra-legal-footer">';foreach($links as $key=>$v){$label=esc_html($labels[$key]);if(!empty($v['text'])){echo '<button type="button" data-legal-open="legal-'.esc_attr($key).'">'.esc_html($labels[$key]).'</button><dialog id="legal-'.esc_attr($key).'" class="obra-legal-dialog"><form method="dialog"><button aria-label="'.esc_attr(obra_t('Cerrar','Close')).'">×</button></form><h2>'.esc_html($labels[$key]).'</h2>'.wp_kses_post($v['text']).'</dialog>';}else echo '<a target="_blank" rel="noopener" href="'.esc_url($v['url']).'">'.esc_html($labels[$key]).'</a>';}echo '</footer>';
}
