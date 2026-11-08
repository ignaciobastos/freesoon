<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Obra_Plugin {
    const OPTION = 'obra_settings';
    private static $instance;

    public static function instance() {
        if ( ! self::$instance ) { self::$instance = new self(); }
        return self::$instance;
    }

    private function __construct() {
        require_once OBRA_DIR . 'includes/studio-v2.php';
        obra_v2_hooks();
        add_action('wp_ajax_obra_site_logo', 'obra_site_logo_status');
        add_action('wp_ajax_obra_logo_decision', 'obra_logo_decision');
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 100 );
        add_action( 'admin_notices', array( $this, 'active_notice' ) );
        add_action( 'wp_ajax_obra_save', array( $this, 'ajax_save' ) );
        add_action( 'wp_ajax_obra_preview_render', array( $this, 'ajax_preview_render' ) );
        add_action( 'template_redirect', array( $this, 'maybe_render' ), 0 );
        add_filter( 'rest_pre_dispatch', array( $this, 'protect_rest' ), 10, 3 );
    }

    public static function defaults() {
        return array(
            'enabled' => false, 'auto_open' => false, 'mode' => 'coming_soon', 'template' => 'center',
            'gradient' => true, 'motion' => true, 'motion_preference' => true, 'smart_text' => true,
            'content_language' => 'auto', 'translations' => array(), 'custom_content' => array(),
            'notify' => false, 'notification_email' => get_option('admin_email'),
            'legal_links' => array(), 'privacy_url' => '', 'show_language_switch' => true,
            'site_logo_snapshot' => wp_get_attachment_image_url(get_theme_mod('custom_logo'),'full') ?: '', 'logo' => '', 'logo_source' => (has_custom_logo() ? 'site' : 'custom'), 'logo_smart' => true, 'logo_trim' => true, 'logo_force' => false, 'eyebrow' => 'ESTAMOS CONSTRUYENDO ALGO ESPECIAL',
            'title' => 'Muy pronto, una nueva forma de verlo todo.',
            'text' => 'Estamos dando los últimos retoques. Déjanos tu correo y te avisaremos cuando abramos las puertas.',
            'button_text' => 'Avísame', 'show_form' => true, 'form_email' => false, 'form_name' => false, 'form_phone' => false, 'email_placeholder' => 'Tu correo', 'name_placeholder' => 'Tu nombre', 'phone_placeholder' => 'Tu teléfono', 'show_countdown' => true,
            'launch_date' => wp_date( 'Y-m-d\TH:i', time() + WEEK_IN_SECONDS ),
            'background_media_kind' => 'image', 'background_fit' => 'cover', 'background_position' => '50% 50%',
            'background_type' => 'gradient', 'background_image' => '',
            'background_video' => '', 'color_bg' => '#0c0d10', 'color_accent' => '#d9ff57',
            'color_text' => '#f5f4ef', 'font' => 'system', 'overlay' => 35,
            'animation' => 'float', 'social_instagram' => '', 'social_linkedin' => '',
            'social_x' => '', 'custom_css' => '', 'exclude_paths' => '',
            'bypass_token' => wp_generate_password( 18, false ), 'allow_logged_in' => true,
            'seo_title' => 'Estamos preparando algo nuevo', 'seo_description' => 'Nuestro sitio estará disponible muy pronto.',
        );
    }

    public static function activate($network_wide=false) {
        if(is_multisite() && $network_wide){
            $offset=0; do { $sites=get_sites(['fields'=>'ids','number'=>100,'offset'=>$offset]); foreach($sites as $site){ switch_to_blog($site); self::activate(false); restore_current_blog(); } $offset+=100; }while(count($sites)===100); return;
        }
        if ( ! get_option( self::OPTION ) ) { add_option( self::OPTION, self::defaults(), '', false ); }
    }

    public static function deactivate() { delete_transient( 'obra_cache_bust' ); }

    public static function settings() {
        $saved=get_option(self::OPTION,array());
        unset($saved['editor_language']);
        if(!isset($saved['motion_preference']))$saved['motion_preference']=$saved['motion']??true;
        if(!isset($saved['logo_source']) && !empty($saved['logo'])) $saved['logo_source']='custom';
        if(!array_key_exists('site_logo_snapshot',$saved)) { $saved['site_logo_snapshot']=wp_get_attachment_image_url(get_theme_mod('custom_logo'),'full')?:''; update_option(self::OPTION,$saved,false); }
        $settings=wp_parse_args($saved,self::defaults());
        $effective=obra_apply_launch($settings);
        if($settings['enabled']&&!$effective['enabled']){$saved['enabled']=false;update_option(self::OPTION,$saved,false);}
        return obra_resolve_legal_settings(obra_localize_content($effective));
    }

    public function admin_menu() {
        add_menu_page( 'FreeSoon', 'FreeSoon', 'manage_options', 'obra', array( $this, 'admin_page' ), 'dashicons-hammer', 3 );
    }

    public function admin_assets( $hook ) {
        if ( 'toplevel_page_obra' !== $hook ) { return; }
        wp_enqueue_media();
        wp_enqueue_style( 'obra-admin', OBRA_URL . 'assets/css/admin.css', array(), OBRA_VERSION );
        wp_enqueue_script( 'obra-icons', OBRA_URL . 'assets/js/lucide.min.js', array(), OBRA_VERSION, true );
        wp_enqueue_script( 'obra-admin', OBRA_URL . 'assets/js/admin.js', array('obra-icons'), OBRA_VERSION, true );
        $copy_defaults=[];foreach(['none','email','phone','both'] as $contact_mode){$copy_settings=self::defaults();$copy_settings['form_email']=in_array($contact_mode,['email','both'],true);$copy_settings['form_phone']=in_array($contact_mode,['phone','both'],true);$copy_defaults[$contact_mode]=obra_localize_content($copy_settings)['text'];}
        wp_localize_script( 'obra-admin', 'OBRA_DATA', array(
            'defaultCopy' => $copy_defaults, 'messages' => obra_js_messages(), 'editorLocale' => substr(get_locale(),0,2), 'legalDetected' => obra_detect_legal(), 'legalPosts' => obra_legal_posts(), 'privacyCandidates' => count(obra_privacy_candidates()),
            'fonts' => array_values(obra_fonts()), 'settings' => self::settings(), 'ajax' => admin_url( 'admin-ajax.php' ),
            'locale' => obra_language(self::settings()),
            'siteLogo' => wp_get_attachment_image_url(get_theme_mod('custom_logo'),'full') ?: '',
            'uploadNonce' => wp_create_nonce('media-form'),
            'nonce' => wp_create_nonce( 'obra_save' ), 'siteUrl' => home_url( '/' ),
            'previewUrl' => add_query_arg( 'obra_preview', wp_create_nonce( 'obra_preview' ), home_url( '/' ) ),
        ) );
    }

    public function admin_page() { include OBRA_DIR . 'templates/admin-v2.php'; }

    public function ajax_preview_render() {
        check_ajax_referer( 'obra_save', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( '', '', array( 'response' => 403 ) ); }
        $raw = json_decode( file_get_contents( 'php://input' ), true );
        $settings = $this->sanitize_settings( is_array( $raw['settings'] ?? null ) ? $raw['settings'] : array() );
        $obra_editor = true;
        header( 'Content-Type: text/html; charset=UTF-8' );
        include OBRA_DIR . 'templates/page-v2.php';
        exit;
    }

    public function ajax_save() {
        check_ajax_referer( 'obra_save', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => obra_t('Sin permisos.','Permission denied.') ), 403 ); }
        $raw = json_decode( file_get_contents( 'php://input' ), true );
        if ( ! is_array( $raw ) ) { $raw = wp_unslash($_POST); }
        if (!is_array($raw['settings'] ?? null)) wp_send_json_error(array('message'=>obra_t('Ajustes no válidos','Invalid settings')),400);
        $settings = $this->sanitize_settings( $raw['settings'] );
        if($settings['auto_open']&&obra_launch_time($settings['launch_date'])===false)wp_send_json_error(['message'=>obra_t('Introduce una fecha de lanzamiento válida.','Enter a valid launch date.')],400);
        $settings=obra_apply_launch($settings);
        update_option( self::OPTION, $settings, false );
        do_action( 'obra_settings_saved', $settings );
        wp_send_json_success( array( 'message' => obra_t('Diseño guardado.','Design saved.'), 'settings' => $settings ) );
    }

    private function sanitize_settings( $input ) {
        $defaults = self::defaults(); $out = array();
        $booleans = array( 'enabled', 'auto_open', 'show_form', 'show_countdown', 'allow_logged_in', 'gradient', 'motion', 'smart_text', 'notify', 'show_language_switch', 'logo_smart', 'logo_trim', 'logo_force', 'motion_preference', 'form_email', 'form_name', 'form_phone' );
        $urls = array( 'site_logo_snapshot', 'logo', 'background_image', 'background_video', 'social_instagram', 'social_linkedin', 'social_x' );
        foreach ( $defaults as $key => $default ) {
            $value = isset( $input[ $key ] ) ? $input[ $key ] : $default;
            if ('legal_links' === $key) { $out[$key]=obra_sanitize_legal($value); continue; }
            if ('custom_content' === $key) {
                $out[$key]=[];
                foreach(array_keys(obra_default_content('en')) as $field)if(!empty($value[$field]))$out[$key][$field]=true;
                continue;
            }
            if ('translations' === $key) {
                $out[$key] = array();
                foreach ((array)$value as $lang => $content) {
                    if (!in_array($lang, array('es','en','fr','de','it','pt'),true) || !is_array($content)) continue;
                    foreach (array('title','text','eyebrow','button_text') as $field) {
                        if (isset($content[$field]) && is_scalar($content[$field])) $out[$key][$lang][$field] = sanitize_textarea_field($content[$field]);
                    }
                }
                continue;
            }
            if (!is_scalar($value)) $value = $default;
            if ('exclude_paths' === $key) { $out[$key] = sanitize_textarea_field($value); continue; }
            if ( in_array( $key, $booleans, true ) ) { $out[ $key ] = (bool) $value; }
            elseif ( in_array( $key, $urls, true ) ) { $out[ $key ] = esc_url_raw( $value ); }
            elseif ( 0 === strpos( $key, 'color_' ) ) { $out[ $key ] = sanitize_hex_color( $value ) ?: $default; }
            elseif ( 'custom_css' === $key ) { $out[ $key ] = wp_strip_all_tags( $value ); }
            elseif ( 'overlay' === $key ) { $out[ $key ] = min( 90, max( 0, absint( $value ) ) ); }
            elseif ( 'text' === $key ) { $out[ $key ] = wp_kses_post( $value ); }
            else { $out[ $key ] = sanitize_text_field( $value ); }
        }
        $out['mode'] = in_array( $out['mode'], array( 'coming_soon', 'maintenance' ), true ) ? $out['mode'] : 'coming_soon';
        $out['background_fit'] = in_array($out['background_fit'], ['cover','contain','original','height','tile'], true) ? $out['background_fit'] : 'cover';
        $out['background_position'] = in_array($out['background_position'], ['0% 0%','50% 0%','100% 0%','0% 50%','50% 50%','100% 50%','0% 100%','50% 100%','100% 100%'], true) ? $out['background_position'] : '50% 50%';
        $out['motion'] = $out['gradient'] && $out['motion_preference'];
        if(!isset(obra_fonts()[$out['font']]))$out['font']='system';
        if(!$out['form_email']&&!$out['form_phone'])$out['form_name']=false;
        return obra_resolve_legal_settings(obra_localize_content($out));
    }

    private function is_bypassed( $settings ) {
        if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) { return true; }
        if ( $settings['allow_logged_in'] && is_user_logged_in() ) { return true; }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The high-entropy private access token is the credential; this does not change site settings.
        if ( isset( $_GET['obra_bypass'] ) && hash_equals( $settings['bypass_token'], sanitize_text_field( wp_unslash( $_GET['obra_bypass'] ) ) ) ) {
            setcookie( 'obra_bypass', hash_hmac( 'sha256', $settings['bypass_token'], wp_salt() ), time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
            return true;
        }
        if ( isset( $_COOKIE['obra_bypass'] ) && hash_equals( hash_hmac( 'sha256', $settings['bypass_token'], wp_salt() ), sanitize_text_field( wp_unslash( $_COOKIE['obra_bypass'] ) ) ) ) { return true; }
        $path = trim( (string) wp_parse_url( sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'] ?? '/')), PHP_URL_PATH ), '/' );
        foreach ( preg_split( '/\r\n|\r|\n/', $settings['exclude_paths'] ) as $excluded ) {
            $excluded = trim( $excluded, " /\t" );
            if ( $excluded && ( $path === $excluded || 0 === strpos( $path, $excluded . '/' ) ) ) { return true; }
        }
        return (bool) apply_filters( 'obra_should_bypass', false, $settings );
    }

    public function maybe_render() {
        $settings = self::settings();
        foreach(obra_effective_legal($settings) as $link) if(!empty($link['post']) && is_singular() && get_queried_object_id()===$link['post'])return;
        $obra_preview = isset( $_GET['obra_preview'] ) && current_user_can( 'manage_options' ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['obra_preview'] ) ), 'obra_preview' );
        if ( ! $obra_preview && ( ! $settings['enabled'] || $this->is_bypassed( $settings ) ) ) { return; }
        if ( 'maintenance' === $settings['mode'] ) {
            status_header( 503 ); header( 'Retry-After: 3600' );
        } else { status_header( 200 ); }
        nocache_headers(); header( 'X-Robots-Tag: noindex, nofollow', true );
        include OBRA_DIR . 'templates/page-v2.php'; exit;
    }

    public function protect_rest( $result, $server, $request ) {
        $settings = self::settings();
        if ( $settings['enabled'] && ! $this->is_bypassed( $settings ) ) {
            return new WP_Error( 'obra_private', obra_t('El sitio está temporalmente en construcción.','The site is temporarily under construction.'), array( 'status' => 503 ) );
        }
        return $result;
    }

    public function admin_bar( $bar ) {
        $s = self::settings(); if ( ! $s['enabled'] || ! current_user_can( 'manage_options' ) ) { return; }
        $bar->add_node( array( 'id' => 'obra-status', 'title' => obra_t('● FreeSoon activo','● FreeSoon active'), 'href' => admin_url( 'admin.php?page=obra' ), 'meta' => array( 'class' => 'obra-admin-bar' ) ) );
    }

    public function active_notice() {
        $s = self::settings(); if ( ! $s['enabled'] || ! current_user_can( 'manage_options' ) || ( get_current_screen() && 'toplevel_page_obra' === get_current_screen()->id ) ) { return; }
        echo '<div class="notice notice-warning"><p><strong>'.esc_html(obra_t('FreeSoon está activo.','FreeSoon is active.')).'</strong> '.esc_html(obra_t('Los visitantes ven la pantalla en construcción.','Visitors see the maintenance screen.')).' <a href="' . esc_url( admin_url( 'admin.php?page=obra' ) ) . '">'.esc_html(obra_t('Abrir el estudio','Open studio')).'</a></p></div>';
    }
}

