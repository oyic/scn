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
        add_shortcode('scn_member_profile', [$this, 'renderMemberProfilePage']);
        add_shortcode('scn_test_auth', [$this, 'renderTestPage']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueProfileScripts']);
    }
    
    /**
     * Enqueue scripts for member profile page
     */
    public function enqueueProfileScripts()
    {
        // Check if we're on a page with the profile shortcode and user is logged in
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'scn_member_profile') && is_user_logged_in()) {
            // Enqueue WordPress media scripts for image upload/cropping
            wp_enqueue_media();
            wp_enqueue_script('jquery');
            wp_enqueue_script('media-editor');
            wp_enqueue_script('media-views');
            wp_enqueue_script('image-edit');
            wp_enqueue_script('wp-util');
        }
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
        
        // Allow if we're in the WordPress admin (check URL)
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-admin/') !== false) {
            return true;
        }
        
        // Allow if we're in the post editor (check for post parameter)
        if (isset($_GET['post']) && is_numeric($_GET['post'])) {
            return true;
        }
        
        // Allow if we're in the block editor (check for editor parameter)
        if (isset($_GET['editor']) && $_GET['editor'] === 'block') {
            return true;
        }
        
        // Allow if we're in the classic editor
        if (isset($_GET['classic-editor'])) {
            return true;
        }
        
        // Allow if we're in the post editor (check for action parameter)
        if (isset($_GET['action']) && $_GET['action'] === 'edit') {
            return true;
        }
        
        // Allow if we're in the admin context (check current screen)
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && $screen->is_admin()) {
                return true;
            }
        }
        
        // Allow if we're in the admin context (check for admin-ajax)
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'admin-ajax.php') !== false) {
            return true;
        }
        
        // Allow if we're in the admin context (check for admin-post)
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'admin-post.php') !== false) {
            return true;
        }
        
        // Allow if we're in the admin context (check for admin.php)
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'admin.php') !== false) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if we're in a backend context (admin, editor, etc.)
     */
    private function isBackendContext()
    {
        // Check if we're in admin area
        if (is_admin()) {
            return true;
        }
        
        // Check if we're in preview mode
        if (isset($_GET['preview']) && $_GET['preview'] === 'true') {
            return true;
        }
        
        // Check if we're editing a post/page
        if (isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit') {
            return true;
        }
        
        // Check if we're in the post editor
        if (isset($_GET['post_type']) && isset($_GET['page']) && $_GET['page'] === 'edit') {
            return true;
        }
        
        // Check if we're in the block editor
        if (isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['meta-box-loader'])) {
            return true;
        }
        
        // Check if we're in the WordPress admin (check URL)
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-admin/') !== false) {
            return true;
        }
        
        // Check if we're in the post editor (check for post parameter)
        if (isset($_GET['post']) && is_numeric($_GET['post'])) {
            return true;
        }
        
        // Check if we're in the block editor (check for editor parameter)
        if (isset($_GET['editor']) && $_GET['editor'] === 'block') {
            return true;
        }
        
        // Check if we're in the classic editor
        if (isset($_GET['classic-editor'])) {
            return true;
        }
        
        // Check if we're in the post editor (check for action parameter)
        if (isset($_GET['action']) && $_GET['action'] === 'edit') {
            return true;
        }
        
        // Check if we're in the admin context (check current screen)
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && $screen->is_admin()) {
                return true;
            }
        }
        
        // Check if we're in the admin context (check for admin-ajax)
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'admin-ajax.php') !== false) {
            return true;
        }
        
        // Check if we're in the admin context (check for admin-post)
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'admin-post.php') !== false) {
            return true;
        }
        
        // Check if we're in the admin context (check for admin.php)
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'admin.php') !== false) {
            return true;
        }
        
        return false;
    }

    public function renderLoginPage($atts)
    {
        // Don't render shortcode in admin/backend context
        if (is_admin() || $this->isBackendContext()) {
            return '';
        }

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

        // Redirect logged-in users to their profile
        if (is_user_logged_in()) {
            $current_user_id = get_current_user_id();
            $profile_posts = get_posts([
                'post_type' => 'scn_profile',
                'meta_query' => [
                    [
                        'key' => 'scn_user_id',
                        'value' => $current_user_id,
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1,
                'post_status' => 'publish'
            ]);
            
            if (!empty($profile_posts)) {
                wp_redirect(home_url('/members-profile/?profile_id=' . $profile_posts[0]->ID));
            } else {
                wp_redirect(home_url('/member-dashboard/'));
            }
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
                    // User has profile, redirect to frontend profile page
                    wp_redirect(home_url('/members-profile/?profile_id=' . $profile_posts[0]->ID));
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
        // Don't render shortcode in admin/backend context
        if (is_admin() || $this->isBackendContext()) {
            return '';
        }

        // Redirect logged-in users to their profile
        if (is_user_logged_in()) {
            $current_user_id = get_current_user_id();
            $profile_posts = get_posts([
                'post_type' => 'scn_profile',
                'meta_query' => [
                    [
                        'key' => 'scn_user_id',
                        'value' => $current_user_id,
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1,
                'post_status' => 'publish'
            ]);
            
            if (!empty($profile_posts)) {
                wp_redirect(home_url('/members-profile/?profile_id=' . $profile_posts[0]->ID));
            } else {
                wp_redirect(home_url('/member-dashboard/'));
            }
            exit;
        }

        ob_start();
        include SCN_MEMBERSHIP_PATH . 'templates/auth/member-register-enhanced.php';
        return ob_get_clean();
    }

    public function renderDashboardPage($atts)
    {
        // Don't render shortcode in admin/backend context
        if (is_admin() || $this->isBackendContext()) {
            return '';
        }

        // Redirect non-logged-in users to login page
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/member-login/'));
            exit;
        }

        ob_start();
        include SCN_MEMBERSHIP_PATH . 'templates/profiles/dashboard.php';
        return ob_get_clean();
    }

    public function renderMemberProfilePage($atts)
    {
        // Don't render shortcode in admin/backend context
        if (is_admin() || $this->isBackendContext()) {
            return '';
        }

        ob_start();
        include SCN_MEMBERSHIP_PATH . 'templates/profiles/member-profile.php';
        return ob_get_clean();
    }

    public function renderTestPage($atts)
    {
        // Don't render shortcode in admin/backend context
        if (is_admin() || $this->isBackendContext()) {
            return '';
        }

        ob_start();
        include SCN_MEMBERSHIP_PATH . 'templates/auth/test-auth.php';
        return ob_get_clean();
    }
}
