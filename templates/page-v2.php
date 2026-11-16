<?php
defined('ABSPATH')||exit;
$obra_settings=$settings;
if($obra_settings['logo_source']==='site') $obra_settings['logo']=$obra_settings['site_logo_snapshot']??'';
if($obra_settings['logo_source']==='none') $obra_settings['logo']='';
$obra_lang=obra_language($obra_settings);$obra_settings=obra_localize_content($obra_settings);
$obra_words=['es'=>['Días','Horas','Minutos','Segundos','Tu correo','Acepto recibir el aviso de lanzamiento.','Gracias. Te avisaremos.','No se pudo guardar. Inténtalo de nuevo.'],'en'=>['Days','Hours','Minutes','Seconds','Your email','I agree to receive the launch notification.','Thank you!','Please try again.'],'fr'=>['Jours','Heures','Minutes','Secondes','Votre e-mail',"Je souhaite recevoir la notification.",'Merci !','Veuillez réessayer.'],'de'=>['Tage','Stunden','Minuten','Sekunden','Ihre E-Mail','Ich möchte zum Start benachrichtigt werden.','Vielen Dank!','Bitte erneut versuchen.'],'it'=>['Giorni','Ore','Minuti','Secondi','La tua email','Desidero ricevere la notifica.','Grazie!','Riprova.'],'pt'=>['Dias','Horas','Minutos','Segundos','O seu email','Quero receber a notificação.','Obrigado!','Tente novamente.']];$obra_w=array_map('obra_translate_public',$obra_words[$obra_lang]??$obra_words['en']);
$obra_rgb=sscanf($obra_settings['color_bg'],'#%02x%02x%02x');$obra_dark=sprintf('#%02x%02x%02x',...array_map(function($obra_c){return (int)($obra_c*.65);},$obra_rgb));
$obra_lum=function($obra_hex){$obra_c=array_map(function($obra_v){$obra_v/=255;return $obra_v<=.04045?$obra_v/12.92:pow(($obra_v+.055)/1.055,2.4);},sscanf($obra_hex,'#%02x%02x%02x'));return .2126*$obra_c[0]+.7152*$obra_c[1]+.0722*$obra_c[2];};
$obra_text=$obra_settings['color_text'];if($obra_settings['smart_text'])$obra_text=(($obra_lum($obra_settings['gradient']?$obra_dark:$obra_settings['color_bg'])+.05)/.05>1.05/($obra_lum($obra_settings['color_bg'])+.05))?'#111111':'#ffffff';
$obra_media=in_array($obra_settings['background_type'],['image','video'],true);if($obra_media&&$obra_settings['smart_text'])$obra_text='#ffffff';
$obra_fonts=['system'=>'system-ui, sans-serif','geometric'=>'"Avenir Next", system-ui, sans-serif','mono'=>'ui-monospace, monospace','serif'=>'Georgia, serif'];
$obra_layout=in_array($obra_settings['template'],['center','editorial','split','bottom'],true)?$obra_settings['template']:'center';
$obra_date=DateTimeImmutable::createFromFormat('Y-m-d\TH:i',$obra_settings['launch_date'],wp_timezone());$obra_editor=!empty($obra_editor);

