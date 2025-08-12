(function($){
    $(function(){
        var baseFrame, textureFrame;

        $('#cts-select-base').on('click', function(e){
            e.preventDefault();
            if (!baseFrame) {
                baseFrame = wp.media({ multiple: true });
                baseFrame.on('select', function(){
                    var ids = baseFrame.state().get('selection').map(function(att){ return att.id; });
                    $('#cts-process-form').data('base', ids);
                });
            }
            baseFrame.open();
        });

        $('#cts-select-texture').on('click', function(e){
            e.preventDefault();
            if (!textureFrame) {
                textureFrame = wp.media({ multiple: false });
                textureFrame.on('select', function(){
                    var id = textureFrame.state().get('selection').first().id;
                    $('#cts-process-form').data('texture', id);
                });
            }
            textureFrame.open();
        });

        $('#cts-process-form').on('submit', function(e){
            e.preventDefault();
            var data = {
                base_image_ids: $(this).data('base') || [],
                texture_image_id: $(this).data('texture') || 0,
                areas: $('input[name="areas[]"]:checked').map(function(){ return $(this).val(); }).get(),
                size: $('#cts-size').val(),
                prompt_overrides: $('#cts-prompt').val()
            };
            $.ajax({
                method: 'POST',
                url: CTS.rest.root + '/process',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', CTS.rest.nonce); },
                data: data
            }).done(function(response){
                console.log(response);
            });
        });
    });
})(jQuery);
