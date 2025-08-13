<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CTS_OpenAI_Client {
    private $api_key;
    private $model;
    private $timeout;

    public function __construct() {
        $this->api_key = get_option( 'cts_api_key', '' );
        $this->model   = get_option( 'cts_model', 'gpt-image-1' );
        // Ensure a sane minimum timeout so large image requests have
        // enough time to complete. Users may override this value via the
        // settings screen, but we never allow less than 60 seconds.
        $option_timeout = (int) get_option( 'cts_timeout', 300 );
        $this->timeout  = max( 60, $option_timeout );
    }

    /**
     * Build multipart/form-data body with boundary.
     *
     * @param array  $params   Fields and files to send.
     * @param string $boundary Generated boundary (passed by reference).
     * @return string
     */
    private function build_multipart_body( $params, &$boundary ) {
        $boundary = wp_generate_uuid4();
        $eol      = "\r\n";
        $body     = '';

        foreach ( $params as $name => $content ) {
            $body .= '--' . $boundary . $eol;
            if ( $content instanceof CURLFile ) {
                $filename = basename( $content->getFilename() );
                $mime     = $content->getMimeType();
                if ( empty( $mime ) ) {
                    $mime = 'application/octet-stream';
                }
                $body .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $filename . '"' . $eol;
                $body .= 'Content-Type: ' . $mime . $eol . $eol;
                $body .= file_get_contents( $content->getFilename() ) . $eol;
            } else {
                $body .= 'Content-Disposition: form-data; name="' . $name . '"' . $eol . $eol;
                $body .= $content . $eol;
            }
        }

        $body .= '--' . $boundary . '--' . $eol;
        return $body;
    }

    public function image_edit( $params ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error(
                'cts_missing_api_key',
                __( 'OpenAI API key is not set', 'chair-texture-swap' )
            );
        }

        $endpoint = 'https://api.openai.com/v1/images/edits';

        // Ensure the required model parameter is always sent.
        $params['model'] = $this->model;

        $body = $this->build_multipart_body( $params, $boundary );

        // Bump PHP execution limits to give long-running image requests a
        // better chance of completing before the server kills the process.
        @set_time_limit( $this->timeout * 2 );
        @ini_set( 'max_execution_time', $this->timeout * 2 );

        $args = array(
            'timeout' => $this->timeout,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
            ),
            'body'    => $body,
        );

        $response = wp_remote_post( $endpoint, $args );

        // Some hosts are slow to contact the OpenAI API which can trigger
        // a cURL 28 timeout. In that case we retry once with a longer
        // timeout to give the request a better chance to succeed.
        if ( is_wp_error( $response ) && false !== strpos( $response->get_error_message(), 'cURL error 28' ) ) {
            $args['timeout'] = $this->timeout * 2;
            $response        = wp_remote_post( $endpoint, $args );
        }

        return $response;
    }
}

?>
