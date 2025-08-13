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

    public function process_job( $base_ids, $texture_id, $areas, $size, $quality, $prompt_overrides ) {
        $results = array();
        foreach ( $base_ids as $base_id ) {
            $results[] = $this->process_single_image( $base_id, $texture_id, $areas, $size, $quality, $prompt_overrides );
        }
        return $results;
    }

    public function process_single_image( $base_id, $texture_id, $areas, $size, $quality, $prompt_overrides ) {
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

        $prompt = $prompt_overrides ? $prompt_overrides : __( 'Replace chair upholstery texture', 'chair-texture-swap' );
        if ( ! empty( $areas ) ) {
            $prompt .= ' (' . implode( ', ', array_map( 'sanitize_text_field', $areas ) ) . ')';
        }
        $prompt .= ' ' . __( 'Do not alter or modify any existing text or letters in the image.', 'chair-texture-swap' );

        $allowed_sizes = array( '1024x1024', '1024x1536', '1536x1024', 'auto' );
        if ( ! in_array( $size, $allowed_sizes, true ) ) {
            $size_param = '1024x1024';
        } else {
            $size_param = $size;
        }

        $texture_path = get_attached_file( $texture_id );

        $params = array(
            'prompt' => $prompt,
            'size'   => $size_param,
        );

        $base_mime = get_post_mime_type( $base_id );
        if ( ! $base_mime ) {
            $base_mime = mime_content_type( $base_path );
        }

        $images = array(
            curl_file_create( $base_path, $base_mime, basename( $base_path ) ),
        );

        if ( $texture_path && file_exists( $texture_path ) ) {
            $texture_mime = get_post_mime_type( $texture_id );
            if ( ! $texture_mime ) {
                $texture_mime = mime_content_type( $texture_path );
            }
            $images[] = curl_file_create( $texture_path, $texture_mime, basename( $texture_path ) );
        }

        foreach ( $images as $index => $file ) {
            $params[ 'image[' . $index . ']' ] = $file;
        }

        $response = $this->client->image_edit( $params );

        if ( is_wp_error( $response ) ) {
            $message = $response->get_error_message();
            $this->logger->error( 'API request failed', array( 'context' => $base_id, 'message' => $message ) );
            return array(
                'status'  => 'error',
                'id'      => $base_id,
                'message' => $message,
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( 200 !== $code ) {
            $data    = json_decode( $body, true );
            $message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'OpenAI request failed', 'chair-texture-swap' );
            $this->logger->error( 'API error', array( 'context' => $base_id, 'message' => $message ) );
            return array(
                'status'  => 'error',
                'id'      => $base_id,
                'message' => $message,
            );
        }

        $data = json_decode( $body, true );
        if ( empty( $data['data'][0]['b64_json'] ) ) {
            $this->logger->error( 'No image data returned', array( 'context' => $base_id ) );
            return array(
                'status'  => 'error',
                'id'      => $base_id,
                'message' => __( 'No image data returned', 'chair-texture-swap' ),
            );
        }

        $binary    = base64_decode( $data['data'][0]['b64_json'] );
        $result_id = cts_save_image_to_media_library( $binary, $base_id, $texture_id, uniqid( 'cts_', true ), $quality );

        if ( is_wp_error( $result_id ) ) {
            $message = $result_id->get_error_message();
            $this->logger->error( 'Saving image failed', array( 'context' => $base_id, 'message' => $message ) );
            return array(
                'status'  => 'error',
                'id'      => $base_id,
                'message' => $message,
            );
        }

        return array(
            'status'     => 'done',
            'id'         => $base_id,
            'base_url'   => wp_get_attachment_image_url( $base_id, 'thumbnail' ),
            'result_id'  => $result_id,
            'result_url' => wp_get_attachment_image_url( $result_id, 'thumbnail' ),
        );
    }
}

?>
