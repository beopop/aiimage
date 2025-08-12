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
    public static function queue( $product_id, $texture_id ) {
        Logger::info( sprintf( 'Queueing generation for product %d', $product_id ) );

        if ( ! function_exists( 'as_enqueue_async_action' ) ) {
            $message = 'Action Scheduler not available.';
            Logger::error( $message );
            return new \WP_Error( 'wcfm_no_scheduler', $message );
        }

        $result = as_enqueue_async_action( self::ACTION, [
            'product_id' => $product_id,
            'texture_id' => $texture_id,
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
        $product_id = $args['product_id'];
        $texture_id = $args['texture_id'];
        Logger::info( sprintf( 'Starting generation task for product %d', $product_id ) );

        $api_key   = get_option( 'wcfm_api_key' );
        $mask_id   = get_option( 'wcfm_mask_image' );
        $mask_path = get_attached_file( $mask_id );
        $texture_path = get_attached_file( $texture_id );

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            Logger::error( 'Product not found: ' . $product_id );
            return;
        }

        $images = array_filter( array_merge( [ $product->get_image_id() ], $product->get_gallery_image_ids() ) );
        $new_ids = [];

        foreach ( $images as $orig_id ) {
            $master_path = get_attached_file( $orig_id );
            if ( ! $master_path ) {
                Logger::error( 'Missing file for attachment ' . $orig_id );
                continue;
            }
            $adapter = new ApiAdapter( $api_key, $master_path, $mask_path );
            $data = $adapter->generate( $texture_path );
            if ( ! $data ) {
                Logger::error( 'Failed to generate image for attachment ' . $orig_id );
                continue;
            }

            $upload_dir = wp_upload_dir();
            $filename   = 'mockup-' . $orig_id . '-' . time() . '.png';
            $filepath   = $upload_dir['path'] . '/' . $filename;
            file_put_contents( $filepath, $data );

            $attachment = [
                'post_mime_type' => 'image/png',
                'post_title'     => 'Mockup ' . $orig_id,
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];
            $attach_id = wp_insert_attachment( $attachment, $filepath );
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $metadata = wp_generate_attachment_metadata( $attach_id, $filepath );
            wp_update_attachment_metadata( $attach_id, $metadata );
            $new_ids[] = $attach_id;
            Logger::info( 'Stored attachment ' . $attach_id . ' for source ' . $orig_id );
        }

        if ( $new_ids ) {
            $product->set_image_id( array_shift( $new_ids ) );
            if ( $new_ids ) {
                $product->set_gallery_image_ids( $new_ids );
            }
            $product->save();
            Logger::info( 'Images replaced for product ' . $product_id );
        } else {
            Logger::error( 'No images generated for product ' . $product_id );
        }
    }
}
