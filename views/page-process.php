<div class="wrap">
    <h1><?php _e( 'Chair Texture Swap', 'chair-texture-swap' ); ?></h1>
    <form id="cts-process-form">
        <div class="cts-uploader">
            <h2><?php _e( 'Base Images', 'chair-texture-swap' ); ?></h2>
            <div id="cts-base-images" class="cts-dropzone"></div>
            <button type="button" class="button" id="cts-select-base"><?php _e( 'Select Images', 'chair-texture-swap' ); ?></button>
        </div>
        <div class="cts-uploader">
            <h2><?php _e( 'Texture', 'chair-texture-swap' ); ?></h2>
            <div id="cts-texture-image" class="cts-dropzone"></div>
            <button type="button" class="button" id="cts-select-texture"><?php _e( 'Select Texture', 'chair-texture-swap' ); ?></button>
        </div>
        <h2><?php _e( 'Options', 'chair-texture-swap' ); ?></h2>
        <fieldset>
            <legend><?php _e( 'Replace fabric on:', 'chair-texture-swap' ); ?></legend>
            <label><input type="checkbox" name="areas[]" value="seat" checked> <?php _e( 'Seat', 'chair-texture-swap' ); ?></label><br>
            <label><input type="checkbox" name="areas[]" value="back" checked> <?php _e( 'Back', 'chair-texture-swap' ); ?></label><br>
            <label><input type="checkbox" name="areas[]" value="arms" checked> <?php _e( 'Armrests', 'chair-texture-swap' ); ?></label>
        </fieldset>
        <p>
            <label for="cts-size"><?php _e( 'Output Size', 'chair-texture-swap' ); ?></label>
            <select id="cts-size" name="size">
                <option value="1024">1024</option>
                <option value="768">768</option>
                <option value="512">512</option>
            </select>
        </p>
        <p>
            <label for="cts-prompt"><?php _e( 'AI Prompt', 'chair-texture-swap' ); ?></label><br>
            <textarea id="cts-prompt" name="prompt_overrides" rows="4" cols="50"><?php echo esc_textarea( __( "Replace only the chair’s fabric upholstery with the provided texture reference.\nKeep frame, legs, lighting, shadows, composition and perspective unchanged.\nPreserve seams, stitch lines, realistic fabric behavior, and scale of the pattern.\nNo other edits besides the fabric swap.", 'chair-texture-swap' ) ); ?></textarea>
        </p>
        <p>
            <button type="submit" class="button button-primary"><?php _e( 'Start Processing', 'chair-texture-swap' ); ?></button>
        </p>
    </form>
    <table class="widefat" id="cts-status-table">
        <thead>
            <tr>
                <th><?php _e( 'Image', 'chair-texture-swap' ); ?></th>
                <th><?php _e( 'Status', 'chair-texture-swap' ); ?></th>
                <th><?php _e( 'Result', 'chair-texture-swap' ); ?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
