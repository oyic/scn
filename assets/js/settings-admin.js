/**
 * Settings Admin JavaScript
 * 
 * @package SCN_Membership
 */

(function($) {
    'use strict';

    const SCN_Settings = {
        init: function() {
            console.log('SCN Settings initializing...');
            console.log('scnSettings object:', typeof scnSettings !== 'undefined' ? scnSettings : 'undefined');
            
            if (typeof scnSettings === 'undefined') {
                console.error('scnSettings object not found!');
                return;
            }
            
            this.bindEvents();
            this.initTabs();
            this.initMediaUpload();
        },

        bindEvents: function() {
            console.log('Binding events...');
            
            // Test image upload
            $('#test-image-upload').on('click', function(e) {
                console.log('Test image upload clicked!');
                e.preventDefault();
                SCN_Settings.testImageUpload.call(this);
            });
            console.log('Test image upload button bound:', $('#test-image-upload').length);

            // Clear cache
            $('#clear-cache').on('click', function(e) {
                console.log('Clear cache clicked!');
                e.preventDefault();
                SCN_Settings.clearCache.call(this);
            });
            console.log('Clear cache button bound:', $('#clear-cache').length);

            // Export settings
            $('#export-settings').on('click', this.exportSettings);
            console.log('Export settings button bound:', $('#export-settings').length);

            // Import settings
            $('#import-settings').on('click', this.showImportModal);
            $('#import-submit').on('click', this.importSettings);
            console.log('Import settings button bound:', $('#import-settings').length);

            // Modal close
            $('.scn-modal-close').on('click', this.closeModal);
            $(document).on('click', '.scn-modal', function(e) {
                if (e.target === this) {
                    SCN_Settings.closeModal();
                }
            });

            // Form validation
            $('#scn-settings-form').on('submit', this.validateForm);

            // Auto-save on change
            $('input, textarea, select').on('change', this.autoSave);
        },

        initTabs: function() {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                const target = $(this).attr('href');
                
                // Update active tab
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Show target content
                $('.scn-settings-tab-content').removeClass('active');
                $(target).addClass('active');
                
                // Update URL hash
                if (history.pushState) {
                    history.pushState(null, null, target);
                }
            });

            // Handle initial tab from URL hash
            const hash = window.location.hash;
            if (hash && $(hash).length) {
                $('.nav-tab[href="' + hash + '"]').trigger('click');
            }
        },

        initMediaUpload: function() {
            $('.select-media').on('click', function(e) {
                e.preventDefault();
                
                const field = $(this).data('field');
                const type = $(this).data('type') || 'image';
                
                const frame = wp.media({
                    title: scnSettings.strings.selectImage,
                    button: {
                        text: scnSettings.strings.selectImage
                    },
                    multiple: false,
                    library: {
                        type: type
                    }
                });

                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $('#' + field).val(attachment.id);
                    
                    const container = $('#' + field).siblings('.media-upload-container');
                    if (type === 'image') {
                        container.find('img').remove();
                        container.find('.no-media').remove();
                        container.prepend('<img src="' + attachment.sizes.medium.url + '" alt="' + attachment.alt + '" />');
                    } else {
                        container.find('.no-media').text(attachment.title);
                    }
                    
                    container.find('.remove-media').show();
                });

                frame.open();
            });

            $('.remove-media').on('click', function(e) {
                e.preventDefault();
                
                const field = $(this).data('field');
                $('#' + field).val('');
                
                const container = $('#' + field).siblings('.media-upload-container');
                container.find('img, .no-media').remove();
                container.append('<p class="no-media">' + scnSettings.strings.noMediaSelected + '</p>');
                $(this).hide();
            });
        },

        testImageUpload: function() {
            console.log('Test image upload clicked');
            const button = $(this);
            const originalText = button.text();
            
            button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: scnSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'scn_test_image_upload',
                    nonce: scnSettings.nonce
                },
                success: function(response) {
                    console.log('AJAX response:', response);
                    if (response.success) {
                        SCN_Settings.showNotice('success', response.data.message);
                    } else {
                        SCN_Settings.showNotice('error', response.data.message || scnSettings.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', xhr, status, error);
                    SCN_Settings.showNotice('error', scnSettings.strings.error);
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },

        clearCache: function() {
            console.log('Clear cache clicked');
            const button = $(this);
            const originalText = button.text();
            
            button.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: scnSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'scn_clear_cache',
                    nonce: scnSettings.nonce
                },
                success: function(response) {
                    console.log('Clear cache response:', response);
                    if (response.success) {
                        SCN_Settings.showNotice('success', response.data.message);
                    } else {
                        SCN_Settings.showNotice('error', response.data.message || scnSettings.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Clear cache error:', xhr, status, error);
                    SCN_Settings.showNotice('error', scnSettings.strings.error);
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },

        exportSettings: function() {
            window.location.href = scnSettings.ajaxUrl + '?action=scn_export_settings&nonce=' + scnSettings.nonce;
        },

        showImportModal: function() {
            $('#import-modal').show();
        },

        closeModal: function() {
            $('.scn-modal').hide();
            $('#import-form')[0].reset();
        },

        importSettings: function() {
            const form = $('#import-form')[0];
            const formData = new FormData(form);
            formData.append('action', 'scn_import_settings');
            formData.append('nonce', scnSettings.nonce);
            
            const button = $('#import-submit');
            const originalText = button.text();
            
            button.prop('disabled', true).text('Importing...');
            
            $.ajax({
                url: scnSettings.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        SCN_Settings.showNotice('success', response.data.message);
                        SCN_Settings.closeModal();
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        SCN_Settings.showNotice('error', response.data.message || scnSettings.strings.error);
                    }
                },
                error: function() {
                    SCN_Settings.showNotice('error', scnSettings.strings.error);
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },

        validateForm: function(e) {
            let isValid = true;
            const errors = [];
            
            // Validate email fields
            $('input[type="email"]').each(function() {
                const value = $(this).val();
                if (value && !SCN_Settings.isValidEmail(value)) {
                    isValid = false;
                    errors.push($(this).attr('name') + ' is not a valid email address.');
                }
            });
            
            // Validate number fields
            $('input[type="number"]').each(function() {
                const value = parseFloat($(this).val());
                const min = parseFloat($(this).attr('min'));
                const max = parseFloat($(this).attr('max'));
                
                if (isNaN(value) || (min && value < min) || (max && value > max)) {
                    isValid = false;
                    errors.push($(this).attr('name') + ' must be a valid number within the specified range.');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                SCN_Settings.showNotice('error', 'Please fix the following errors:\n' + errors.join('\n'));
            }
        },

        autoSave: function() {
            // Auto-save functionality could be implemented here
            // For now, we'll just add a visual indicator that changes have been made
            if (!$('#scn-settings-form').hasClass('changed')) {
                $('#scn-settings-form').addClass('changed');
                $('.scn-settings-footer .button-primary').text('Save Changes');
            }
        },

        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        showNotice: function(type, message) {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.scn-settings-page h1').after(notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        console.log('DOM ready, initializing SCN Settings...');
        console.log('jQuery version:', $.fn.jquery);
        console.log('Buttons found:', $('#test-image-upload, #clear-cache, #export-settings, #import-settings').length);
        SCN_Settings.init();
    });

    // Handle browser back/forward buttons
    $(window).on('popstate', function() {
        const hash = window.location.hash;
        if (hash && $(hash).length) {
            $('.nav-tab[href="' + hash + '"]').trigger('click');
        }
    });

})(jQuery);
