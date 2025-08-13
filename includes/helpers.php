<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cts_save_image_to_media_library( $binary, $base_id, $texture_id, $job_id, $quality = 10 ) {
    $upload_dir = wp_upload_dir();
    $base_name = pathinfo( get_attached_file( $base_id ), PATHINFO_FILENAME );
    $base_dir  = trailingslashit( $upload_dir['path'] );
    $counter   = 1;
    do {
        $name      = $base_name . '-' . $counter;
        $png_path  = $base_dir . sanitize_file_name( $name . '.png' );
        $jpg_path  = $base_dir . sanitize_file_name( $name . '.jpg' );
        $counter++;
    } while ( file_exists( $png_path ) || file_exists( $jpg_path ) );

    $filename = basename( $png_path );
    $filepath = $png_path;

    if ( ! wp_mkdir_p( $upload_dir['path'] ) ) {
        return new WP_Error( 'cts_upload_dir', __( 'Upload directory not writable', 'chair-texture-swap' ) );
    }

    if ( false === file_put_contents( $filepath, $binary ) ) {
        return new WP_Error( 'cts_write_failed', __( 'Could not write file', 'chair-texture-swap' ) );
    }

    $quality     = max( 1, min( 10, intval( $quality ) ) );
    $max_size    = $quality * 100 * 1024; // Rough target size in bytes.
    if ( filesize( $filepath ) > $max_size ) {
        $editor = wp_get_image_editor( $filepath );
        if ( ! is_wp_error( $editor ) ) {
            $jpg_quality    = 90;
            $compressed_path = preg_replace( '/\.png$/i', '.jpg', $filepath );
            do {
                $editor->set_quality( $jpg_quality );
                $result = $editor->save( $compressed_path, 'image/jpeg' );
                if ( ! is_wp_error( $result ) && file_exists( $result['path'] ) && filesize( $result['path'] ) <= $max_size ) {
                    @unlink( $filepath );
                    $filepath = $result['path'];
                    $filename = basename( $compressed_path );
                    break;
                }
                $jpg_quality -= 10;
            } while ( $jpg_quality >= 10 );

            if ( file_exists( $compressed_path ) && $filepath !== $compressed_path ) {
                @unlink( $filepath );
                $filepath = $compressed_path;
                $filename = basename( $compressed_path );
            }
        }
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
