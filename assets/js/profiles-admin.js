/**
 * SCN Profiles Admin JavaScript
 */

(function($) {
    'use strict';

    let mediaUploader;
    let gallerySortable;

    $(document).ready(function() {
        initGalleryUploader();
        initPressKitUploader();
        initServicesManager();
        initGallerySortable();
        initVideoValidation();
    });

    function initGalleryUploader() {
        $('#scn-add-gallery-images').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: scnProfilesAdmin.strings.selectImages,
                button: {
                    text: scnProfilesAdmin.strings.selectImages
                },
                multiple: true,
                library: {
                    type: 'image'
                }
            });

            mediaUploader.on('select', function() {
                const attachments = mediaUploader.state().get('selection').toJSON();
                uploadGalleryImages(attachments);
            });

            mediaUploader.open();
        });
    }

    function uploadGalleryImages(attachments) {
        const postId = $('#post_ID').val();
        const formData = new FormData();
        formData.append('action', 'scn_upload_gallery_image');
        formData.append('post_id', postId);
        formData.append('nonce', scnProfilesAdmin.nonce);

        let uploadCount = 0;
        const totalAttachments = attachments.length;

        attachments.forEach(function(attachment) {
            fetch(attachment.url)
                .then(response => response.blob())
                .then(blob => {
                    const file = new File([blob], attachment.filename, { type: attachment.mime });
                    formData.append('file', file);

                    $.ajax({
                        url: scnProfilesAdmin.ajaxUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                addGalleryImage(response.data);
                            } else {
                                showError(response.data);
                            }
                        },
                        error: function() {
                            showError(scnProfilesAdmin.strings.uploadError);
                        },
                        complete: function() {
                            uploadCount++;
                            if (uploadCount === totalAttachments) {
                                updateGalleryInput();
                            }
                        }
                    });
                });
        });
    }

    function addGalleryImage(data) {
        const galleryItem = $(`
            <div class="scn-gallery-item" data-image-id="${data.attachment_id}">
                ${data.thumbnail}
                <button type="button" class="scn-remove-gallery-image">${scnProfilesAdmin.strings.removeImage}</button>
            </div>
        `);
        
        $('#scn-gallery-preview').append(galleryItem);
        initGallerySortable();
    }

    function initPressKitUploader() {
        $('#scn-add-press-kit-files').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: scnProfilesAdmin.strings.selectFiles,
                button: {
                    text: scnProfilesAdmin.strings.selectFiles
                },
                multiple: true,
                library: {
                    type: 'application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                }
            });

            mediaUploader.on('select', function() {
                const attachments = mediaUploader.state().get('selection').toJSON();
                uploadPressKitFiles(attachments);
            });

            mediaUploader.open();
        });
    }

    function uploadPressKitFiles(attachments) {
        const postId = $('#post_ID').val();
        const formData = new FormData();
        formData.append('action', 'scn_upload_press_kit_file');
        formData.append('post_id', postId);
        formData.append('nonce', scnProfilesAdmin.nonce);

        let uploadCount = 0;
        const totalAttachments = attachments.length;

        attachments.forEach(function(attachment) {
            fetch(attachment.url)
                .then(response => response.blob())
                .then(blob => {
                    const file = new File([blob], attachment.filename, { type: attachment.mime });
                    formData.append('file', file);

                    $.ajax({
                        url: scnProfilesAdmin.ajaxUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                addPressKitFile(response.data);
                            } else {
                                showError(response.data);
                            }
                        },
                        error: function() {
                            showError(scnProfilesAdmin.strings.uploadError);
                        },
                        complete: function() {
                            uploadCount++;
                            if (uploadCount === totalAttachments) {
                                updatePressKitInput();
                            }
                        }
                    });
                });
        });
    }

    function addPressKitFile(data) {
        const pressKitItem = $(`
            <div class="scn-press-kit-item" data-file-id="${data.attachment_id}">
                <a href="${data.url}" target="_blank">${data.filename}</a>
                <button type="button" class="scn-remove-press-kit-file">${scnProfilesAdmin.strings.removeFile}</button>
            </div>
        `);
        
        $('#scn-press-kit-list').append(pressKitItem);
    }

    function initServicesManager() {
        $('#scn-add-service').on('click', function(e) {
            e.preventDefault();
            addServiceField();
        });

        $(document).on('click', '.scn-remove-service', function(e) {
            e.preventDefault();
            $(this).closest('.scn-service-item').remove();
        });
    }

    function addServiceField() {
        const index = $('#scn-services-list .scn-service-item').length;
        const serviceItem = $(`
            <div class="scn-service-item">
                <input type="text" name="scn_services[${index}][name]" placeholder="${scnProfilesAdmin.strings.serviceName || 'Service name'}" class="regular-text" />
                <textarea name="scn_services[${index}][description]" placeholder="${scnProfilesAdmin.strings.serviceDescription || 'Short description (optional)'}" rows="2" class="large-text"></textarea>
                <button type="button" class="scn-remove-service">${scnProfilesAdmin.strings.removeService || 'Remove'}</button>
            </div>
        `);
        
        $('#scn-services-list').append(serviceItem);
    }

    function initGallerySortable() {
        if (gallerySortable) {
            gallerySortable.destroy();
        }
        
        gallerySortable = $('#scn-gallery-preview').sortable({
            items: '.scn-gallery-item',
            placeholder: 'scn-gallery-sortable-placeholder',
            update: function() {
                updateGalleryInput();
            }
        });
    }

    function updateGalleryInput() {
        const imageIds = [];
        $('#scn-gallery-preview .scn-gallery-item').each(function() {
            imageIds.push($(this).data('image-id'));
        });
        $('#scn_gallery_images').val(imageIds.join(','));
    }

    function updatePressKitInput() {
        const fileIds = [];
        $('#scn-press-kit-list .scn-press-kit-item').each(function() {
            fileIds.push($(this).data('file-id'));
        });
        $('#scn_press_kit_files').val(fileIds.join(','));
    }

    function initVideoValidation() {
        $('#scn_featured_video_url').on('blur', function() {
            const url = $(this).val();
            if (url) {
                validateVideoUrl(url);
            }
        });
    }

    function validateVideoUrl(url) {
        $.ajax({
            url: scnProfilesAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'scn_get_video_thumbnail',
                url: url,
                nonce: scnProfilesAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showVideoPreview(response.data.thumbnail);
                } else {
                    showError(response.data);
                    hideVideoPreview();
                }
            },
            error: function() {
                showError(scnProfilesAdmin.strings.uploadError);
                hideVideoPreview();
            }
        });
    }

    function showVideoPreview(thumbnailUrl) {
        let preview = $('#scn-video-preview');
        if (preview.length === 0) {
            preview = $('<div id="scn-video-preview"></div>');
            $('#scn_featured_video_url').closest('tr').after('<tr><td colspan="2"></td></tr>');
            $('#scn_featured_video_url').closest('tr').next().find('td').append(preview);
        }
        
        preview.html(`
            <h4>${scnProfilesAdmin.strings.videoPreview || 'Video Preview'}</h4>
            <img src="${thumbnailUrl}" alt="${scnProfilesAdmin.strings.videoThumbnail || 'Video thumbnail'}" style="max-width: 300px;" />
        `);
    }

    function hideVideoPreview() {
        $('#scn-video-preview').remove();
    }

    function showError(message) {
        // Remove existing errors
        $('.scn-error').remove();
        
        // Add new error
        $('<div class="scn-error">' + message + '</div>').insertAfter('#scn-gallery-uploader');
    }

    // Remove gallery image
    $(document).on('click', '.scn-remove-gallery-image', function(e) {
        e.preventDefault();
        const imageId = $(this).closest('.scn-gallery-item').data('image-id');
        const postId = $('#post_ID').val();
        
        $.ajax({
            url: scnProfilesAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'scn_remove_gallery_image',
                post_id: postId,
                image_id: imageId,
                nonce: scnProfilesAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $(e.target).closest('.scn-gallery-item').remove();
                    updateGalleryInput();
                }
            }
        });
    });

    // Remove press kit file
    $(document).on('click', '.scn-remove-press-kit-file', function(e) {
        e.preventDefault();
        const fileId = $(this).closest('.scn-press-kit-item').data('file-id');
        const postId = $('#post_ID').val();
        
        $.ajax({
            url: scnProfilesAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'scn_remove_press_kit_file',
                post_id: postId,
                file_id: fileId,
                nonce: scnProfilesAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $(e.target).closest('.scn-press-kit-item').remove();
                    updatePressKitInput();
                }
            }
        });
    });

})(jQuery);


