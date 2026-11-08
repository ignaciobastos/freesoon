<?php
defined('ABSPATH') || exit;
function obra_v2_hooks() {
    add_action('init', function() {
        register_post_type('obra_subscriber', array('label'=>obra_t('FreeSoon · Suscriptores','FreeSoon · Subscribers'),'public'=>false,'show_ui'=>false,'supports'=>array('title')));
    });
    add_action('wp_ajax_obra_subscribe','obra_subscribe');
    add_action('wp_ajax_nopriv_obra_subscribe','obra_subscribe');
    add_action('admin_menu', function() {
        add_submenu_page('obra',obra_t('Suscriptores','Subscribers'),obra_t('Suscriptores','Subscribers'),'manage_options','obra-subscribers','obra_subscribers_page');
    },20);
    add_action('admin_post_obra_export',function() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Permission denied.','freesoon'),'',array('response'=>403));
        check_admin_referer('obra_export');
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=obra-subscribers.csv');
        $f=fopen('php://output','w'); fputcsv($f,array(obra_t('Nombre','Name'),obra_t('Correo','Email'),obra_t('Teléfono','Phone'),obra_t('Fecha UTC','UTC date'),obra_t('Idioma','Language')),',','"','');
        $page=1;
        do {
            $rows=get_posts(array('post_type'=>'obra_subscriber','posts_per_page'=>200,'paged'=>$page++));
            foreach($rows as $row) {
                $contact=obra_subscriber_contact($row);
                $cells=array($contact['name'],$contact['email'],$contact['phone'],$row->post_date_gmt,get_post_meta($row->ID,'language',true));
                fputcsv($f,array_map(function($value){return preg_replace('/^([\s]*[=+@-])/',"'$1",$value);},$cells),',','"','');
            }
        } while(count($rows)===200);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the CSV response stream, not a filesystem file.
        fclose($f); exit;
    });
}
function obra_validate_contact($settings,$input) {
    if(!$settings['form_email']&&!$settings['form_phone'])return new WP_Error('no_contact');
    $contact=['name'=>'','email'=>'','phone'=>''];
    foreach($contact as $field=>$unused){
        if(!$settings['form_'.$field])continue;
        if(!isset($input[$field])||!is_string($input[$field]))return new WP_Error('missing_field');
        $value=trim($input[$field]);
        if($field==='email'){
            if(strlen($value)>254||!is_email($value))return new WP_Error('invalid_email');
            $contact[$field]=sanitize_email($value);
        }elseif($field==='phone'){
            if(strlen($value)>40||!preg_match('/^\+?[0-9 ().-]+$/',$value))return new WP_Error('invalid_phone');
            $digits=preg_replace('/\D/','',$value);
            if(strlen($digits)<7||strlen($digits)>15)return new WP_Error('invalid_phone');
            $contact[$field]=(strpos($value,'+')===0?'+':'').$digits;
        }else{
            $value=sanitize_text_field($value);
            if($value===''||strlen($value)>480)return new WP_Error('invalid_name');
            $contact[$field]=$value;
        }
    }
    return $contact;
}
function obra_subscriber_contact($post){
    return [
        'name'=>get_post_meta($post->ID,'contact_name',true),
        'email'=>metadata_exists('post',$post->ID,'contact_email')?get_post_meta($post->ID,'contact_email',true):(is_email($post->post_title)?$post->post_title:''),
        'phone'=>get_post_meta($post->ID,'contact_phone',true),
    ];
}
function obra_subscribe() {
    check_ajax_referer('obra_subscribe','nonce');
    $s=Obra_Plugin::settings();
    if (!$s['enabled'] || !$s['show_form']) wp_send_json_error(obra_t('No disponible','Unavailable'),403);
    if(empty($_POST['consent']) || !empty($_POST['website']))wp_send_json_error(obra_t('Envío no válido','Invalid submission'),400);
    $contact=obra_validate_contact($s,wp_unslash($_POST));
    if(is_wp_error($contact))wp_send_json_error(obra_t('Envío no válido','Invalid submission'),400);
    $identity=$contact['email']?:$contact['phone'];
    $key='obra_rate_'.hash_hmac('sha256',sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']??'')),wp_salt());
    $count=(int)get_transient($key);
    if($count>=10) wp_send_json_error(obra_t('Demasiadas solicitudes','Too many requests'),429);
    set_transient($key,$count+1,10*MINUTE_IN_SECONDS);
    $existing=get_posts(array('post_type'=>'obra_subscriber','title'=>$identity,'posts_per_page'=>1));
    if(!$existing) {
        $id=wp_insert_post(array('post_type'=>'obra_subscriber','post_status'=>'publish','post_title'=>$identity),true);
        if(is_wp_error($id)) wp_send_json_error(obra_t('No se pudo guardar','Storage failed'),500);
        foreach($contact as $field=>$value)update_post_meta($id,'contact_'.$field,$value);
        update_post_meta($id,'language',sanitize_key(wp_unslash($_POST['language']??'')));
        update_post_meta($id,'consent','launch-notification');
        if($s['notify']) {
            $sent=wp_mail(sanitize_email($s['notification_email']), obra_t('FreeSoon: nuevo registro','FreeSoon: new subscriber'), obra_t('Nombre: ','Name: ').$contact['name'].PHP_EOL.'Email: '.$contact['email'].PHP_EOL.obra_t('Teléfono: ','Phone: ').$contact['phone']);
            update_post_meta($id,'notification',$sent?'accepted':'failed');
        }
    }
    wp_send_json_success();
}
function obra_subscribers_page() {
    if(!current_user_can('manage_options')) return;
    if(isset($_POST['delete_id'])) {
        check_admin_referer('obra_delete');
        $id=absint($_POST['delete_id']);
        if(get_post_type($id)==='obra_subscriber') wp_delete_post($id,true);
    }
    $page=max(1,absint($_GET['paged']??1));
    $q=new WP_Query(array('post_type'=>'obra_subscriber','posts_per_page'=>30,'paged'=>$page,'s'=>sanitize_text_field(wp_unslash($_GET['s']??''))));
    echo '<div class="wrap"><h1>'.esc_html(obra_t('FreeSoon · Suscriptores','FreeSoon · Subscribers')).'</h1><p>'.esc_html(obra_t('Los registros se guardan en WordPress. Las notificaciones opcionales utilizan wp_mail y el SMTP configurado en el sitio.','Records are stored in WordPress. Optional notifications use wp_mail and your site SMTP settings.')).'</p><a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=obra_export'),'obra_export')).'">'.esc_html(obra_t('Exportar CSV','Export CSV')).'</a><form><input type="hidden" name="page" value="obra-subscribers"><input name="s" placeholder="'.esc_attr(obra_t('Buscar correo','Search email')).'"><button class="button">'.esc_html(obra_t('Buscar','Search')).'</button></form><table class="widefat striped"><thead><tr><th>'.esc_html(obra_t('Nombre','Name')).'</th><th>'.esc_html(obra_t('Correo','Email')).'</th><th>'.esc_html(obra_t('Teléfono','Phone')).'</th><th>'.esc_html(obra_t('Fecha UTC','UTC date')).'</th><th>'.esc_html(obra_t('Idioma','Language')).'</th><th>'.esc_html(obra_t('Notificación','Notification')).'</th><th></th></tr></thead><tbody>';
    foreach($q->posts as $row) {
        $contact=obra_subscriber_contact($row);
        echo '<tr><td>'.esc_html($contact['name']).'</td><td>'.esc_html($contact['email']).'</td><td>'.esc_html($contact['phone']).'</td><td>'.esc_html($row->post_date_gmt).'</td><td>'.esc_html(get_post_meta($row->ID,'language',true)).'</td><td>'.esc_html(get_post_meta($row->ID,'notification',true)==='accepted'?obra_t('Aceptado por el servicio de correo','Accepted by mail service'):(get_post_meta($row->ID,'notification',true)==='failed'?obra_t('Falló el envío del correo','Mail delivery failed'):'—')).'</td><td><form method="post">';
        wp_nonce_field('obra_delete');
        echo '<button class="button" name="delete_id" value="'.absint($row->ID).'">'.esc_html(obra_t('Eliminar','Delete')).'</button></form></td></tr>';
    }
    echo '</tbody></table>'.wp_kses_post(paginate_links(array('total'=>$q->max_num_pages,'current'=>$page))).'</div>';
}
function obra_language($s) {
    $language=function_exists('pll_current_language')?pll_current_language('slug'):null;
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Official WPML integration hook.
    if(!$language)$language=apply_filters('wpml_current_language',null);
    if(!$language)$language=get_locale();
    $language=substr(strtolower($language),0,2);
    return preg_match('/^[a-z]{2}$/',$language)?$language:'en';
}

