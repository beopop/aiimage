<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CTS_Processor {
    private $client;
    private $logger;

    public function __construct( CTS_OpenAI_Client $client, CTS_Logger $logger ) {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function process_job( $base_ids, $texture_id, $areas, $size, $prompt_overrides ) {
        $results = array();
        foreach ( $base_ids as $base_id ) {
            $results[] = $this->process_single_image( $base_id, $texture_id, $areas, $size, $prompt_overrides );
        }
        return $results;
    }

    public function process_single_image( $base_id, $texture_id, $areas, $size, $prompt_overrides ) {
        $this->logger->info( 'Processing image', array( 'context' => $base_id ) );

        $base_path = get_attached_file( $base_id );
        if ( ! $base_path || ! file_exists( $base_path ) ) {
            $this->logger->error( 'Base image not found', array( 'context' => $base_id ) );
            return array(
                'status'  => 'error',
                'id'      => $base_id,
                'message' => __( 'Base image not found', 'chair-texture-swap' ),
            );
        }

        $upload_dir   = wp_upload_dir();
        $mime         = get_post_mime_type( $base_id );
        $new_filename = wp_unique_filename( $upload_dir['path'], 'cts-' . basename( $base_path ) );
        $new_path     = trailingslashit( $upload_dir['path'] ) . $new_filename;

        if ( ! copy( $base_path, $new_path ) ) {
            $this->logger->error( 'Failed to copy image', array( 'context' => $base_id ) );
            return array(
                'status'  => 'error',
                'id'      => $base_id,
                'message' => __( 'Could not create result image', 'chair-texture-swap' ),
            );
        }

        $attachment = array(
            'post_mime_type' => $mime,
            'post_title'     => sanitize_text_field( 'CTS result ' . $base_id ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attach_id = wp_insert_attachment( $attachment, $new_path );
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata( $attach_id, $new_path );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        return array(
            'status'     => 'done',
            'id'         => $base_id,
            'base_url'   => wp_get_attachment_image_url( $base_id, 'thumbnail' ),
            'result_id'  => $attach_id,
            'result_url' => wp_get_attachment_image_url( $attach_id, 'thumbnail' ),
        );
    }
}

?>