$obra_opening_time=obra_launch_time($obra_settings['launch_date']);
$obra_public_config=array(
 'editor'=>$obra_editor,
 'openingAt'=>(!$obra_editor && empty($obra_preview) && $obra_settings['auto_open'] && $obra_opening_time!==false)?$obra_opening_time*1000:0,
 'language'=>$obra_lang,'nonce'=>wp_create_nonce('obra_subscribe'),'ajax'=>admin_url('admin-ajax.php'),
 'success'=>$obra_w[6],'error'=>$obra_w[7],
 'logo'=>array('trim'=>(bool)$obra_settings['logo_trim'],'fit'=>$obra_settings['background_fit'],'position'=>$obra_settings['background_position'],'force'=>(bool)$obra_settings['logo_force'],'enabled'=>(bool)$obra_settings['logo_smart'],'color'=>$obra_settings['color_bg'],'dark'=>$obra_dark,'gradient'=>(bool)$obra_settings['gradient'],'overlay'=>(int)$obra_settings['overlay'])
);
$obra_enqueue_scripts=static function() use($obra_public_config){
wp_enqueue_script('obra-public', OBRA_URL.'assets/js/page.js', array(), OBRA_VERSION, true);
wp_add_inline_script('obra-public','window.OBRA_PUBLIC='.wp_json_encode($obra_public_config,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';','before');
wp_enqueue_script('obra-logo-contrast', OBRA_URL.'assets/js/logo-contrast.js', array('obra-public'), OBRA_VERSION, true);
};
if($obra_editor){$obra_enqueue_scripts();}else{add_action('wp_enqueue_scripts',$obra_enqueue_scripts);}
// Public requests use the standard enqueue hook; the isolated AJAX preview prints
// the same registered stylesheet without loading the surrounding admin styles.
$obra_enqueue_styles = static function() use ($obra_settings, $obra_text, $obra_dark, $obra_media) {
    wp_enqueue_style('freesoon-page', OBRA_URL.'assets/css/page.css', array(), OBRA_VERSION);
    $background=$obra_settings['gradient']?'linear-gradient(125deg,'.$obra_settings['color_bg'].','.$obra_dark.','.$obra_settings['color_bg'].')':$obra_settings['color_bg'];
    $fit=$obra_settings['background_fit'];
    $position=$obra_settings['background_position'];
    $family=obra_fonts()[$obra_settings['font']]['family']??'system-ui, sans-serif';
    $css=':root{--obra-bg:'.$obra_settings['color_bg'].';--obra-text:'.$obra_text.';--obra-background:'.$background.';--obra-overlay:'.($obra_media?(int)$obra_settings['overlay']/100:0).';--obra-position:'.$position.';--obra-fit:'.($fit==='contain'?'contain':($fit==='original'?'none':'cover')).';--obra-font:'.$family.';}';
    if($fit==='height'){
        $left=explode(' ',$position)[0];
        $css.='.background img,.background video{object-fit:contain;width:auto;max-width:none;position:absolute;left:'.$left.';transform:translateX(-'.$left.')}';
    }
    if($fit==='tile'&&$obra_settings['background_type']==='image'){
        $url=wp_json_encode(esc_url_raw($obra_settings['background_image']),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
        $css.='.background{background-image:url('.$url.');background-size:auto;background-repeat:repeat;background-position:'.$position.';animation:none}.background img{opacity:0}';
    }
    wp_add_inline_style('freesoon-page',$css);
};
if($obra_editor){$obra_enqueue_styles();}
else{
    add_action('wp_enqueue_scripts',$obra_enqueue_styles);
    add_action('wp_head',static function() use($obra_settings){obra_print_fonts($obra_settings['font']);},50);
}
?>
<!doctype html><html lang="<?php echo esc_attr($obra_lang); ?>"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="<?php echo esc_attr($obra_settings['seo_description']); ?>"><title><?php echo esc_html($obra_settings['seo_title']); ?></title><?php if($obra_editor){wp_print_styles(array('freesoon-page'));obra_print_fonts($obra_settings['font']);}else{show_admin_bar(false);remove_action('wp_head','_wp_render_title_tag',1);wp_head();} ?></head><body><?php if(!$obra_editor)wp_body_open();?><main class="obra-page <?php echo esc_html($obra_layout); ?>"><div class="background <?php echo esc_html($obra_settings['gradient']&&$obra_settings['motion']?'moving':''); ?>">
<?php if($obra_settings['background_type']==='image'&&$obra_settings['background_image']):?><img src="<?php echo esc_url($obra_settings['background_image']); ?>" alt=""><?php endif;?>
<?php if($obra_settings['background_type']==='video'&&$obra_settings['background_video']):?><video src="<?php echo esc_url($obra_settings['background_video']); ?>" autoplay muted loop playsinline></video><?php endif;?><div class="shade"></div></div>
<div class="obra-shell"><header><?php if($obra_settings['logo']):?><img class="obra-logo" src="<?php echo esc_url($obra_settings['logo']); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>"><?php endif;?><p class="obra-eyebrow"><?php echo esc_html($obra_settings['eyebrow']); ?></p><h1 class="obra-title"><?php echo esc_html($obra_settings['title']); ?></h1></header><div class="obra-bottom"><section><div class="obra-copy"><?php echo esc_html($obra_settings['text']); ?></div>
<?php if($obra_settings['show_form']&&($obra_settings['form_email']||$obra_settings['form_phone'])):?><form class="obra-form"><div class="email-row"><?php foreach(['name'=>'text','email'=>'email','phone'=>'tel'] as $obra_kind=>$obra_type):if(!$obra_settings['form_'.$obra_kind])continue;
$obra_paths=['name'=>'<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>','email'=>'<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-9 6a2 2 0 0 1-2 0L2 7"/>','phone'=>'<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.96.35 1.9.69 2.79a2 2 0 0 1-.45 2.11L8.09 9.89a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.89.34 1.83.57 2.79.69A2 2 0 0 1 22 16.92z"/>'];
?><div class="obra-input"><svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><?php echo wp_kses($obra_paths[$obra_kind], array('path'=>array('d'=>true),'rect'=>array('x'=>true,'y'=>true,'width'=>true,'height'=>true,'rx'=>true),'circle'=>array('cx'=>true,'cy'=>true,'r'=>true))); ?></svg><input type="<?php echo esc_html($obra_type); ?>"  name="<?php echo esc_html($obra_kind); ?>" required autocomplete="<?php echo esc_html($obra_kind==='phone'?'tel':$obra_kind); ?>" maxlength="<?php echo esc_html($obra_kind==='name'?120:($obra_kind==='phone'?40:254)); ?>" aria-label="<?php echo esc_attr($obra_settings[$obra_kind.'_placeholder']); ?>" placeholder="<?php echo esc_attr($obra_settings[$obra_kind.'_placeholder']); ?>"></div><?php endforeach;?><button><?php echo esc_html($obra_settings['button_text']); ?> →</button></div><label class="consent"><input type="checkbox" name="consent" required value="1"><span><?php echo esc_html($obra_w[5]); ?> <?php if($obra_settings['privacy_url']):?><a href="<?php echo esc_url($obra_settings['privacy_url']); ?>">↗</a><?php endif;?></span></label><input class="trap" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"><p class="status" role="status"></p></form><?php endif;?></section>
<?php if($obra_settings['show_countdown']):?><div class="obra-countdown" data-time="<?php echo esc_html($obra_date?$obra_date->getTimestamp()*1000:0); ?>"><?php foreach(array_slice($obra_w,0,4) as $obra_label):?><div><strong>00</strong><span><?php echo esc_html($obra_label); ?></span></div><?php endforeach;?></div><?php endif;?></div><nav class="social"><?php foreach(['social_instagram'=>'Instagram','social_linkedin'=>'LinkedIn','social_x'=>'X'] as $obra_key=>$obra_label)if($obra_settings[$obra_key])echo '<a href="'.esc_url($obra_settings[$obra_key]).'">'.esc_html($obra_label).'</a>';?></nav><?php obra_legal_footer($obra_settings);?></div></main>
<?php
if($obra_editor)wp_print_scripts(array('obra-public','obra-logo-contrast'));
else wp_footer();
?></body></html>
