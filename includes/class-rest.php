<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CTS_REST {
    private $processor;
    private $logger;

    public function __construct( CTS_Processor $processor, CTS_Logger $logger ) {
        $this->processor = $processor;
        $this->logger    = $logger;
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'chair-texture-swap/v1', '/process', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'process_batch' ),
            'permission_callback' => array( $this, 'permissions_check' ),
        ) );

        register_rest_route( 'chair-texture-swap/v1', '/status/(?P<job_id>[a-zA-Z0-9-]+)', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'status' ),
            'permission_callback' => array( $this, 'permissions_check' ),
        ) );

        register_rest_route( 'chair-texture-swap/v1', '/cancel/(?P<job_id>[a-zA-Z0-9-]+)', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'cancel' ),
            'permission_callback' => array( $this, 'permissions_check' ),
        ) );
    }

    public function permissions_check( $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        return current_user_can( 'upload_files' ) && wp_verify_nonce( $nonce, 'wp_rest' );
    }

    public function process_batch( WP_REST_Request $request ) {
        $params = $request->get_params();
        $base_ids = array_map( 'intval', (array) ( $params['base_image_ids'] ?? array() ) );
        $texture_id = isset( $params['texture_image_id'] ) ? intval( $params['texture_image_id'] ) : 0;
        $areas = isset( $params['areas'] ) ? array_map( 'sanitize_text_field', (array) $params['areas'] ) : array();

        $size = isset( $params['size'] ) ? $params['size'] : 1024;
        if ( 'base' !== $size ) {
            $allowed_sizes = array( 256, 512, 768, 1024 );
            $size          = intval( $size );
            if ( ! in_array( $size, $allowed_sizes, true ) ) {
                $size = 1024;
            }
        }

        $prompt = sanitize_textarea_field( $params['prompt_overrides'] ?? '' );
        $job_id = uniqid( 'cts_', true );

        $this->logger->info( 'Job created', array( 'context' => $job_id ) );
        $items = $this->processor->process_job( $base_ids, $texture_id, $areas, $size, $prompt );
        set_transient( 'cts_job_' . $job_id, $items, HOUR_IN_SECONDS );

        return rest_ensure_response( array( 'job_id' => $job_id, 'items' => $items ) );
    }

    public function status( WP_REST_Request $request ) {
        $job_id = sanitize_text_field( $request['job_id'] );
        $items  = get_transient( 'cts_job_' . $job_id );
        return rest_ensure_response( array( 'job_id' => $job_id, 'items' => $items ? $items : array() ) );
    }

    public function cancel( WP_REST_Request $request ) {
        $job_id = sanitize_text_field( $request['job_id'] );
        delete_transient( 'cts_job_' . $job_id );
        $this->logger->warn( 'Job canceled', array( 'context' => $job_id ) );
        return rest_ensure_response( array( 'job_id' => $job_id, 'canceled' => true ) );
    }
}

?>
