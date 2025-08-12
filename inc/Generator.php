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
        $logger  = wc_get_logger();
        $context = [ 'source' => 'wc-fabric-mockups' ];
        $logger->info( sprintf( 'Queueing generation for product %d fabric "%s"', $product_id, $fabric_name ), $context );
        as_enqueue_async_action( self::ACTION, [
            'product_id'  => $product_id,
            'fabric_name' => $fabric_name,
            'texture_id'  => $texture_id,
            'angles'      => $angles,
        ] );
    }

    /**
     * Process scheduled action
     */
    public static function process( $args ) {
        $product_id  = $args['product_id'];
        $fabric_name = $args['fabric_name'];
        $texture_id  = $args['texture_id'];
        $angles      = $args['angles'];

        $logger  = wc_get_logger();
        $context = [ 'source' => 'wc-fabric-mockups' ];
        $logger->info( sprintf( 'Starting generation task for product %d fabric "%s"', $product_id, $fabric_name ), $context );

        $api_key = get_option( 'wcfm_api_key' );
        $master_id = get_option( 'wcfm_master_image' );
        $mask_id = get_option( 'wcfm_mask_image' );

        $master_path = get_attached_file( $master_id );
        $mask_path   = get_attached_file( $mask_id );
        $texture_path = get_attached_file( $texture_id );

        $adapter = new ApiAdapter( $api_key, $master_path, $mask_path );
        $image_ids = [];

        foreach ( $angles as $angle ) {
            $logger->info( 'Generating angle ' . $angle, $context );
            $data = $adapter->generate( $texture_path, $angle );
            if ( ! $data ) {
                $logger->error( 'Failed to generate angle ' . $angle, $context );
                continue;
            }
            $logger->info( 'Image generated for angle ' . $angle, $context );

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
            $logger->info( 'Stored attachment ' . $attach_id . ' for angle ' . $angle, $context );
        }

        if ( $image_ids ) {
            $logger->info( 'Creating variation with generated images', $context );
            Woo::create_variation( $product_id, $fabric_name, $image_ids );
            $logger->info( 'Generation completed for product ' . $product_id . ' fabric "' . $fabric_name . '"', $context );
        } else {
            $logger->error( 'No images generated for product ' . $product_id . ' fabric "' . $fabric_name . '"', $context );
        }
    }
}
