<?php
namespace WC_Fabric_Mockups;

class Generator {
    const ACTION = 'wcfm_generate_task';

    public static function init() {
        add_action( self::ACTION, [ __CLASS__, 'process' ], 10, 1 );
    }

    /**
     * Schedule generation
     */
    public static function queue( $product_id, $fabric_name, $texture_id, $angles ) {
        Logger::info( sprintf( 'Queueing generation for product %d fabric "%s"', $product_id, $fabric_name ) );

        if ( ! function_exists( 'as_enqueue_async_action' ) ) {
            $message = 'Action Scheduler not available.';
            Logger::error( $message );
            return new \WP_Error( 'wcfm_no_scheduler', $message );
        }

        $result = as_enqueue_async_action( self::ACTION, [
            'product_id'  => $product_id,
            'fabric_name' => $fabric_name,
            'texture_id'  => $texture_id,
            'angles'      => $angles,
        ] );

        if ( is_wp_error( $result ) ) {
            Logger::error( 'Error scheduling generation: ' . $result->get_error_message() );
            return $result;
        }

        if ( ! $result ) {
            $message = 'Unknown error scheduling generation.';
            Logger::error( $message );
            return new \WP_Error( 'wcfm_schedule_failed', $message );
        }

        Logger::info( 'Generation scheduled with action ID ' . $result );
        return $result;
    }

    /**
     * Process scheduled action
     */
    public static function process( $args ) {
        $product_id  = $args['product_id'];
        $fabric_name = $args['fabric_name'];
        $texture_id  = $args['texture_id'];
        $angles      = $args['angles'];
        Logger::info( sprintf( 'Starting generation task for product %d fabric "%s"', $product_id, $fabric_name ) );

        $api_key = get_option( 'wcfm_api_key' );
        $master_id = get_option( 'wcfm_master_image' );
        $mask_id = get_option( 'wcfm_mask_image' );

        $master_path = get_attached_file( $master_id );
        $mask_path   = get_attached_file( $mask_id );
        $texture_path = get_attached_file( $texture_id );

        $adapter = new ApiAdapter( $api_key, $master_path, $mask_path );
        $image_ids = [];

        foreach ( $angles as $angle ) {
            Logger::info( 'Generating angle ' . $angle );
            $data = $adapter->generate( $texture_path, $angle );
            if ( ! $data ) {
                Logger::error( 'Failed to generate angle ' . $angle );
                continue;
            }
            Logger::info( 'Image generated for angle ' . $angle );

            $upload_dir = wp_upload_dir();
            $filename   = 'mockup-' . sanitize_title( $fabric_name . '-' . $angle ) . '.png';
            $filepath   = $upload_dir['path'] . '/' . $filename;
            file_put_contents( $filepath, $data );

            $attachment = [
                'post_mime_type' => 'image/png',
                'post_title'     => $fabric_name . ' ' . $angle,
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];
            $attach_id = wp_insert_attachment( $attachment, $filepath );
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $metadata = wp_generate_attachment_metadata( $attach_id, $filepath );
            wp_update_attachment_metadata( $attach_id, $metadata );
            $image_ids[] = $attach_id;
            Logger::info( 'Stored attachment ' . $attach_id . ' for angle ' . $angle );
        }

        if ( $image_ids ) {
            Logger::info( 'Creating variation with generated images' );
            Woo::create_variation( $product_id, $fabric_name, $image_ids );
            Logger::info( 'Generation completed for product ' . $product_id . ' fabric "' . $fabric_name . '"' );
        } else {
            Logger::error( 'No images generated for product ' . $product_id . ' fabric "' . $fabric_name . '"' );
        }
    }
}
