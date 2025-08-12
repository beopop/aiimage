<?php
/**
 * Plugin Name: Chair Texture Swap
 * Description: Swap chair upholstery textures using the OpenAI image API.
 * Version: 0.1.0
 * Author: Example Author
 * Text Domain: chair-texture-swap
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CTS_PLUGIN_FILE', __FILE__ );
define( 'CTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CTS_PLUGIN_VERSION', '0.1.0' );

require_once CTS_PLUGIN_DIR . 'includes/helpers.php';
require_once CTS_PLUGIN_DIR . 'includes/class-logger.php';
require_once CTS_PLUGIN_DIR . 'includes/class-openai-client.php';
require_once CTS_PLUGIN_DIR . 'includes/class-processor.php';
require_once CTS_PLUGIN_DIR . 'includes/class-rest.php';
require_once CTS_PLUGIN_DIR . 'includes/class-settings.php';
require_once CTS_PLUGIN_DIR . 'includes/class-admin-menu.php';

register_activation_hook( __FILE__, array( 'CTS_Logger', 'create_table' ) );

function cts_bootstrap() {
    load_plugin_textdomain( 'chair-texture-swap', false, dirname( plugin_basename( CTS_PLUGIN_FILE ) ) . '/languages/' );

    $logger    = new CTS_Logger();
    $client    = new CTS_OpenAI_Client();
    $processor = new CTS_Processor( $client, $logger );
    $rest      = new CTS_REST( $processor, $logger );
    $settings  = new CTS_Settings();
    $admin     = new CTS_Admin_Menu( $settings, $logger );
}
add_action( 'plugins_loaded', 'cts_bootstrap' );

?>
