<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CTS_Settings {
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function register_settings() {
        register_setting( 'cts_settings', 'cts_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'cts_settings', 'cts_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'cts_settings', 'cts_timeout', array( 'sanitize_callback' => 'absint' ) );

        add_settings_section( 'cts_section', __( 'OpenAI Settings', 'chair-texture-swap' ), '__return_false', 'cts_settings' );

        add_settings_field( 'cts_api_key', __( 'OpenAI API Key', 'chair-texture-swap' ), array( $this, 'field_api_key' ), 'cts_settings', 'cts_section' );
        add_settings_field( 'cts_model', __( 'Model', 'chair-texture-swap' ), array( $this, 'field_model' ), 'cts_settings', 'cts_section' );
        add_settings_field( 'cts_timeout', __( 'Timeout (s)', 'chair-texture-swap' ), array( $this, 'field_timeout' ), 'cts_settings', 'cts_section' );
    }

    public function field_api_key() {
        echo '<input type="text" name="cts_api_key" value="' . esc_attr( get_option( 'cts_api_key' ) ) . '" class="regular-text" />';
    }

    public function field_model() {
        echo '<input type="text" name="cts_model" value="' . esc_attr( get_option( 'cts_model', 'gpt-image-1' ) ) . '" class="regular-text" />';
    }

    public function field_timeout() {
        echo '<input type="number" name="cts_timeout" value="' . esc_attr( get_option( 'cts_timeout', 60 ) ) . '" class="small-text" />';
    }
}

?>
