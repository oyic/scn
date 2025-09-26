<?php

namespace SCN\Membership\Modules\Auth;

class AuthModule {
    private $auth_service;
    private $shortcodes;

    public function register() {
        $this->auth_service = new AuthService();
        $this->auth_service->register();
        
        $this->shortcodes = new AuthShortcodes();
        $this->shortcodes->register();
        
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function init() {
        // Initialize auth module
        $this->registerHooks();
    }

    public function enqueueScripts() {
        if (get_query_var('scn_member_login') || 
            get_query_var('scn_member_register') || 
            get_query_var('scn_member_dashboard')) {
            
            // Use minimal auth script to avoid JavaScript errors
            wp_enqueue_script(
                'scn-auth-minimal',
                SCN_MEMBERSHIP_URL . 'assets/js/auth-minimal.js',
                ['jquery'],
                SCN_MEMBERSHIP_VERSION,
                true
            );

            wp_localize_script('scn-auth-minimal', 'scnAuth', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('scn_auth_nonce'),
                'urls' => $this->auth_service->getAuthUrls(),
                'strings' => [
                    'loading' => __('Loading...', 'scn-membership'),
                    'error' => __('An error occurred', 'scn-membership'),
                    'success' => __('Success', 'scn-membership'),
                ],
            ]);
        }
    }

    public function registerHooks() {
        // Add custom hooks for authentication
        add_filter('login_redirect', [$this, 'customLoginRedirect'], 10, 3);
        add_filter('logout_redirect', [$this, 'customLogoutRedirect'], 10, 3);
        
        // Add member-specific capabilities
        add_action('init', [$this, 'addMemberCapabilities']);
        
        // Handle profile creation after user registration
        add_action('user_register', [$this, 'handleUserRegistration']);
    }

    public function customLoginRedirect($redirect_to, $requested_redirect_to, $user) {
        // If user has a profile, redirect to dashboard
        if ($user && !is_wp_error($user)) {
            $profile = $this->auth_service->getUserProfileById($user->ID);
            if ($profile) {
                return home_url('/member-dashboard/');
            }
        }
        
        return $redirect_to;
    }

    public function customLogoutRedirect($redirect_to, $requested_redirect_to, $user) {
        return home_url('/member-login/');
    }

    public function addMemberCapabilities() {
        // Add capabilities for members
        $role = get_role('subscriber');
        if ($role) {
            $capabilities = [
                'read_scn_profiles',
                'edit_scn_profiles',
                'publish_scn_profiles',
                'delete_scn_profiles',
                'edit_published_scn_profiles',
                'create_scn_sessions',
                'edit_scn_sessions',
                'publish_scn_sessions',
            ];

            foreach ($capabilities as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    public function handleUserRegistration($user_id) {
        // Auto-create profile for new users if they don't have one
        if (!$this->auth_service->userHasProfile($user_id)) {
            $user = get_userdata($user_id);
            if ($user) {
                $profile_data = [
                    'post_title' => $user->display_name ?: $user->user_login,
                    'post_type' => 'scn_profile',
                    'post_status' => 'publish',
                    'post_author' => $user_id,
                ];
                
                $profile_id = wp_insert_post($profile_data);
                
                if ($profile_id) {
                    update_post_meta($profile_id, 'scn_user_id', $user_id);
                    update_post_meta($profile_id, 'scn_member_since', current_time('mysql'));
                    
                    // Set basic profile info from user data
                    if ($user->first_name) {
                        update_post_meta($profile_id, 'scn_first_name', $user->first_name);
                    }
                    if ($user->last_name) {
                        update_post_meta($profile_id, 'scn_last_name', $user->last_name);
                    }
                }
            }
        }
    }

    /**
     * Get authentication service instance
     */
    public function getAuthService() {
        return $this->auth_service;
    }
}
