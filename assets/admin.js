jQuery(function($){
    var frame;
    $('#wcfm_upload_texture').on('click', function(e){
        e.preventDefault();
        if ( frame ) {
            frame.open();
            return;
        }
        frame = wp.media({
            title: 'Select fabric texture',
            button: { text: 'Use this texture' },
            library: { type: 'image' }
        });
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $('#wcfm_texture_id').val( attachment.id );
            $('#wcfm_texture_status').show();
            var preview = '<img src="' + (attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" style="max-width:100%;height:auto;" />';
            $('#wcfm_texture_preview').html( preview );
        });
        frame.open();
    });

    $('#wcfm_generate').on('click', function(){
        var fabric = $('#wcfm_fabric_name').val();
        var tex = $('#wcfm_texture_id').val();
        var allAngles = $('#wcfm_all_angles').is(':checked');
        if ( ! fabric || ! tex ) {
            alert('Please provide fabric name and upload texture.');
            return;
        }
        var btn = $(this).prop('disabled', true);
        fetch(WCFM.rest_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': WCFM.nonce
            },
            body: JSON.stringify({
                product_id: WCFM.product_id,
                fabric_name: fabric,
                texture_id: tex,
                all_angles: allAngles ? 1 : 0
            })
        }).then(function(resp){
            if ( ! resp.ok ) {
                return resp.json().then(function(err){ throw err; });
            }
            return resp.json();
        }).then(function(){
            alert('Generation scheduled.');
            btn.prop('disabled', false);
        }).catch(function(){
            alert('Error scheduling generation');
            btn.prop('disabled', false);
        });
    });
});
