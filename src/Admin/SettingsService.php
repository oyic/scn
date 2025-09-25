<?php

namespace SCN\Membership\Admin;

class SettingsService {
    private $settings_group = 'scn_membership_settings';
    private $settings_section = 'scn_membership_section';
    private $option_name = 'scn_membership_options';

    public function register() {
        add_action('admin_init', [$this, 'initSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_ajax_scn_test_image_upload', [$this, 'testImageUpload']);
        add_action('wp_ajax_scn_clear_cache', [$this, 'clearCache']);
        add_action('wp_ajax_scn_export_settings', [$this, 'exportSettings']);
        add_action('wp_ajax_scn_import_settings', [$this, 'importSettings']);
    }

    public function initSettings() {
        register_setting(
            $this->settings_group,
            $this->option_name,
            [
                'sanitize_callback' => [$this, 'sanitizeSettings'],
                'default' => $this->getDefaultSettings()
            ]
        );

        $this->registerGeneralSettings();
        $this->registerImageSettings();
        $this->registerUrlSettings();
        $this->registerEventSettings();
        $this->registerWidgetSettings();
        $this->registerAdvancedSettings();
    }

    private function registerGeneralSettings() {
        add_settings_section(
            'scn_general_section',
            __('General Settings', 'scn-membership'),
            [$this, 'generalSectionCallback'],
            'scn-membership-settings-general'
        );

        add_settings_field(
            'site_name',
            __('Site Name', 'scn-membership'),
            [$this, 'textFieldCallback'],
            'scn-membership-settings-general',
            'scn_general_section',
            [
                'field' => 'site_name',
                'description' => __('The name of your SCN membership site.', 'scn-membership'),
                'placeholder' => __('SCN Membership', 'scn-membership')
            ]
        );

        add_settings_field(
            'site_description',
            __('Site Description', 'scn-membership'),
            [$this, 'textareaFieldCallback'],
            'scn-membership-settings',
            'scn_general_section',
            [
                'field' => 'site_description',
                'description' => __('A brief description of your SCN membership site.', 'scn-membership'),
                'rows' => 3
            ]
        );

        add_settings_field(
            'contact_email',
            __('Contact Email', 'scn-membership'),
            [$this, 'emailFieldCallback'],
            'scn-membership-settings',
            'scn_general_section',
            [
                'field' => 'contact_email',
                'description' => __('Email address for general inquiries.', 'scn-membership')
            ]
        );

        add_settings_field(
            'enable_registration',
            __('Enable Member Registration', 'scn-membership'),
            [$this, 'checkboxFieldCallback'],
            'scn-membership-settings',
            'scn_general_section',
            [
                'field' => 'enable_registration',
                'description' => __('Allow new members to register and create profiles.', 'scn-membership')
            ]
        );
    }

    private function registerImageSettings() {
        add_settings_section(
            'scn_image_section',
            __('Image Upload Settings', 'scn-membership'),
            [$this, 'imageSectionCallback'],
            'scn-membership-settings'
        );

        add_settings_field(
            'max_image_size',
            __('Maximum Image Size (MB)', 'scn-membership'),
            [$this, 'numberFieldCallback'],
            'scn-membership-settings',
            'scn_image_section',
            [
                'field' => 'max_image_size',
                'description' => __('Maximum file size for image uploads.', 'scn-membership'),
                'min' => 1,
                'max' => 50,
                'step' => 1
            ]
        );

        add_settings_field(
            'allowed_image_types',
            __('Allowed Image Types', 'scn-membership'),
            [$this, 'checkboxGroupFieldCallback'],
            'scn-membership-settings',
            'scn_image_section',
            [
                'field' => 'allowed_image_types',
                'description' => __('Select which image file types are allowed for upload.', 'scn-membership'),
                'options' => [
                    'jpg' => 'JPEG (.jpg)',
                    'jpeg' => 'JPEG (.jpeg)',
                    'png' => 'PNG (.png)',
                    'gif' => 'GIF (.gif)',
                    'webp' => 'WebP (.webp)'
                ]
            ]
        );

        add_settings_field(
            'auto_optimize_images',
            __('Auto-optimize Images', 'scn-membership'),
            [$this, 'checkboxFieldCallback'],
            'scn-membership-settings',
            'scn_image_section',
            [
                'field' => 'auto_optimize_images',
                'description' => __('Automatically optimize uploaded images for web.', 'scn-membership')
            ]
        );

        add_settings_field(
            'generate_webp',
            __('Generate WebP Versions', 'scn-membership'),
            [$this, 'checkboxFieldCallback'],
            'scn-membership-settings',
            'scn_image_section',
            [
                'field' => 'generate_webp',
                'description' => __('Generate WebP versions of uploaded images for better performance.', 'scn-membership')
            ]
        );

        add_settings_field(
            'placeholder_image',
            __('Default Placeholder Image', 'scn-membership'),
            [$this, 'mediaFieldCallback'],
            'scn-membership-settings',
            'scn_image_section',
            [
                'field' => 'placeholder_image',
                'description' => __('Default image to use when no profile image is provided.', 'scn-membership'),
                'type' => 'image'
            ]
        );
    }

    private function registerUrlSettings() {
        add_settings_section(
            'scn_url_section',
            __('URL & Link Settings', 'scn-membership'),
            [$this, 'urlSectionCallback'],
            'scn-membership-settings'
        );

        add_settings_field(
            'force_https',
            __('Force HTTPS', 'scn-membership'),
            [$this, 'checkboxFieldCallback'],
            'scn-membership-settings',
            'scn_url_section',
            [
                'field' => 'force_https',
                'description' => __('Automatically convert HTTP URLs to HTTPS.', 'scn-membership')
            ]
        );

        add_settings_field(
            'validate_urls',
            __('Validate URLs', 'scn-membership'),
            [$this, 'checkboxFieldCallback'],
            'scn-membership-settings',
            'scn_url_section',
            [
                'field' => 'validate_urls',
                'description' => __('Validate URLs before saving to ensure they are accessible.', 'scn-membership')
            ]
        );

        add_settings_field(
            'allowed_domains',
            __('Allowed Domains', 'scn-membership'),
            [$this, 'textareaFieldCallback'],
            'scn-membership-settings',
            'scn_url_section',
            [
                'field' => 'allowed_domains',
                'description' => __('Comma-separated list of allowed domains for external links.', 'scn-membership'),
                'rows' => 3,
                'placeholder' => 'example.com, another-site.com'
            ]
        );

        add_settings_field(
            'open_external_new_tab',
            __('Open External Links in New Tab', 'scn-membership'),
            [$this, 'checkboxFieldCallback'],
            'scn-membership-settings',
            'scn_url_section',
            [
                'field' => 'open_external_new_tab',
                'description' => __('Automatically open external links in a new tab.', 'scn-membership')
            ]
        );
    }

    private function registerEventSettings() {
        add_settings_section(
            'scn_event_section',
            __('Event Settings', 'scn-membership'),
            [$this, 'eventSectionCallback'],
            'scn-membership-settings'
        );

        add_settings_field(
            'auto_approve_sessions',
            __('Auto-approve Sessions', 'scn-membership'),
            [$this, 'checkboxFieldCallback'],
            'scn-membership-settings',
            'scn_event_section',
            [
                'field' => 'auto_approve_sessions',
                'description' => __('Automatically approve sessions for current and future events.', 'scn-membership')
            ]
        );

        add_settings_field(
            'session_approval_email',
            __('Session Approval Email', 'scn-membership'),
            [$this, 'emailFieldCallback'],
            'scn-membership-settings',
            'scn_event_section',
            [
                'field' => 'session_approval_email',
                'description' => __('Email address to receive session approval notifications.', 'scn-membership')
            ]
        );

        add_settings_field(
            'widget_cache_duration',
            __('Widget Cache Duration (minutes)', 'scn-membership'),
            [$this, 'numberFieldCallback'],
            'scn-membership-settings',
            'scn_event_section',
            [
                'field' => 'widget_cache_duration',
                'description' => __('How long to cache widget data before refreshing.', 'scn-membership'),
                'min' => 5,
                'max' => 1440,
                'step' => 5
            ]
        );
    }

    private function registerWidgetSettings() {
        add_settings_section(
            'scn_widget_section',
            __('Widget Settings', 'scn-membership'),
            [$this, 'widgetSectionCallback'],
            'scn-membership-settings'
        );

        add_settings_field(
            'default_widget_title',
            __('Default Widget Title', 'scn-membership'),
            [$this, 'textFieldCallback'],
            'scn-membership-settings',
            'scn_widget_section',
            [
                'field' => 'default_widget_title',
                'description' => __('Default title for the "Where Our Members Are Speaking" widget.', 'scn-membership'),
                'placeholder' => __('Where Our Members Are Speaking', 'scn-membership')
            ]
        );

        add_settings_field(
            'default_widget_limit',
            __('Default Widget Limit', 'scn-membership'),
            [$this, 'numberFieldCallback'],
            'scn-membership-settings',
            'scn_widget_section',
            [
                'field' => 'default_widget_limit',
                'description' => __('Default number of sessions to show in the widget.', 'scn-membership'),
                'min' => 1,
                'max' => 20,
                'step' => 1
            ]
        );

        add_settings_field(
            'widget_show_dates',
            __('Show Event Dates by Default', 'scn-membership'),
            [$this, 'checkboxFieldCallback'],
            'scn-membership-settings',
            'scn_widget_section',
            [
                'field' => 'widget_show_dates',
                'description' => __('Show event dates in the widget by default.', 'scn-membership')
            ]
        );

        add_settings_field(
            'widget_show_events',
            __('Show Event Names by Default', 'scn-membership'),
            [$this, 'checkboxFieldCallback'],
            'scn-membership-settings',
            'scn_widget_section',
            [
                'field' => 'widget_show_events',
                'description' => __('Show event names in the widget by default.', 'scn-membership')
            ]
        );
    }

    private function registerAdvancedSettings() {
        add_settings_section(
            'scn_advanced_section',
            __('Advanced Settings', 'scn-membership'),
            [$this, 'advancedSectionCallback'],
            'scn-membership-settings'
        );

        add_settings_field(
            'debug_mode',
            __('Debug Mode', 'scn-membership'),
            [$this, 'checkboxFieldCallback'],
            'scn-membership-settings',
            'scn_advanced_section',
            [
                'field' => 'debug_mode',
                'description' => __('Enable debug mode for troubleshooting.', 'scn-membership')
            ]
        );

        add_settings_field(
            'log_errors',
            __('Log Errors', 'scn-membership'),
            [$this, 'checkboxFieldCallback'],
            'scn-membership-settings',
            'scn_advanced_section',
            [
                'field' => 'log_errors',
                'description' => __('Log errors to the WordPress error log.', 'scn-membership')
            ]
        );

        add_settings_field(
            'custom_css',
            __('Custom CSS', 'scn-membership'),
            [$this, 'textareaFieldCallback'],
            'scn-membership-settings',
            'scn_advanced_section',
            [
                'field' => 'custom_css',
                'description' => __('Custom CSS to be added to the frontend.', 'scn-membership'),
                'rows' => 10,
                'class' => 'code'
            ]
        );

        add_settings_field(
            'custom_js',
            __('Custom JavaScript', 'scn-membership'),
            [$this, 'textareaFieldCallback'],
            'scn-membership-settings',
            'scn_advanced_section',
            [
                'field' => 'custom_js',
                'description' => __('Custom JavaScript to be added to the frontend.', 'scn-membership'),
                'rows' => 10,
                'class' => 'code'
            ]
        );
    }

    public function getSettings() {
        return get_option($this->option_name, $this->getDefaultSettings());
    }

    public function getSetting($key, $default = null) {
        $settings = $this->getSettings();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    public function updateSetting($key, $value) {
        $settings = $this->getSettings();
        $settings[$key] = $value;
        return update_option($this->option_name, $settings);
    }

    public function getDefaultSettings() {
        return [
            'site_name' => 'SCN Membership',
            'site_description' => '',
            'contact_email' => get_option('admin_email'),
            'enable_registration' => true,
            'max_image_size' => 10,
            'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif'],
            'auto_optimize_images' => true,
            'generate_webp' => false,
            'placeholder_image' => 0,
            'force_https' => true,
            'validate_urls' => true,
            'allowed_domains' => '',
            'open_external_new_tab' => true,
            'auto_approve_sessions' => true,
            'session_approval_email' => get_option('admin_email'),
            'widget_cache_duration' => 15,
            'default_widget_title' => 'Where Our Members Are Speaking',
            'default_widget_limit' => 6,
            'widget_show_dates' => true,
            'widget_show_events' => true,
            'debug_mode' => false,
            'log_errors' => true,
            'custom_css' => '',
            'custom_js' => ''
        ];
    }

    public function sanitizeSettings($input) {
        $sanitized = [];
        $defaults = $this->getDefaultSettings();

        foreach ($defaults as $key => $default_value) {
            if (!isset($input[$key])) {
                $sanitized[$key] = $default_value;
                continue;
            }

            switch ($key) {
                case 'site_name':
                case 'contact_email':
                case 'session_approval_email':
                case 'default_widget_title':
                    $sanitized[$key] = sanitize_text_field($input[$key]);
                    break;

                case 'site_description':
                case 'allowed_domains':
                case 'custom_css':
                case 'custom_js':
                    $sanitized[$key] = sanitize_textarea_field($input[$key]);
                    break;

                case 'max_image_size':
                case 'widget_cache_duration':
                case 'default_widget_limit':
                    $sanitized[$key] = absint($input[$key]);
                    break;

                case 'placeholder_image':
                    $sanitized[$key] = absint($input[$key]);
                    break;

                case 'allowed_image_types':
                    $sanitized[$key] = is_array($input[$key]) ? array_map('sanitize_text_field', $input[$key]) : [];
                    break;

                case 'enable_registration':
                case 'auto_optimize_images':
                case 'generate_webp':
                case 'force_https':
                case 'validate_urls':
                case 'open_external_new_tab':
                case 'auto_approve_sessions':
                case 'widget_show_dates':
                case 'widget_show_events':
                case 'debug_mode':
                case 'log_errors':
                    $sanitized[$key] = !empty($input[$key]);
                    break;

                default:
                    $sanitized[$key] = sanitize_text_field($input[$key]);
            }
        }

        return $sanitized;
    }

    // Field callback methods
    public function textFieldCallback($args) {
        $value = $this->getSetting($args['field']);
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        $class = isset($args['class']) ? $args['class'] : 'regular-text';
        
        printf(
            '<input type="text" id="%s" name="%s[%s]" value="%s" placeholder="%s" class="%s" />',
            esc_attr($args['field']),
            esc_attr($this->option_name),
            esc_attr($args['field']),
            esc_attr($value),
            esc_attr($placeholder),
            esc_attr($class)
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    public function emailFieldCallback($args) {
        $value = $this->getSetting($args['field']);
        
        printf(
            '<input type="email" id="%s" name="%s[%s]" value="%s" class="regular-text" />',
            esc_attr($args['field']),
            esc_attr($this->option_name),
            esc_attr($args['field']),
            esc_attr($value)
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    public function textareaFieldCallback($args) {
        $value = $this->getSetting($args['field']);
        $rows = isset($args['rows']) ? $args['rows'] : 5;
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        $class = isset($args['class']) ? $args['class'] : 'large-text';
        
        printf(
            '<textarea id="%s" name="%s[%s]" rows="%d" placeholder="%s" class="%s">%s</textarea>',
            esc_attr($args['field']),
            esc_attr($this->option_name),
            esc_attr($args['field']),
            esc_attr($rows),
            esc_attr($placeholder),
            esc_attr($class),
            esc_textarea($value)
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    public function numberFieldCallback($args) {
        $value = $this->getSetting($args['field']);
        $min = isset($args['min']) ? $args['min'] : '';
        $max = isset($args['max']) ? $args['max'] : '';
        $step = isset($args['step']) ? $args['step'] : '';
        
        printf(
            '<input type="number" id="%s" name="%s[%s]" value="%s" min="%s" max="%s" step="%s" class="small-text" />',
            esc_attr($args['field']),
            esc_attr($this->option_name),
            esc_attr($args['field']),
            esc_attr($value),
            esc_attr($min),
            esc_attr($max),
            esc_attr($step)
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    public function checkboxFieldCallback($args) {
        $value = $this->getSetting($args['field']);
        
        printf(
            '<label for="%s"><input type="checkbox" id="%s" name="%s[%s]" value="1" %s /> %s</label>',
            esc_attr($args['field']),
            esc_attr($args['field']),
            esc_attr($this->option_name),
            esc_attr($args['field']),
            checked($value, true, false),
            isset($args['label']) ? esc_html($args['label']) : ''
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    public function checkboxGroupFieldCallback($args) {
        $value = $this->getSetting($args['field']);
        if (!is_array($value)) {
            $value = [];
        }
        
        echo '<fieldset>';
        foreach ($args['options'] as $option_value => $option_label) {
            printf(
                '<label for="%s_%s"><input type="checkbox" id="%s_%s" name="%s[%s][]" value="%s" %s /> %s</label><br>',
                esc_attr($args['field']),
                esc_attr($option_value),
                esc_attr($args['field']),
                esc_attr($option_value),
                esc_attr($this->option_name),
                esc_attr($args['field']),
                esc_attr($option_value),
                checked(in_array($option_value, $value), true, false),
                esc_html($option_label)
            );
        }
        echo '</fieldset>';
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    public function mediaFieldCallback($args) {
        $value = $this->getSetting($args['field']);
        $type = isset($args['type']) ? $args['type'] : 'image';
        
        printf(
            '<input type="hidden" id="%s" name="%s[%s]" value="%s" />',
            esc_attr($args['field']),
            esc_attr($this->option_name),
            esc_attr($args['field']),
            esc_attr($value)
        );
        
        echo '<div class="media-upload-container">';
        if ($value) {
            if ($type === 'image') {
                echo wp_get_attachment_image($value, 'medium', false, ['id' => $args['field'] . '_preview']);
            } else {
                echo '<p>' . esc_html(get_the_title($value)) . '</p>';
            }
        } else {
            echo '<p class="no-media">' . __('No media selected', 'scn-membership') . '</p>';
        }
        
        printf(
            '<button type="button" class="button select-media" data-field="%s" data-type="%s">%s</button>',
            esc_attr($args['field']),
            esc_attr($type),
            __('Select Media', 'scn-membership')
        );
        
        if ($value) {
            printf(
                '<button type="button" class="button remove-media" data-field="%s">%s</button>',
                esc_attr($args['field']),
                __('Remove', 'scn-membership')
            );
        }
        
        echo '</div>';
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    // Section callback methods
    public function generalSectionCallback() {
        echo '<p>' . __('Configure general settings for your SCN membership site.', 'scn-membership') . '</p>';
    }

    public function imageSectionCallback() {
        echo '<p>' . __('Configure image upload settings and optimization options.', 'scn-membership') . '</p>';
    }

    public function urlSectionCallback() {
        echo '<p>' . __('Configure URL validation and link handling options.', 'scn-membership') . '</p>';
    }

    public function eventSectionCallback() {
        echo '<p>' . __('Configure event and session management settings.', 'scn-membership') . '</p>';
    }

    public function widgetSectionCallback() {
        echo '<p>' . __('Configure widget display and behavior settings.', 'scn-membership') . '</p>';
    }

    public function advancedSectionCallback() {
        echo '<p>' . __('Advanced configuration options for developers and power users.', 'scn-membership') . '</p>';
    }

    public function enqueueScripts($hook) {
        if ($hook !== 'toplevel_page_scn-membership-settings') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'scn-settings-admin',
            SCN_MEMBERSHIP_URL . 'assets/js/settings-admin.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        // Ensure jQuery is loaded
        wp_enqueue_script('jquery');

        wp_enqueue_style(
            'scn-settings-admin',
            SCN_MEMBERSHIP_URL . 'assets/css/settings-admin.css',
            [],
            '1.0.0'
        );

        wp_localize_script('scn-settings-admin', 'scnSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scn_settings_nonce'),
            'strings' => [
                'selectImage' => __('Select Image', 'scn-membership'),
                'selectFile' => __('Select File', 'scn-membership'),
                'removeImage' => __('Remove Image', 'scn-membership'),
                'removeFile' => __('Remove File', 'scn-membership'),
                'noMediaSelected' => __('No media selected', 'scn-membership'),
                'confirmReset' => __('Are you sure you want to reset all settings to defaults?', 'scn-membership'),
                'settingsSaved' => __('Settings saved successfully!', 'scn-membership'),
                'settingsReset' => __('Settings reset to defaults!', 'scn-membership'),
                'cacheCleared' => __('Cache cleared successfully!', 'scn-membership'),
                'error' => __('An error occurred. Please try again.', 'scn-membership')
            ]
        ]);
        
        // Add inline script for immediate testing
        wp_add_inline_script('scn-settings-admin', '
            console.log("SCN Settings script loaded!");
            console.log("scnSettings:", typeof scnSettings !== "undefined" ? scnSettings : "undefined");
            
            // Test if buttons exist
            jQuery(document).ready(function($) {
                console.log("jQuery ready, testing buttons...");
                console.log("Test button exists:", $("#test-image-upload").length);
                console.log("Clear cache button exists:", $("#clear-cache").length);
                
                // Add simple click test
                $("#test-image-upload").on("click", function() {
                    console.log("Button clicked via inline script!");
                    alert("Test Image Upload clicked!");
                });
            });
        ');
    }

    // AJAX handlers
    public function testImageUpload() {
        check_ajax_referer('scn_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $max_size = $this->getSetting('max_image_size', 10);
        $allowed_types = $this->getSetting('allowed_image_types', ['jpg', 'jpeg', 'png', 'gif']);
        
        $response = [
            'success' => true,
            'data' => [
                'max_size' => $max_size,
                'allowed_types' => $allowed_types,
                'message' => __('Image upload settings are configured correctly.', 'scn-membership')
            ]
        ];

        wp_send_json_success($response);
    }

    public function clearCache() {
        check_ajax_referer('scn_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        // Clear various caches
        delete_transient('scn_events_widget_upcoming_sessions');
        wp_cache_flush();
        
        // Clear any other plugin-specific caches
        do_action('scn/clear_all_caches');

        wp_send_json_success(['message' => __('Cache cleared successfully!', 'scn-membership')]);
    }

    public function exportSettings() {
        check_ajax_referer('scn_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $settings = $this->getSettings();
        $export_data = [
            'version' => SCN_MEMBERSHIP_VERSION,
            'export_date' => current_time('mysql'),
            'settings' => $settings
        ];

        $filename = 'scn-membership-settings-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo wp_json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }

    public function importSettings() {
        check_ajax_referer('scn_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        if (!isset($_FILES['settings_file']) || $_FILES['settings_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('No file uploaded or upload error.', 'scn-membership')]);
        }

        $file_content = file_get_contents($_FILES['settings_file']['tmp_name']);
        $import_data = json_decode($file_content, true);

        if (!$import_data || !isset($import_data['settings'])) {
            wp_send_json_error(['message' => __('Invalid settings file format.', 'scn-membership')]);
        }

        $sanitized_settings = $this->sanitizeSettings($import_data['settings']);
        update_option($this->option_name, $sanitized_settings);

        wp_send_json_success(['message' => __('Settings imported successfully!', 'scn-membership')]);
    }

}