function obra_site_logo_status(){
 check_ajax_referer('obra_save','nonce');if(!current_user_can('manage_options'))wp_send_json_error(null,403);
 $id=(int)get_theme_mod('custom_logo');$url=wp_get_attachment_image_url($id,'full')?:'';
 $signature=$url?hash('sha256',$id.'|'.$url):'';
 if(!$signature)delete_option('freesoon_logo_seen');
 wp_send_json_success(['url'=>$url,'signature'=>$signature,'pending'=>$signature&&get_option('freesoon_logo_seen','')!==$signature]);
}
function obra_logo_decision(){
 check_ajax_referer('obra_save','nonce');if(!current_user_can('manage_options'))wp_send_json_error(null,403);
 $id=(int)get_theme_mod('custom_logo');$url=wp_get_attachment_image_url($id,'full')?:'';$signature=$url?hash('sha256',$id.'|'.$url):'';
 if(!$signature||!hash_equals($signature,sanitize_text_field(wp_unslash($_POST['signature']??''))))wp_send_json_error(null,409);
 if(sanitize_text_field(wp_unslash($_POST['use']??''))==='1'){$saved=get_option(Obra_Plugin::OPTION,[]);$saved['logo_source']='site';$saved['site_logo_snapshot']=$url;update_option(Obra_Plugin::OPTION,$saved,false);}
 update_option('freesoon_logo_seen',$signature,false);
 wp_send_json_success();
}
