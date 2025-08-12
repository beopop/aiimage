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

    public function image_edit( $params ) {
        $endpoint = 'https://api.openai.com/v1/images/edits';
        $args = array(
            'timeout' => $this->timeout,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'body'    => $params,
        );
        return wp_remote_post( $endpoint, $args );
    }
}

?>
