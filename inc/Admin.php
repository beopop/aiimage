<?php
namespace WC_Fabric_Mockups;

use WP_Post;

class Admin {
    public static function init() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'register_metabox' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
        add_action( 'admin_menu', [ __CLASS__, 'settings_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function register_metabox() {
        add_meta_box( 'wcfm_fabric_mockups', __( 'Fabric Mockups', 'wcfm' ), [ __CLASS__, 'render_metabox' ], 'product', 'side' );
    }

    public static function render_metabox( WP_Post $post ) {
        wp_nonce_field( 'wcfm_metabox', 'wcfm_nonce' );
        ?>
        <p>
            <label><?php _e( 'Fabric texture', 'wcfm' ); ?></label><br/>
            <input type="hidden" id="wcfm_texture_id" />
            <button type="button" class="button" id="wcfm_upload_texture"><?php _e( 'Upload/Select', 'wcfm' ); ?></button>
            <span id="wcfm_texture_status" class="dashicons dashicons-yes" style="display:none;color:#46b450;margin-left:5px;"></span>
            <div id="wcfm_texture_preview" style="margin-top:10px;"></div>
        </p>
        <p>
            <button type="button" class="button button-primary" id="wcfm_generate"><?php _e( 'Generate mockups', 'wcfm' ); ?></button>
        </p>
        <?php
    }

    public static function enqueue( $hook ) {
        if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
            $screen = get_current_screen();
            if ( 'product' !== $screen->post_type ) {
                return;
            }
            wp_enqueue_media();
            wp_enqueue_script( 'wcfm-admin', WCFM_PLUGIN_URL . 'assets/admin.js', [ 'jquery' ], '1.0', true );
            wp_localize_script( 'wcfm-admin', 'WCFM', [
                'nonce'      => wp_create_nonce( 'wp_rest' ),
                'rest_url'   => rest_url( 'wc-fabric-mockups/v1/generate' ),
                'product_id' => get_the_ID(),
            ] );
        }
    }

    public static function settings_menu() {
        add_options_page( 'Fabric Mockups', 'Fabric Mockups', 'manage_options', 'wcfm-settings', [ __CLASS__, 'render_settings' ] );
        add_options_page( 'Fabric Mockups Logs', 'Fabric Mockups Logs', 'manage_options', 'wcfm-logs', [ __CLASS__, 'render_logs' ] );
    }

    public static function register_settings() {
        // API key stored with autoload = no
        if ( false === get_option( 'wcfm_api_key', false ) ) {
            add_option( 'wcfm_api_key', '', '', 'no' );
        }
        register_setting( 'wcfm-settings', 'wcfm_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        if ( false === get_option( 'wcfm_enable_logging', false ) ) {
            add_option( 'wcfm_enable_logging', 0, '', 'no' );
        }
        register_setting( 'wcfm-settings', 'wcfm_enable_logging', [ 'sanitize_callback' => 'absint', 'default' => 0 ] );
    }

    public static function render_settings() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Fabric Mockups Settings', 'wcfm' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'wcfm-settings' );
                ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wcfm_api_key"><?php _e( 'OpenAI API Key', 'wcfm' ); ?></label></th>
                        <td><input type="text" name="wcfm_api_key" id="wcfm_api_key" value="<?php echo esc_attr( get_option( 'wcfm_api_key' ) ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcfm_enable_logging"><?php _e( 'Enable logging', 'wcfm' ); ?></label></th>
                        <td>
                            <input type="checkbox" name="wcfm_enable_logging" id="wcfm_enable_logging" value="1" <?php checked( get_option( 'wcfm_enable_logging' ), 1 ); ?> />
                            <p class="description"><?php _e( 'Log plugin activity to WooCommerce logs.', 'wcfm' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><a href="<?php echo esc_url( admin_url( 'options-general.php?page=wcfm-logs' ) ); ?>"><?php _e( 'View logs', 'wcfm' ); ?></a></p>
        </div>
        <?php
    }

    /**
     * Render debug log page.
     */
    public static function render_logs() {
        if ( isset( $_POST['wcfm_clear_logs'] ) && check_admin_referer( 'wcfm_clear_logs' ) ) {
            Logger::clear();
            echo '<div class="updated"><p>' . esc_html__( 'Logs cleared.', 'wcfm' ) . '</p></div>';
        }

        $logs = Logger::get_logs();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Fabric Mockups Logs', 'wcfm' ); ?></h1>
            <form method="post">
                <?php wp_nonce_field( 'wcfm_clear_logs' ); ?>
                <?php submit_button( __( 'Clear Logs', 'wcfm' ), 'secondary', 'wcfm_clear_logs', false ); ?>
            </form>
            <pre style="background:#fff;border:1px solid #ccc;padding:10px;max-height:500px;overflow:auto;">
<?php
if ( $logs ) {
    foreach ( $logs as $entry ) {
        echo esc_html( sprintf( '[%s] %s: %s', $entry['time'], strtoupper( $entry['level'] ), $entry['message'] ) ) . "\n";
    }
} else {
    esc_html_e( 'No log entries found.', 'wcfm' );
}
?>
            </pre>
        </div>
        <?php
    }
}
