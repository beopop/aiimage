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
            <label for="wcfm_fabric_name"><?php _e( 'Fabric name', 'wcfm' ); ?></label>
            <input type="text" id="wcfm_fabric_name" class="widefat" />
        </p>
        <p>
            <label><?php _e( 'Fabric texture', 'wcfm' ); ?></label><br/>
            <input type="hidden" id="wcfm_texture_id" />
            <button type="button" class="button" id="wcfm_upload_texture"><?php _e( 'Upload/Select', 'wcfm' ); ?></button>
        </p>
        <p>
            <label><input type="checkbox" id="wcfm_all_angles" checked /> <?php _e( 'Generate all 6 angles', 'wcfm' ); ?></label>
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
                'nonce'    => wp_create_nonce( 'wcfm_generate' ),
                'rest_url' => rest_url( 'wc-fabric-mockups/v1/generate' ),
                'product_id' => get_the_ID(),
            ] );
        }
    }

    public static function settings_menu() {
        add_options_page( 'Fabric Mockups', 'Fabric Mockups', 'manage_options', 'wcfm-settings', [ __CLASS__, 'render_settings' ] );
    }

    public static function register_settings() {
        // API key stored with autoload = no
        if ( false === get_option( 'wcfm_api_key', false ) ) {
            add_option( 'wcfm_api_key', '', '', 'no' );
        }
        register_setting( 'wcfm-settings', 'wcfm_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'wcfm-settings', 'wcfm_master_image', [ 'sanitize_callback' => 'absint' ] );
        register_setting( 'wcfm-settings', 'wcfm_mask_image', [ 'sanitize_callback' => 'absint' ] );
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
                        <th scope="row"><label for="wcfm_master_image"><?php _e( 'Master chair image', 'wcfm' ); ?></label></th>
                        <td>
                            <?php $master = get_option( 'wcfm_master_image' ); ?>
                            <input type="number" name="wcfm_master_image" id="wcfm_master_image" value="<?php echo esc_attr( $master ); ?>" />
                            <p class="description"><?php _e( 'Attachment ID of the base chair image.', 'wcfm' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wcfm_mask_image"><?php _e( 'Mask image', 'wcfm' ); ?></label></th>
                        <td>
                            <?php $mask = get_option( 'wcfm_mask_image' ); ?>
                            <input type="number" name="wcfm_mask_image" id="wcfm_mask_image" value="<?php echo esc_attr( $mask ); ?>" />
                            <p class="description"><?php _e( 'Attachment ID of PNG mask with transparent upholstery.', 'wcfm' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
