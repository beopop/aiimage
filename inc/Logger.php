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
    }

    public static function info( $message ) {
        self::log( 'info', $message );
    }

    public static function error( $message ) {
        self::log( 'error', $message );
    }
}
