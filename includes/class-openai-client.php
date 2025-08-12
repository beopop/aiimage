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
        $this->timeout = (int) get_option( 'cts_timeout', 30 );
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

        $args = array(
            'timeout' => $this->timeout,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
            ),
            'body'    => $body,
        );

        return wp_remote_post( $endpoint, $args );
    }
}

?>
