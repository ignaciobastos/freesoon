<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }
function obra_remove_site_data() {
    delete_option( 'obra_settings' );
    delete_option( 'freesoon_logo_seen' );
    delete_transient( 'obra_cache_bust' );
    do {
        $obra_ids = get_posts( array( 'post_type' => 'obra_subscriber', 'post_status' => array_values( get_post_stati() ), 'fields' => 'ids', 'posts_per_page' => 100 ) );
        foreach ( $obra_ids as $obra_id ) { wp_delete_post( $obra_id, true ); }
    } while ( count( $obra_ids ) === 100 );
}
if ( is_multisite() ) {
    $obra_offset = 0;
    do {
        $obra_sites = get_sites( array( 'fields' => 'ids', 'number' => 100, 'offset' => $obra_offset ) );
        foreach ( $obra_sites as $obra_site ) { switch_to_blog( $obra_site ); obra_remove_site_data(); restore_current_blog(); }
        $obra_offset += 100;
    } while ( count( $obra_sites ) === 100 );
} else { obra_remove_site_data(); }
