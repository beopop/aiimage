<div class="wrap">
    <h1><?php _e( 'Chair Texture Swap Settings', 'chair-texture-swap' ); ?></h1>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'cts_settings' );
        do_settings_sections( 'cts_settings' );
        submit_button();
        ?>
    </form>
</div>
