<?php
/** WordPress personal-data tools. Phone-only records are managed in Subscribers. */
defined( 'ABSPATH' ) || exit;
add_filter( 'wp_privacy_personal_data_exporters', function ( $exporters ) {
    $exporters['obra-subscribers'] = array( 'exporter_friendly_name' => 'FreeSoon', 'callback' => 'obra_export_personal_data' );
    return $exporters;
} );
add_filter( 'wp_privacy_personal_data_erasers', function ( $erasers ) {
    $erasers['obra-subscribers'] = array( 'eraser_friendly_name' => 'FreeSoon', 'callback' => 'obra_erase_personal_data' );
    return $erasers;
} );
function obra_privacy_records( $email, $page = 1 ) {
    return get_posts( array( 'post_type' => 'obra_subscriber', 'post_status' => array_values( get_post_stati() ), 'posts_per_page' => 100, 'paged' => max( 1, (int) $page ), 'orderby' => 'ID', 'order' => 'ASC', 'title' => sanitize_email( $email ) ) );
}
function obra_export_personal_data( $email, $page = 1 ) {
    $rows = obra_privacy_records( $email, $page );
    $data = array();
    foreach ( $rows as $row ) {
        $contact = obra_subscriber_contact( $row );
        $data[] = array( 'group_id' => 'obra-subscribers', 'group_label' => __( 'FreeSoon subscribers', 'freesoon' ), 'item_id' => 'obra-subscriber-' . $row->ID, 'data' => array(
            array( 'name' => __( 'Name', 'freesoon' ), 'value' => $contact['name'] ),
            array( 'name' => __( 'Email', 'freesoon' ), 'value' => $contact['email'] ),
            array( 'name' => __( 'Phone', 'freesoon' ), 'value' => $contact['phone'] ),
            array( 'name' => __( 'UTC date', 'freesoon' ), 'value' => $row->post_date_gmt ),
            array( 'name' => __( 'Consent', 'freesoon' ), 'value' => get_post_meta( $row->ID, 'consent', true ) ),
        ) );
    }
    return array( 'data' => $data, 'done' => count( $rows ) < 100 );
}
function obra_erase_personal_data( $email, $page = 1 ) {
    // Always take the first batch because previous batches have been deleted.
    $rows = obra_privacy_records( $email );
    $removed = false;
    $retained = false;
    foreach ( $rows as $row ) {
        if ( wp_delete_post( $row->ID, true ) ) { $removed = true; } else { $retained = true; }
    }
    return array( 'items_removed' => $removed, 'items_retained' => $retained, 'messages' => $retained ? array( __( 'Some records could not be erased. Please contact the site administrator.', 'freesoon' ) ) : array(), 'done' => $retained || count( $rows ) < 100 );
}
add_action( 'admin_init', function () {
    if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
        wp_add_privacy_policy_content( 'FreeSoon', wpautop( __( 'If you use the optional FreeSoon notification form, the contact details and launch-notification consent are stored in this WordPress database. Optional administrator notifications use the site mail service. Records remain until the administrator deletes them or uninstalls FreeSoon. State your retention period and contact information here. Email records support WordPress personal-data export and erasure. Phone-only requests must be handled manually in FreeSoon Subscribers. A temporary hashed IP rate-limit key expires after ten minutes. A private-access cookie lasts one day when a visitor uses a private access link.', 'freesoon' ) ) );
    }
} );
