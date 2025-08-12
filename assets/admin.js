(function($){
    $(function(){
        var baseFrame, textureFrame;
        const apiRoot = CTS.rest.root.replace(/\/$/, '');

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

        function renderRows(items){
            var tbody = $('#cts-status-table tbody').empty();
            items.forEach(function(item){
                var row = $('<tr>');
                var imgCell = $('<td>');
                if (item.base_url){
                    imgCell.append($('<img>').attr('src', item.base_url));
                } else {
                    imgCell.text(item.id);
                }
                row.append(imgCell);
                row.append($('<td>').text(item.status));
                var resultCell = $('<td>');
                if (item.result_url){
                    resultCell.append($('<img>').attr('src', item.result_url));
                } else if (item.message){
                    resultCell.text(item.message);
                }
                row.append(resultCell);
                tbody.append(row);
            });
        }

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
                url: apiRoot + '/process',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', CTS.rest.nonce); },
                data: JSON.stringify(data),
                contentType: 'application/json',
                processData: false
            }).done(function(response){
                renderRows(response.items || []);
            }).fail(function(xhr, textStatus, errorThrown){
                var message = 'Error starting job';
                if (xhr.responseJSON && xhr.responseJSON.message){
                    message += ': ' + xhr.responseJSON.message;
                } else if (xhr.responseText){
                    message += ': ' + xhr.responseText;
                } else if (errorThrown){
                    message += ': ' + errorThrown;
                }
                alert(message);
                console.error('CTS process error', xhr);
            });
        });
    });
})(jQuery);
