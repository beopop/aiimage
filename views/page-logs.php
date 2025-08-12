<div class="wrap">
    <h1><?php _e( 'Logs', 'chair-texture-swap' ); ?></h1>
    <?php
    global $wpdb;
    $table = $wpdb->prefix . 'chair_texture_swap_logs';
    $logs  = $wpdb->get_results( "SELECT * FROM $table ORDER BY id DESC LIMIT 50" );
    if ( $logs ) {
        echo '<table class="widefat"><thead><tr><th>ID</th><th>' . esc_html__( 'Date', 'chair-texture-swap' ) . '</th><th>' . esc_html__( 'Level', 'chair-texture-swap' ) . '</th><th>' . esc_html__( 'Message', 'chair-texture-swap' ) . '</th></tr></thead><tbody>';
        foreach ( $logs as $log ) {
            echo '<tr><td>' . esc_html( $log->id ) . '</td><td>' . esc_html( $log->datetime ) . '</td><td>' . esc_html( $log->level ) . '</td><td>' . esc_html( $log->message ) . '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>' . esc_html__( 'No logs found.', 'chair-texture-swap' ) . '</p>';
    }
    ?>
</div>
