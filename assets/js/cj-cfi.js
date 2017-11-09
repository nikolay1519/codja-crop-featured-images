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
        width: 'auto'
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

        };

        if (size_width != 9999 && size_height != 9999) {
            opts.aspectRatio = size_width / size_height;
        }

        cropsInstances[size_id] = new Cropper(element, opts);

        element.addEventListener('ready', function () {
            var size_box = $(this.cropper.cropper).closest('.cj_cfi_size_box');
            var currentCrop = size_box.find('.cj_cfi_size__crop.current img');

            if (currentCrop.length > 0) {
                var crop_id = currentCrop.data('cropId');

                if (crop_id != 'default') {
                    var original_image = currentCrop.data('originalImage');
                    var cropped_image = currentCrop.data('croppedImage');
                    var cropper_data = currentCrop.data('cropperData');

                    cropsInstances[size_id].setCropBoxData(cropper_data.cropBoxData);
                    cropsInstances[size_id].setCanvasData(cropper_data.canvasData);
                }
            }

        });

        element.addEventListener('cropstart', function () {
            var size_box = $(this.cropper.cropper).closest('.cj_cfi_size_box');

            var currentCrop = size_box.find('.cj_cfi_size__crop.current');

            if (currentCrop.length > 0) {
                currentCrop.addClass('prev');
                currentCrop.removeClass('current');
            }
        });
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
                console.log(data);
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
                    },
                    error: function () {
                        console.log('Upload error');
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
        if (currentCrop.length > 0) {
            var crop_id = currentCrop.data('cropId');

            if (crop_id != 'default') {
                showLoader();

                var sendData = {};

                sendData.action = 'cfi_reset_image';
                sendData.nonce = button.data('nonce');
                sendData.size_id = size_id;
                sendData.post_id = post_id;

                $.post(ajaxurl, sendData, function(data) {
                    if (data.status == 'success') {
                        currentCrop.parent().removeClass('current');

                        var defaultCrop = size_box.find('.cj_cfi_size__crop.default img');
                        if (defaultCrop.length > 0) {
                            defaultCrop.parent().addClass('current');
                            size_box.data('attachmentId', defaultCrop.data('attachmentId'));
                        }

                        cropsInstances[size_id].reset();
                    } else {
                        console.log(data);
                    }

                    hideLoader();
                }, 'json');
            }
        }
    });

    $('.cj_cfi_size__crops').on('click', '.cj_cfi_size__crop img', function() {
        var _this = $(this),
            _crops = _this.closest('.cj_cfi_size__crops');

        if (_this.parent().hasClass('current')) return false;

        var size_box = _this.closest('.cj_cfi_size_box');
        var size_id = size_box.data('sizeId');
        var crop_id = _this.data('cropId');

        var original_image = _this.data('originalImage');

        if (crop_id != 'default') {
            var cropped_image = _this.data('croppedImage');
            var cropper_data = _this.data('cropperData');

            if (cropsInstances[size_id].url != original_image) {
                cropsInstances[size_id].replace(original_image);
                size_box.data('attachmentId', _this.data('attachmentId'));
            }

            cropsInstances[size_id].setCropBoxData(cropper_data.cropBoxData);
            cropsInstances[size_id].setCanvasData(cropper_data.canvasData);

        } else {
            if (cropsInstances[size_id].url != original_image) {
                cropsInstances[size_id].replace(original_image);
                size_box.data('attachmentId', _this.data('attachmentId'));
            }

            cropsInstances[size_id].reset();
        }

        _crops.find('.current').removeClass('current');
        _this.parent().addClass('current');
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
            viewCropDialog.dialog('option', 'title', cj_cfi.view_crop + ' (' + crop_sizes + ')');
            viewCropDialog.html('<img src="' + currentCrop.attr('src') + '" />');
            viewCropDialog.dialog('option', 'position', { my: "center", at: "center", of: window });

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
                    viewCropDialog.dialog('option', 'title', cj_cfi.view_crop + ' (' + crop_sizes + ')');
                    viewCropDialog.html(img);
                    viewCropDialog.dialog('option', 'position', { my: "center", at: "center", of: window });

                    viewCropDialog.dialog('open');
                }

            });
        }
    });

    function showLoader() {
        cj_loader.stop().fadeIn(200);
    }

    function hideLoader() {
        cj_loader.stop().fadeOut(200);
    }
});