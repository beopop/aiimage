(function($){
    $(function(){
        var baseFrame, textureFrame;

        $('#cts-select-base').on('click', function(e){
            e.preventDefault();
            if (!baseFrame) {
                baseFrame = wp.media({ multiple: true });
                baseFrame.on('select', function(){
                    var selection = baseFrame.state().get('selection');
                    var ids = selection.map(function(att){ return att.id; });
                    $('#cts-process-form').data('base', ids);

                    var container = $('#cts-base-images').empty();
                    selection.each(function(att){
                        var url = att.get('url');
                        var sizes = att.get('sizes');
                        if (sizes && sizes.thumbnail) {
                            url = sizes.thumbnail.url;
                        }
                        $('<img>').attr('src', url).appendTo(container);
                    });
                });
            }
            baseFrame.open();
        });

        $('#cts-select-texture').on('click', function(e){
            e.preventDefault();
            if (!textureFrame) {
                textureFrame = wp.media({ multiple: false });
                textureFrame.on('select', function(){
                    var attachment = textureFrame.state().get('selection').first();
                    var id = attachment.id;
                    $('#cts-process-form').data('texture', id);

                    var url = attachment.get('url');
                    var sizes = attachment.get('sizes');
                    if (sizes && sizes.thumbnail) {
                        url = sizes.thumbnail.url;
                    }
                    $('#cts-texture-image').empty().append($('<img>').attr('src', url));
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
