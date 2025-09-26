/**
 * Minimal Authentication JavaScript
 * This version has no complex features that could cause errors
 */

(function($) {
    'use strict';

    // Simple initialization
    $(document).ready(function() {
        console.log('SCN Auth: Minimal version loaded');
        
        // Only run if we're on an auth page
        if ($('.scn-login-form, .scn-register-form').length === 0) {
            console.log('SCN Auth: No auth forms found, exiting');
            return;
        }
        
        console.log('SCN Auth: Initializing minimal features');
        
        // Simple password toggle
        $('.scn-toggle-password').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $icon = $button.find('.dashicons');
            var target = $button.data('target');
            var $input = $('#' + target);
            
            if ($input.length === 0) {
                console.log('SCN Auth: Target input not found:', target);
                return;
            }
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                console.log('SCN Auth: Password shown');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                console.log('SCN Auth: Password hidden');
            }
        });
        
        // Simple form submission with loading state
        $('.scn-login-form, .scn-register-form').on('submit', function() {
            console.log('SCN Auth: Form submitted');
            
            var $form = $(this);
            var $btn = $form.find('.scn-login-btn, .scn-register-btn');
            var $btnText = $btn.find('.scn-btn-text');
            var $btnLoading = $btn.find('.scn-btn-loading');
            
            if ($btnText.length && $btnLoading.length) {
                console.log('SCN Auth: Showing loading state');
                $btnText.hide();
                $btnLoading.show();
                $btn.prop('disabled', true);
            }
        });
        
        // Auto-focus first input
        var $firstInput = $('.scn-login-form input, .scn-register-form input').first();
        if ($firstInput.length) {
            console.log('SCN Auth: Auto-focusing first input');
            $firstInput.focus();
        }
        
        console.log('SCN Auth: Minimal initialization complete');
    });

})(jQuery);
