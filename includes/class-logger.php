<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CTS_Logger {
    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'chair_texture_swap_logs';
    }

    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'chair_texture_swap_logs';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            datetime datetime NOT NULL,
            level varchar(10) NOT NULL,
            context varchar(255) DEFAULT '' NOT NULL,
            message text NOT NULL,
            payload_json longtext,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    private function log( $level, $message, $context = array() ) {
        global $wpdb;
        $wpdb->insert(
            $this->table,
            array(
                'datetime'     => current_time( 'mysql' ),
                'level'        => strtoupper( $level ),
                'context'      => isset( $context['context'] ) ? sanitize_text_field( $context['context'] ) : '',
                'message'      => sanitize_text_field( $message ),
                'payload_json' => wp_json_encode( $context ),
            )
        );
    }

    public function info( $message, $context = array() ) {
        $this->log( 'INFO', $message, $context );
    }

    public function warn( $message, $context = array() ) {
        $this->log( 'WARN', $message, $context );
    }

    public function error( $message, $context = array() ) {
        $this->log( 'ERROR', $message, $context );
    }
}

?>
