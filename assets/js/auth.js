/**
 * SCN Authentication JavaScript
 */

(function($) {
    'use strict';

    class SCNAuth {
        constructor() {
            this.init();
        }

        init() {
            this.setupEventListeners();
            this.handleFormSubmissions();
            this.setupPasswordToggle();
            this.setupFormValidation();
        }

        setupEventListeners() {
            // Auto-focus first input
            $('.scn-login-form input, .scn-register-form input').first().focus();
            
            // Handle form submissions
            $('.scn-login-form, .scn-register-form').on('submit', (e) => {
                this.handleFormSubmit(e);
            });
            
            // Handle logout links
            $(document).on('click', '.scn-logout-link', (e) => {
                e.preventDefault();
                this.handleLogout();
            });
            
            // Handle navigation links
            $(document).on('click', '.scn-auth-nav-link', (e) => {
                this.handleNavigation(e);
            });
        }

        handleFormSubmissions() {
            // Login form
            $('.scn-login-form').on('submit', function() {
                const $form = $(this);
                const $btn = $form.find('.scn-login-btn');
                const $btnText = $btn.find('.scn-btn-text');
                const $btnLoading = $btn.find('.scn-btn-loading');
                
                // Show loading state
                $btnText.hide();
                $btnLoading.show();
                $btn.prop('disabled', true);
                
                // Remove any existing error messages
                $('.scn-login-error').remove();
            });
            
            // Registration form
            $('.scn-register-form').on('submit', function() {
                const $form = $(this);
                const $btn = $form.find('.scn-register-btn');
                const $btnText = $btn.find('.scn-btn-text');
                const $btnLoading = $btn.find('.scn-btn-loading');
                
                // Show loading state
                $btnText.hide();
                $btnLoading.show();
                $btn.prop('disabled', true);
                
                // Remove any existing error messages
                $('.scn-register-errors').remove();
            });
        }

        setupPasswordToggle() {
            // Toggle password visibility
            $('.scn-toggle-password').on('click', function() {
                const target = $(this).data('target');
                const $input = $('#' + target);
                const $icon = $(this).find('.dashicons');
                
                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                } else {
                    $input.attr('type', 'password');
                    $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                }
            });
        }

        setupFormValidation() {
            // Real-time password confirmation validation
            $('#confirm_password').on('input', function() {
                const password = $('#password').val() || '';
                const confirm = $(this).val() || '';
                
                if (password !== confirm && confirm.length > 0) {
                    $(this).css('border-color', '#e74c3c');
                    this.showPasswordMismatch();
                } else {
                    $(this).css('border-color', '#ecf0f1');
                    this.hidePasswordMismatch();
                }
            }.bind(this));
            
            // Username availability check
            $('#username').on('blur', function() {
                const username = $(this).val() || '';
                if (username.length > 3) {
                    this.checkUsernameAvailability(username);
                }
            }.bind(this));
            
            // Email format validation
            $('#email').on('blur', function() {
                const email = $(this).val() || '';
                if (email && !this.isValidEmail(email)) {
                    this.showEmailError();
                } else {
                    this.hideEmailError();
                }
            }.bind(this));
        }

        handleFormSubmit(e) {
            const $form = $(e.target);
            const formType = $form.hasClass('scn-login-form') ? 'login' : 'register';
            
            // Basic validation
            if (!this.validateForm($form, formType)) {
                e.preventDefault();
                return false;
            }
            
            // Additional AJAX validation for registration
            if (formType === 'register') {
                e.preventDefault();
                this.submitRegistrationForm($form);
                return false;
            }
        }

        validateForm($form, type) {
            let isValid = true;
            
            if (type === 'login') {
                const username = ($form.find('#username').val() || '').trim();
                const password = $form.find('#password').val() || '';
                
                if (!username) {
                    this.showFieldError($form.find('#username'), 'Username is required');
                    isValid = false;
                }
                
                if (!password) {
                    this.showFieldError($form.find('#password'), 'Password is required');
                    isValid = false;
                }
            } else if (type === 'register') {
                const requiredFields = ['first_name', 'last_name', 'username', 'email', 'password', 'confirm_password'];
                
                requiredFields.forEach(field => {
                    const $field = $form.find(`#${field}`);
                    const value = ($field.val() || '').trim();
                    if (!value) {
                        this.showFieldError($field, `${this.getFieldLabel(field)} is required`);
                        isValid = false;
                    }
                });
                
                // Check password match
                const password = $form.find('#password').val() || '';
                const confirmPassword = $form.find('#confirm_password').val() || '';
                if (password !== confirmPassword) {
                    this.showFieldError($form.find('#confirm_password'), 'Passwords do not match');
                    isValid = false;
                }
                
                // Check terms agreement
                if (!$form.find('input[name="terms"]').is(':checked')) {
                    this.showGeneralError($form, 'You must agree to the terms and conditions');
                    isValid = false;
                }
            }
            
            return isValid;
        }

        submitRegistrationForm($form) {
            const formData = new FormData($form[0]);
            
            $.ajax({
                url: scnAuth.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showSuccessMessage('Registration successful! Redirecting...');
                        setTimeout(() => {
                            window.location.href = scnAuth.urls.dashboard;
                        }, 1500);
                    } else {
                        this.showRegistrationErrors(response.data);
                    }
                },
                error: () => {
                    this.showGeneralError($form, 'Registration failed. Please try again.');
                },
                complete: () => {
                    this.resetFormButtons($form);
                }
            });
        }

        checkUsernameAvailability(username) {
            $.ajax({
                url: scnAuth.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'scn_check_username_availability',
                    username: username,
                    nonce: scnAuth.nonce
                },
                success: (response) => {
                    if (response.success && response.data.available === false) {
                        this.showFieldError($('#username'), 'Username is already taken');
                    } else {
                        this.hideFieldError($('#username'));
                    }
                }
            });
        }

        handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = scnAuth.urls.logout;
            }
        }

        handleNavigation(e) {
            e.preventDefault();
            const href = $(e.target).attr('href');
            if (href) {
                window.location.href = href;
            }
        }

        // Utility methods
        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        getFieldLabel(field) {
            const labels = {
                'first_name': 'First Name',
                'last_name': 'Last Name',
                'username': 'Username',
                'email': 'Email',
                'password': 'Password',
                'confirm_password': 'Confirm Password'
            };
            return labels[field] || field;
        }

        showFieldError($field, message) {
            this.hideFieldError($field);
            $field.after(`<div class="scn-field-error">${message}</div>`);
            $field.css('border-color', '#e74c3c');
        }

        hideFieldError($field) {
            $field.siblings('.scn-field-error').remove();
            $field.css('border-color', '#ecf0f1');
        }

        showGeneralError($form, message) {
            const errorHtml = `
                <div class="scn-general-error">
                    <span class="dashicons dashicons-warning"></span>
                    ${message}
                </div>
            `;
            $form.prepend(errorHtml);
        }

        showSuccessMessage(message) {
            const successHtml = `
                <div class="scn-success-message">
                    <span class="dashicons dashicons-yes-alt"></span>
                    ${message}
                </div>
            `;
            $('.scn-register-header').after(successHtml);
        }

        showRegistrationErrors(errors) {
            let errorHtml = '<div class="scn-register-errors">';
            if (Array.isArray(errors)) {
                errors.forEach(error => {
                    errorHtml += `
                        <div class="scn-error-item">
                            <span class="dashicons dashicons-warning"></span>
                            ${error}
                        </div>
                    `;
                });
            } else {
                errorHtml += `
                    <div class="scn-error-item">
                        <span class="dashicons dashicons-warning"></span>
                        ${errors}
                    </div>
                `;
            }
            errorHtml += '</div>';
            
            $('.scn-register-form').prepend(errorHtml);
        }

        hidePasswordMismatch() {
            $('.scn-password-mismatch').remove();
        }

        showPasswordMismatch() {
            if (!$('.scn-password-mismatch').length) {
                $('#confirm_password').after('<div class="scn-password-mismatch">Passwords do not match</div>');
            }
        }

        showEmailError() {
            if (!$('.scn-email-error').length) {
                $('#email').after('<div class="scn-email-error">Please enter a valid email address</div>');
            }
        }

        hideEmailError() {
            $('.scn-email-error').remove();
        }

        resetFormButtons($form) {
            const $btn = $form.find('.scn-login-btn, .scn-register-btn');
            const $btnText = $btn.find('.scn-btn-text');
            const $btnLoading = $btn.find('.scn-btn-loading');
            
            $btnText.show();
            $btnLoading.hide();
            $btn.prop('disabled', false);
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.scn-login-form, .scn-register-form').length) {
            new SCNAuth();
        }
    });

    // Add some utility functions to global scope
    window.SCNAuthUtils = {
        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        formatUsername: function(username) {
            return username.toLowerCase().replace(/[^a-z0-9]/g, '');
        },
        
        generatePassword: function() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
            let password = '';
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return password;
        }
    };

})(jQuery);
