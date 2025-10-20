<?php

namespace SCN\Membership\Modules\Auth;

class AuthService {
    public function register() {
        add_action('init', [$this, 'addRewriteRules']);
        add_filter('query_vars', [$this, 'addQueryVars']);
        add_filter('template_include', [$this, 'templateInclude']);
        add_action('wp_login', [$this, 'handleLoginRedirect'], 10, 2);
        add_action('wp_logout', [$this, 'handleLogoutRedirect']);
        add_action('template_redirect', [$this, 'handleAuthRedirects']);
        add_action('wp_ajax_scn_check_user_profile', [$this, 'checkUserProfile']);
        add_action('wp_ajax_nopriv_scn_check_user_profile', [$this, 'checkUserProfile']);
    }

    public function addRewriteRules() {
        // Member login page
        add_rewrite_rule(
            '^member-login/?$',
            'index.php?member_login=1',
            'top'
        );
        
        // Member registration page
        add_rewrite_rule(
            '^member-register/?$',
            'index.php?member_register=1',
            'top'
        );
        
        // Member dashboard
        add_rewrite_rule(
            '^member-dashboard/?$',
            'index.php?member_dashboard=1',
            'top'
        );
        
        // Member logout
        add_rewrite_rule(
            '^member-logout/?$',
            'index.php?member_logout=1',
            'top'
        );
        
        // Test authentication page
        add_rewrite_rule(
            '^test-auth/?$',
            'index.php?scn_test_auth=1',
            'top'
        );
    }

    public function addQueryVars($vars) {
        $vars[] = 'member_login';
        $vars[] = 'member_register';
        $vars[] = 'member_dashboard';
        $vars[] = 'member_logout';
        $vars[] = 'scn_test_auth';
        return $vars;
    }

    public function templateInclude($template) {
        if (get_query_var('member_login')) {
            return SCN_MEMBERSHIP_PATH . 'templates/auth/member-login.php';
        }
        
        if (get_query_var('member_register')) {
            return SCN_MEMBERSHIP_PATH . 'templates/auth/member-register.php';
        }
        
        if (get_query_var('member_dashboard')) {
            return SCN_MEMBERSHIP_PATH . 'templates/profiles/profile-dashboard-fixed.php';
        }
        
        if (get_query_var('member_logout')) {
            // Include authentication functions
            require_once SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions.php';
            
            // Logout profile
            if (function_exists('scn_logout_profile')) {
                scn_logout_profile();
            }
            
            // Redirect to login
            wp_redirect(home_url('/member-login/'));
            exit;
        }
        
        if (get_query_var('scn_test_auth')) {
            return SCN_MEMBERSHIP_PATH . 'templates/auth/test-auth.php';
        }
        
        return $template;
    }

    public function handleLoginRedirect($user_login, $user) {
        // Check if user has a member profile
        $member = $this->getUserMember($user->ID);
        
        if ($member) {
            // User has member profile, redirect to member CPT URL format: /member/slug-name
            wp_redirect(get_permalink($member->ID));
            exit;
        } else {
            // User doesn't have profile, redirect to member creation
            wp_redirect(admin_url('post-new.php?post_type=member'));
            exit;
        }
    }

    public function handleLogoutRedirect() {
        wp_redirect(home_url('/member-login/'));
        exit;
    }

    public function handleLogout() {
        if (is_user_logged_in()) {
            wp_logout();
        }
        wp_redirect(home_url('/member-login/'));
        exit;
    }

    public function handleAuthRedirects() {
        // Redirect logged-in users away from login/register pages
        if (is_user_logged_in()) {
            if (get_query_var('member_login') || get_query_var('member_register')) {
                $current_user_id = get_current_user_id();
                $member = $this->getUserMember($current_user_id);
                
                if ($member) {
                    // Redirect to member CPT URL format: /member/slug-name
                    wp_redirect(get_permalink($member->ID));
                } else {
                    wp_redirect(home_url('/member-dashboard/'));
                }
                exit;
            }
        } else {
            // Redirect non-logged-in users away from dashboard
            if (get_query_var('member_dashboard')) {
                wp_redirect(home_url('/member-login/'));
                exit;
            }
        }
    }

    public function checkUserProfile() {
        check_ajax_referer('scn_auth_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('User not logged in.', 'scn-membership'));
        }
        
        $user_id = get_current_user_id();
        $member = $this->getUserMember($user_id);
        
        wp_send_json_success([
            'has_profile' => !empty($member),
            'member_id' => $member ? $member->ID : null,
            'redirect_url' => $member ? get_permalink($member->ID) : admin_url('post-new.php?post_type=member')
        ]);
    }

    private function getUserMember($user_id) {
        $member_posts = get_posts([
            'post_type' => 'member',
            'meta_query' => [
                [
                    'key' => 'scn_user_id',
                    'value' => $user_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        
        return !empty($member_posts) ? $member_posts[0] : null;
    }

    private function getUserProfile($user_id) {
        $profile_posts = get_posts([
            'post_type' => 'member',
            'meta_query' => [
                [
                    'key' => 'scn_user_id',
                    'value' => $user_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);

        return !empty($profile_posts) ? $profile_posts[0] : null;
    }

    /**
     * Create a member user with profile
     */
    public function createMemberUser($user_data) {
        // Create WordPress user
        $user_id = wp_create_user(
            $user_data['username'],
            $user_data['password'],
            $user_data['email']
        );
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Update user meta
        if (isset($user_data['first_name'])) {
            update_user_meta($user_id, 'first_name', $user_data['first_name']);
        }
        if (isset($user_data['last_name'])) {
            update_user_meta($user_id, 'last_name', $user_data['last_name']);
        }
        
        // Create profile post
        $profile_data = [
            'post_title' => ($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''),
            'post_type' => 'member',
            'post_status' => 'publish',
            'post_author' => $user_id,
        ];
        
        $profile_id = wp_insert_post($profile_data);
        
        if ($profile_id) {
            // Set profile meta
            update_post_meta($profile_id, 'scn_user_id', $user_id);
            if (isset($user_data['first_name'])) {
                update_post_meta($profile_id, 'scn_first_name', $user_data['first_name']);
            }
            if (isset($user_data['last_name'])) {
                update_post_meta($profile_id, 'scn_last_name', $user_data['last_name']);
            }
            update_post_meta($profile_id, 'member_since', current_time('mysql'));
            
            return [
                'user_id' => $user_id,
                'profile_id' => $profile_id
            ];
        }
        
        return new \WP_Error('profile_creation_failed', __('Failed to create profile.', 'scn-membership'));
    }

    /**
     * Get user profile by user ID
     */
    public function getUserProfileById($user_id) {
        return $this->getUserProfile($user_id);
    }

    /**
     * Check if user has a profile
     */
    public function userHasProfile($user_id) {
        $profile = $this->getUserProfile($user_id);
        return !empty($profile);
    }

    /**
     * Get authentication URLs
     */
    public function getAuthUrls() {
        return [
            'login' => home_url('/member-login/'),
            'register' => home_url('/member-register/'),
            'dashboard' => home_url('/member-dashboard/'),
            'logout' => home_url('/member-logout/'),
        ];
    }
}
