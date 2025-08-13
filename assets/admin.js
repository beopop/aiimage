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

            var baseIds = $(this).data('base') || [];
            var textureId = $(this).data('texture') || 0;
            var areas = $('input[name="areas[]"]:checked').map(function(){ return $(this).val(); }).get();
            var size = $('#cts-size').val();
            var prompt = $('#cts-prompt').val();

            var items = baseIds.map(function(id){
                return { id: id, status: 'queued' };
            });
            renderRows(items);

            function processNext(index){
                if (index >= baseIds.length){
                    return;
                }

                items[index].status = 'processing';
                renderRows(items);

                var data = {
                    base_image_ids: [ baseIds[index] ],
                    texture_image_id: textureId,
                    areas: areas,
                    size: size,
                    prompt_overrides: prompt
                };

                function pollJob(jobId, index){
                    $.ajax({
                        type: 'GET',
                        url: apiRoot + '/status/' + jobId,
                        beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', CTS.rest.nonce); }
                    }).done(function(resp){
                        if (resp.status === 'done'){
                            if (resp.items && resp.items[0]){
                                items[index] = resp.items[0];
                            } else {
                                items[index].status = 'error';
                                items[index].message = 'No response';
                            }
                            renderRows(items);
                            processNext(index + 1);
                        } else {
                            setTimeout(function(){ pollJob(jobId, index); }, 2000);
                        }
                    }).fail(function(xhr, textStatus, errorThrown){
                        items[index].status = 'error';
                        if (xhr.responseJSON && xhr.responseJSON.message){
                            items[index].message = xhr.responseJSON.message;
                        } else if (xhr.responseText){
                            items[index].message = xhr.responseText;
                        } else if (errorThrown){
                            items[index].message = errorThrown;
                        }
                        renderRows(items);
                        processNext(index + 1);
                    });
                }

                $.ajax({
                    type: 'POST',
                    url: apiRoot + '/process',
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', CTS.rest.nonce); },
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    processData: false
                }).done(function(response){
                    if (response.job_id){
                        pollJob(response.job_id, index);
                    } else if (response.items && response.items[0]){
                        items[index] = response.items[0];
                        renderRows(items);
                        processNext(index + 1);
                    } else {
                        items[index].status = 'error';
                        items[index].message = 'No response';
                        renderRows(items);
                        processNext(index + 1);
                    }
                }).fail(function(xhr, textStatus, errorThrown){
                    items[index].status = 'error';
                    if (xhr.responseJSON && xhr.responseJSON.message){
                        items[index].message = xhr.responseJSON.message;
                    } else if (xhr.responseText){
                        items[index].message = xhr.responseText;
                    } else if (errorThrown){
                        items[index].message = errorThrown;
                    }
                    renderRows(items);
                    processNext(index + 1);
                });
            }

            processNext(0);
        });
    });
})(jQuery);
