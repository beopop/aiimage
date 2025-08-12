<?php
/**
 * Plugin Name: WooCommerce Fabric Mockups
 * Description: Generate chair fabric mockup images using OpenAI DALL·E and create WooCommerce variations.
 * Version: 1.0.0
 * Author: ChatGPT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin paths
define( 'WCFM_PLUGIN_FILE', __FILE__ );
define( 'WCFM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCFM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once WCFM_PLUGIN_DIR . 'inc/Admin.php';
require_once WCFM_PLUGIN_DIR . 'inc/Generator.php';
require_once WCFM_PLUGIN_DIR . 'inc/Woo.php';
require_once WCFM_PLUGIN_DIR . 'inc/Rest.php';
require_once WCFM_PLUGIN_DIR . 'inc/ApiAdapter.php';

/**
 * Bootstrap the plugin
 */
function wcfm_init() {
    \WC_Fabric_Mockups\Woo::init();
    \WC_Fabric_Mockups\Generator::init();
    \WC_Fabric_Mockups\Rest::init();

    if ( is_admin() ) {
        \WC_Fabric_Mockups\Admin::init();
    }
}
add_action( 'plugins_loaded', 'wcfm_init' );
