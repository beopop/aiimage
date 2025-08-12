<?php
namespace WC_Fabric_Mockups;

class Logger {
    protected static function enabled() {
        return (bool) get_option( 'wcfm_enable_logging' );
    }

    protected static function log( $level, $message ) {
        if ( ! self::enabled() ) {
            return;
        }
        wc_get_logger()->log( $level, $message, [ 'source' => 'wc-fabric-mockups' ] );

        // Store log entry for display in admin debug page.
        if ( false === get_option( 'wcfm_debug_log', false ) ) {
            add_option( 'wcfm_debug_log', [], '', 'no' );
        }
        $logs   = get_option( 'wcfm_debug_log', [] );
        $logs[] = [
            'time'    => current_time( 'mysql' ),
            'level'   => $level,
            'message' => $message,
        ];
        // Keep the log size reasonable.
        if ( count( $logs ) > 1000 ) {
            $logs = array_slice( $logs, -1000 );
        }
        update_option( 'wcfm_debug_log', $logs, false );
    }

    public static function info( $message ) {
        self::log( 'info', $message );
    }

    public static function error( $message ) {
        self::log( 'error', $message );
    }

    /**
     * Retrieve stored debug log entries.
     *
     * @return array
     */
    public static function get_logs() {
        return get_option( 'wcfm_debug_log', [] );
    }

    /**
     * Clear stored debug log entries.
     */
    public static function clear() {
        delete_option( 'wcfm_debug_log' );
    }
}
