<?php

namespace SCN\Membership\Modules\Auth;

if (!defined('ABSPATH')) {
    exit;
}

class AuthShortcodes
{

    public function register()
    {
        add_shortcode('scn_member_login', [$this, 'renderLoginPage']);
        add_shortcode('scn_member_register', [$this, 'renderRegisterPage']);
        add_shortcode('scn_member_dashboard', [$this, 'renderDashboardPage']);
        add_shortcode('scn_test_auth', [$this, 'renderTestPage']);
    }

    /**
     * Check if we should allow admin access (prevent redirects in admin context)
     */
    private function shouldAllowAdminAccess()
    {
        // Allow if user can edit pages (admin/editor)
        if (current_user_can('edit_pages')) {
            return true;
        }
        
        // Allow if we're in admin area
        if (is_admin()) {
            return true;
        }
        
        // Allow if we're in preview mode
        if (isset($_GET['preview']) && $_GET['preview'] === 'true') {
            return true;
        }
        
        // Allow if we're editing a post/page
        if (isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit') {
            return true;
        }
        
        // Allow if we're in the post editor
        if (isset($_GET['post_type']) && isset($_GET['page']) && $_GET['page'] === 'edit') {
            return true;
        }
        
        // Allow if we're in the block editor
        if (isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['meta-box-loader'])) {
            return true;
        }
        
        return false;
    }

    public function renderLoginPage($atts)
    {
        // Handle logout action
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            // Clear WordPress user session if logged in
            if (is_user_logged_in()) {
                wp_logout();
            }
            
            // Clear profile session
            if (function_exists('scn_logout_profile')) {
                scn_logout_profile();
            }
            
            // Redirect to login page without action parameter
            $login_page = get_page_by_path('member-login');
            if ($login_page) {
                wp_redirect(get_permalink($login_page->ID));
            } else {
                wp_redirect(home_url('/member-login/'));
            }
            exit;
        }

        // Don't redirect if we're in admin, preview mode, or editing mode
        if (is_user_logged_in() && !$this->shouldAllowAdminAccess()) {
            wp_redirect(home_url('/member-dashboard/'));
            exit;
        }

        // Handle login form submission
        if ($_POST && isset($_POST['scn_member_login'])) {
            $username = sanitize_text_field($_POST['username']);
            $password = $_POST['password'];
            $remember = isset($_POST['rememberme']) ? true : false;
            
            $creds = [
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => $remember,
            ];
            
            $user = wp_signon($creds, false);
            
            if (!is_wp_error($user)) {
                // Check if user has a profile
                $profile_posts = get_posts([
                    'post_type' => 'scn_profile',
                    'meta_query' => [
                        [
                            'key' => 'scn_user_id',
                            'value' => $user->ID,
                            'compare' => '='
                        ]
                    ],
                    'posts_per_page' => 1,
                    'post_status' => 'publish'
                ]);
                
                if (!empty($profile_posts)) {
                    // User has profile, redirect to dashboard
                    wp_redirect(home_url('/member-dashboard/'));
                } else {
                    // User doesn't have profile, redirect to profile creation
                    wp_redirect(admin_url('post-new.php?post_type=scn_profile'));
                }
                exit;
            } else {
                $login_error = $user->get_error_message();
            }
        }

        ob_start();
        include SCN_MEMBERSHIP_PATH . 'templates/auth/no-js-login.php';
        return ob_get_clean();
    }

    public function renderRegisterPage($atts)
    {
        // Don't redirect if we're in admin, preview mode, or editing mode
        if (is_user_logged_in() && !$this->shouldAllowAdminAccess()) {
            wp_redirect(home_url('/member-dashboard/'));
            exit;
        }

        ob_start();
        include SCN_MEMBERSHIP_PATH . 'templates/auth/member-register.php';
        return ob_get_clean();
    }

    public function renderDashboardPage($atts)
    {
        // Check if user is logged in (but allow admin/preview access)
        if (!is_user_logged_in() && !$this->shouldAllowAdminAccess()) {
            wp_redirect(home_url('/member-login/'));
            exit;
        }

        ob_start();
        include SCN_MEMBERSHIP_PATH . 'templates/profiles/dashboard.php';
        return ob_get_clean();
    }

    public function renderTestPage($atts)
    {
        ob_start();
        include SCN_MEMBERSHIP_PATH . 'templates/auth/test-auth.php';
        return ob_get_clean();
    }
}
