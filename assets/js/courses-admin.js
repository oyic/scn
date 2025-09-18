jQuery(document).ready(function($) {
    'use strict';
    
    // Image uploader functionality
    let courseImageUploader;
    
    $('#scn_course_upload_image').on('click', function(e) {
        e.preventDefault();
        
        if (courseImageUploader) {
            courseImageUploader.open();
            return;
        }
        
        courseImageUploader = wp.media({
            title: scnCoursesAdmin.selectImageTitle,
            button: {
                text: scnCoursesAdmin.useImageText
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        courseImageUploader.on('select', function() {
            const attachment = courseImageUploader.state().get('selection').first().toJSON();
            $('#scn_course_image_id').val(attachment.id);
            $('#scn_course_image_preview').html('<img src="' + attachment.sizes.medium.url + '" alt="' + attachment.alt + '" />');
            $('#scn_course_remove_image').show();
        });
        
        courseImageUploader.open();
    });
    
    $('#scn_course_remove_image').on('click', function(e) {
        e.preventDefault();
        $('#scn_course_image_id').val('');
        $('#scn_course_image_preview').empty();
        $(this).hide();
    });
    
    // CE Hours toggle functionality
    $('#scn_course_ce_enabled').on('change', function() {
        const ceHoursField = $('#scn_course_ce_hours');
        if (this.checked) {
            ceHoursField.prop('disabled', false);
            if (!ceHoursField.val()) {
                ceHoursField.val('0.5');
            }
        } else {
            ceHoursField.prop('disabled', true);
            ceHoursField.val('');
        }
    });
    
    // Initialize CE Hours field state
    if (!$('#scn_course_ce_enabled').is(':checked')) {
        $('#scn_course_ce_hours').prop('disabled', true);
    }
    
    // Learning outcomes management
    $('#scn-add-outcome').on('click', function(e) {
        e.preventDefault();
        
        const container = $('#scn-outcomes-container');
        const currentIndex = container.find('.scn-outcome-item').length;
        
        const newItem = $('<div class="scn-outcome-item">' +
            '<input type="text" name="scn_course_outcomes[' + currentIndex + ']" value="" class="regular-text" placeholder="' + scnCoursesAdmin.outcomePlaceholder + '" />' +
            '<button type="button" class="scn-remove-outcome button">' + scnCoursesAdmin.removeText + '</button>' +
            '</div>');
        
        container.append(newItem);
        updateRemoveButtons();
    });
    
    $('#scn-outcomes-container').on('click', '.scn-remove-outcome', function(e) {
        e.preventDefault();
        $(this).closest('.scn-outcome-item').remove();
        updateRemoveButtons();
    });
    
    function updateRemoveButtons() {
        const items = $('#scn-outcomes-container .scn-outcome-item');
        items.each(function(index) {
            const removeBtn = $(this).find('.scn-remove-outcome');
            if (items.length > 1) {
                removeBtn.show();
            } else {
                removeBtn.hide();
            }
        });
    }
    
    // Initialize remove buttons state
    updateRemoveButtons();
    
    // Form validation
    $('form#post').on('submit', function(e) {
        const errors = [];
        
        // Validate CE hours when enabled
        if ($('#scn_course_ce_enabled').is(':checked')) {
            const ceHours = parseFloat($('#scn_course_ce_hours').val());
            if (isNaN(ceHours) || ceHours < 0.5) {
                errors.push(scnCoursesAdmin.ceHoursError);
            }
        }
        
        // Validate outcomes
        const outcomes = [];
        $('#scn-outcomes-container input[type="text"]').each(function() {
            const value = $(this).val().trim();
            if (value) {
                outcomes.push(value);
            }
        });
        
        if (outcomes.length === 0) {
            errors.push(scnCoursesAdmin.outcomesError);
        }
        
        // Validate on-demand link if provided
        const ondemandLink = $('#scn_ondemand_link').val().trim();
        if (ondemandLink && !isValidUrl(ondemandLink)) {
            errors.push(scnCoursesAdmin.linkError);
        }
        
        if (errors.length > 0) {
            e.preventDefault();
            alert(errors.join('\n'));
            return false;
        }
    });
    
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    // Auto-save draft functionality
    let autoSaveTimeout;
    $('input, textarea, select').on('change', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            if (typeof wp !== 'undefined' && wp.autosave) {
                wp.autosave.server.triggerSave();
            }
        }, 2000);
    });
});





