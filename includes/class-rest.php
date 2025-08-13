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
        add_action( 'cts_run_job', array( $this, 'run_job' ), 10, 6 );
    }

    public function register_routes() {
        register_rest_route( 'chair-texture-swap/v1', '/process', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'process_batch' ),
            'permission_callback' => array( $this, 'permissions_check' ),
        ) );

        register_rest_route( 'chair-texture-swap/v1', '/status/(?P<job_id>[a-zA-Z0-9_.-]+)', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'status' ),
            'permission_callback' => array( $this, 'permissions_check' ),
        ) );

        register_rest_route( 'chair-texture-swap/v1', '/cancel/(?P<job_id>[a-zA-Z0-9_.-]+)', array(
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

        $size = isset( $params['size'] ) ? sanitize_text_field( $params['size'] ) : '1024x1024';
        $allowed_sizes = array( '1024x1024', '1024x1536', '1536x1024', 'auto' );
        if ( ! in_array( $size, $allowed_sizes, true ) ) {
            $size = '1024x1024';
        }

        $prompt = sanitize_textarea_field( $params['prompt_overrides'] ?? '' );
        $job_id = uniqid( 'cts_', true );

        $this->logger->info( 'Job created', array( 'context' => $job_id ) );

        set_transient(
            'cts_job_' . $job_id,
            array( 'status' => 'pending', 'items' => array() ),
            HOUR_IN_SECONDS
        );

        wp_schedule_single_event(
            time(),
            'cts_run_job',
            array( $job_id, $base_ids, $texture_id, $areas, $size, $prompt )
        );

        return rest_ensure_response(
            array(
                'job_id' => $job_id,
                'status' => 'pending',
                'items'  => array(),
            )
        );
    }

    public function status( WP_REST_Request $request ) {
        $job_id = sanitize_text_field( $request['job_id'] );
        $data   = get_transient( 'cts_job_' . $job_id );
        if ( ! $data ) {
            $data = array( 'status' => 'pending', 'items' => array() );
        }
        return rest_ensure_response(
            array(
                'job_id' => $job_id,
                'status' => $data['status'],
                'items'  => $data['items'],
            )
        );
    }

    public function cancel( WP_REST_Request $request ) {
        $job_id = sanitize_text_field( $request['job_id'] );
        delete_transient( 'cts_job_' . $job_id );
        $this->logger->warn( 'Job canceled', array( 'context' => $job_id ) );
        return rest_ensure_response( array( 'job_id' => $job_id, 'canceled' => true ) );
    }

    public function run_job( $job_id, $base_ids, $texture_id, $areas, $size, $prompt ) {
        ignore_user_abort( true );
        $items = $this->processor->process_job( $base_ids, $texture_id, $areas, $size, $prompt );
        set_transient(
            'cts_job_' . $job_id,
            array( 'status' => 'done', 'items' => $items ),
            HOUR_IN_SECONDS
        );
    }
}

?>
