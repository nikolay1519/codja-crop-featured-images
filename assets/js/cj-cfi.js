jQuery(document).ready(function($) {
    var cropsInstances = {};
    var cj_media = false;
    var cj_loader = $('.cj_cfi_loader');
    var viewCropDialog = $('#viewCropImageDialog');

    viewCropDialog.dialog({
        autoOpen: false,
        draggable: false,
        resizable: false,
        modal: true,
        width: 'auto',
        open: function( event, ui ) {
            viewCropDialog.dialog('option', 'position', { my: "center", at: "center", of: window });
        }
    });

    $('.cj_cfi_image_for_crop').each(function(index, element) {
        var image = $(element),
            size_box = image.closest('.cj_cfi_size_box'),
            size_id = size_box.data('sizeId'),
            size_width = size_box.data('sizeWidth'),
            size_height = size_box.data('sizeHeight');

        var opts = {
            viewMode: 2,
            zoomable: false,
            movable: false,
            dragMode: 'move',
            autoCropArea: 1
        };

        if (size_width != 9999 && size_height != 9999) {
            opts.aspectRatio = size_width / size_height;
        }

        cropsInstances[size_id] = new Cropper(element, opts);

        element.addEventListener('ready', function () {
            var size_box = $(this.cropper.cropper).closest('.cj_cfi_size_box');
            var currentCrop = size_box.find('.cj_cfi_size__crop.current img');

            if (currentCrop.length > 0) {
                var cropper_data = currentCrop.data('cropperData');

                cropsInstances[size_id].setCropBoxData(cropper_data.cropBoxData);
                cropsInstances[size_id].setCanvasData(cropper_data.canvasData);
            }
        });

        element.addEventListener('cropstart', function () {
            var size_box = $(this.cropper.cropper).closest('.cj_cfi_size_box');
            var currentCrop = size_box.find('.cj_cfi_size__crop.current');

            if (currentCrop.length > 0) {
                currentCrop.removeClass('current');
            }
        });
    });

    $('.cj_cfi_size__crops').on('click', '.cj_cfi_size__crop img', function() {
        var _this = $(this),
            _crops = _this.closest('.cj_cfi_size__crops');

        if (_this.parent().hasClass('current')) return false;

        var size_box = _this.closest('.cj_cfi_size_box'),
            size_id = size_box.data('sizeId'),
            crop_id = _this.data('cropId'),
            original_image = _this.data('originalImage'),
            cropper_data = _this.data('cropperData');

        if (cropsInstances[size_id].url != original_image) {
            cropsInstances[size_id].replace(original_image);
            size_box.data('attachmentId', _this.data('attachmentId'));
        }

        cropsInstances[size_id].setCropBoxData(cropper_data.cropBoxData);
        cropsInstances[size_id].setCanvasData(cropper_data.canvasData);

        _crops.find('.current').removeClass('current');
        _this.parent().addClass('current');
    });

    $('.cj_cfi_button__save').on('click', function() {
        var button = $(this),
            size_box = button.closest('.cj_cfi_size_box'),
            size_id = size_box.data('sizeId'),
            post_id = size_box.data('postId'),
            size_width = size_box.data('sizeWidth'),
            size_height = size_box.data('sizeHeight');

        showLoader();

        var currentCrop = size_box.find('.cj_cfi_size__crop.current img');
        if (currentCrop.length > 0) {
            var sendData = {};

            sendData.action = 'cfi_save_image';
            sendData.nonce = button.data('nonce');
            sendData.size_id = size_id;
            sendData.post_id = post_id;
            sendData.crop_id = currentCrop.data('cropId');
            sendData.attachment_id = size_box.data('attachmentId');

            $.post(ajaxurl, sendData, function(data) {
                hideLoader();
            }, 'json');
        } else {
            cropsInstances[size_id].getCroppedCanvas({
                width: size_width,
                height: size_height,
                minWidth: 50,
                minHeight: 50,
                fillColor: '#fff',
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            }).toBlob(function (blob) {
                var formData = new FormData(),
                    tmp = {};

                tmp.cropBoxData = cropsInstances[size_id].getCropBoxData();
                tmp.canvasData = cropsInstances[size_id].getCanvasData();

                formData.append('action', 'cfi_save_cropped_image');
                formData.append('nonce', button.data('nonce'));
                formData.append('size_id', size_id);
                formData.append('post_id', post_id);
                formData.append('attachment_id', size_box.data('attachmentId'));
                formData.append('croppedImage', blob);
                formData.append('croppedData', JSON.stringify(tmp));

                $.ajax(ajaxurl, {
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (data) {
                        if (data.status == 'success') {
                            size_box.find('.cj_cfi_size__crops').append(data.template);
                        } else {
                            console.log(data);
                        }

                        hideLoader();
                    }
                });
            });
        }
    });

    $('.cj_cfi_button__reset').on('click', function() {
        var button = $(this),
            size_box = button.closest('.cj_cfi_size_box'),
            size_id = size_box.data('sizeId'),
            post_id = size_box.data('postId');

        var currentCrop = size_box.find('.cj_cfi_size__crop.current img');

        var sendData = {};

        sendData.action = 'cfi_reset_image';
        sendData.nonce = button.data('nonce');
        sendData.size_id = size_id;
        sendData.post_id = post_id;

        $.post(ajaxurl, sendData, function(data) {
            if (data.status == 'success') {
                if (currentCrop.length > 0) {
                    currentCrop.parent().removeClass('current');
                }

                cropsInstances[size_id].reset();
                var default_image = size_box.data('defaultImage');

                if (cropsInstances[size_id].url != default_image) {
                    cropsInstances[size_id].replace(default_image);
                    size_box.data('attachmentId', size_box.data('defaultAttachmentId'));
                }
            } else {
                console.log(data);
            }

            hideLoader();
        }, 'json');
    });

    $('.cj_cfi_button__change_image').on('click', function() {
        var button = $(this),
            size_box = button.closest('.cj_cfi_size_box'),
            size_id = size_box.data('sizeId'),
            post_id = size_box.data('postId');

        if (cj_media) {
            cj_media['cj_size_id'] = size_id;
            cj_media.open();
            return;
        }

        cj_media = wp.media({
            title: 'Choose Image',
            library: {type: 'image'},
            button: {text: 'Select'},
            multiple: false
        });

        cj_media.on('select', function() {
            var attachment = cj_media.state().get('selection').first().toJSON();

            // Change data-attachment-id for size box
            var size_id = cj_media.cj_size_id,
                size_box = $('#cj_cfi_size_box_' + size_id);

            size_box.data('attachmentId', attachment.id);

            // Change the image in the cropper
            cropsInstances[size_id].replace(attachment.url);

            // Remove current flag from prev crop
            size_box.find('.cj_cfi_size__crop').removeClass('current');
        });

        //cj_media.on('open', function() {});

        cj_media['cj_size_id'] = size_id;
        cj_media.open();
    });

    $('.cj_cfi_button__view_crop').on('click', function() {
        var button = $(this),
            size_box = button.closest('.cj_cfi_size_box'),
            size_id = size_box.data('sizeId');

        var crop_sizes = button.closest('.cj_cfi_size_box__head').find('.cj_cfi_size_box__size_params').text();
        var crop_title = button.closest('.cj_cfi_size_box__head').find('.cj_cfi_size_box__size_name').text();

        var currentCrop = size_box.find('.cj_cfi_size__crop.current img');
        if (currentCrop.length > 0) {
            // If image is cropped, show image
            viewCropDialog.dialog('option', 'title', cj_cfi.view_crop + ' ' + crop_sizes);
            viewCropDialog.html('<img src="' + currentCrop.attr('src') + '" />');

            viewCropDialog.dialog('open');
        } else {
            // If images is not cropped, show blob
            var size_width = size_box.data('sizeWidth'),
                size_height = size_box.data('sizeHeight');

            cropsInstances[size_id].getCroppedCanvas({
                width: size_width,
                height: size_height,
                minWidth: 50,
                minHeight: 50,
                fillColor: '#fff',
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            }).toBlob(function (blob) {
                var cropUrl = window.URL.createObjectURL(new Blob([blob]));
                var img = new Image();
                img.src = cropUrl;
                img.onload = function() {
                    viewCropDialog.dialog('option', 'title', cj_cfi.view_crop + ' ' + crop_sizes);
                    viewCropDialog.html(img);

                    viewCropDialog.dialog('open');
                }

            });
        }
    });

    $('#cj_cfi_button__save_all').on('click', function() {
        var button = $(this),
            crop_boxes = $('.cj_cfi_size_box');

        if (crop_boxes.length > 0) {
            showLoader();

            var formData = new FormData();

            formData.append('action', 'cfi_save_all');
            formData.append('nonce', button.data('nonce'));
            formData.append('post_id', button.data('postId'));

            var cropCount = 0;
            var cropReady = 0;

            crop_boxes.each(function(index, element) {
                var size_box = $(element),
                    size_id = size_box.data('sizeId'),
                    post_id = size_box.data('postId'),
                    size_width = size_box.data('sizeWidth'),
                    size_height = size_box.data('sizeHeight');

                var currentCrop = size_box.find('.cj_cfi_size__crop.current img');
                if (currentCrop.length > 0) {
                    formData.append('save[' + size_id + '][crop_id]', currentCrop.data('cropId'));
                    formData.append('save[' + size_id + '][attachment_id]', size_box.data('attachmentId'));
                } else {
                    cropCount++;
                    cropsInstances[size_id].getCroppedCanvas({
                        width: size_width,
                        height: size_height,
                        minWidth: 50,
                        minHeight: 50,
                        fillColor: '#fff',
                        imageSmoothingEnabled: true,
                        imageSmoothingQuality: 'high'
                    }).toBlob(function (blob) {
                        var tmp = {};

                        tmp.cropBoxData = cropsInstances[size_id].getCropBoxData();
                        tmp.canvasData = cropsInstances[size_id].getCanvasData();

                        formData.append('crop[' + size_id + '][attachment_id]', size_box.data('attachmentId'));
                        formData.append('croppedImage_' + size_id, blob);
                        formData.append('crop[' + size_id + '][croppedData]', JSON.stringify(tmp));

                        cropReady++;
                    });
                }
            });

            var waitCrop = setInterval(function() {
                if (cropCount == cropReady) {
                    clearInterval(waitCrop);
                    $.ajax(ajaxurl, {
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function (data) {
                            if (data.status == 'success') {
                                console.log(data);

                                // Check if exist data.crop
                                if (data.crop) {
                                    // Look each property
                                    for (var size_id in data.crop) {
                                        if (data.crop[size_id].status == 'success') {
                                            $('#cj_cfi_size_box_' + size_id).find('.cj_cfi_size__crops').append(data.crop[size_id].template);
                                        }
                                    }
                                }
                            } else {
                                console.log(data);
                            }

                            hideLoader();
                        }
                    });
                } else {
                    console.log('wait: ' + cropCount + ' - ' + cropReady);
                }
            }, 1000);
        }

    });

    function showLoader() {
        cj_loader.stop().fadeIn(200);
    }

    function hideLoader() {
        cj_loader.stop().fadeOut(200);
    }
});