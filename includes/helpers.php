<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cts_save_image_to_media_library( $binary, $base_id, $texture_id, $job_id ) {
    $upload_dir = wp_upload_dir();
    $base_name  = pathinfo( get_attached_file( $base_id ), PATHINFO_FILENAME );
    $filename   = sanitize_file_name( $base_name . '-texture-swap-' . current_time( 'Ymd-His' ) . '.png' );
    $filepath   = trailingslashit( $upload_dir['path'] ) . $filename;

    if ( ! wp_mkdir_p( $upload_dir['path'] ) ) {
        return new WP_Error( 'cts_upload_dir', __( 'Upload directory not writable', 'chair-texture-swap' ) );
    }

    if ( false === file_put_contents( $filepath, $binary ) ) {
        return new WP_Error( 'cts_write_failed', __( 'Could not write file', 'chair-texture-swap' ) );
    }

    $filetype = wp_check_filetype( $filename, null );

    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_text_field( $base_name ),
        'post_content'   => '',
        'post_status'    => 'inherit',
    );

    $attach_id = wp_insert_attachment( $attachment, $filepath );

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $metadata = wp_generate_attachment_metadata( $attach_id, $filepath );
    wp_update_attachment_metadata( $attach_id, $metadata );

    update_post_meta( $attach_id, '_cts_source_id', (int) $base_id );
    update_post_meta( $attach_id, '_cts_texture_id', (int) $texture_id );
    update_post_meta( $attach_id, '_cts_job_id', sanitize_text_field( $job_id ) );

    return $attach_id;
}

?>
