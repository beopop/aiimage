<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CTS_Admin_Menu {
    private $settings;
    private $logger;

    public function __construct( CTS_Settings $settings, CTS_Logger $logger ) {
        $this->settings = $settings;
        $this->logger   = $logger;
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function register_menu() {
        $slug = 'chair-texture-swap';
        $cap  = 'upload_files';
        add_menu_page( __( 'Chair Texture Swap', 'chair-texture-swap' ), __( 'Chair Texture Swap', 'chair-texture-swap' ), $cap, $slug, array( $this, 'render_process_page' ), 'dashicons-admin-customizer' );
        add_submenu_page( $slug, __( 'Promeni teksturu', 'chair-texture-swap' ), __( 'Promeni teksturu', 'chair-texture-swap' ), $cap, $slug, array( $this, 'render_process_page' ) );
        add_submenu_page( $slug, __( 'Podešavanja', 'chair-texture-swap' ), __( 'Podešavanja', 'chair-texture-swap' ), 'manage_options', 'cts-settings', array( $this, 'render_settings_page' ) );
        add_submenu_page( $slug, __( 'Logovi', 'chair-texture-swap' ), __( 'Logovi', 'chair-texture-swap' ), 'manage_options', 'cts-logs', array( $this, 'render_logs_page' ) );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'chair-texture-swap' ) === false && strpos( $hook, 'cts-' ) === false ) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script( 'cts-admin', CTS_PLUGIN_URL . 'assets/admin.js', array( 'jquery' ), CTS_PLUGIN_VERSION, true );
        wp_localize_script( 'cts-admin', 'CTS', array(
            'rest' => array(
                'root'  => esc_url_raw( rest_url( 'chair-texture-swap/v1' ) ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
            ),
        ) );
        wp_enqueue_style( 'cts-admin', CTS_PLUGIN_URL . 'assets/admin.css', array(), CTS_PLUGIN_VERSION );
    }

    public function render_process_page() {
        if ( ! current_user_can( 'upload_files' ) ) {
            wp_die( __( 'You do not have permission to access this page.', 'chair-texture-swap' ) );
        }
        include CTS_PLUGIN_DIR . 'views/page-process.php';
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to access this page.', 'chair-texture-swap' ) );
        }
        include CTS_PLUGIN_DIR . 'views/page-settings.php';
    }

    public function render_logs_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to access this page.', 'chair-texture-swap' ) );
        }
        include CTS_PLUGIN_DIR . 'views/page-logs.php';
    }
}

?>
