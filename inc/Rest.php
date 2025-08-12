<?php
namespace WC_Fabric_Mockups;

class Rest {
    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        register_rest_route( 'wc-fabric-mockups/v1', '/generate', [
            'methods'             => 'POST',
            'permission_callback' => function () {
                return current_user_can( 'manage_woocommerce' );
            },
            'callback'            => [ __CLASS__, 'handle_generate' ],
        ] );
    }

    public static function handle_generate( $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! wp_verify_nonce( $nonce, 'wcfm_generate' ) ) {
            return new \WP_Error( 'wcfm_nonce', __( 'Invalid nonce', 'wcfm' ), [ 'status' => 403 ] );
        }

        $product_id  = intval( $request['product_id'] );
        $fabric_name = sanitize_text_field( $request['fabric_name'] );
        $texture_id  = intval( $request['texture_id'] );
        $all         = ! empty( $request['all_angles'] );

        $angles = $all ? [ 'front', 'front-left', 'left', 'back', 'right', 'front-right' ] : [ 'front' ];

        $logger  = wc_get_logger();
        $context = [ 'source' => 'wc-fabric-mockups' ];
        $logger->info( sprintf( 'Generation requested for product %d with fabric "%s" (texture %d)', $product_id, $fabric_name, $texture_id ), $context );
        $logger->info( 'Angles queued: ' . implode( ', ', $angles ), $context );

        Generator::queue( $product_id, $fabric_name, $texture_id, $angles );

        return [ 'scheduled' => true ];
    }
}
