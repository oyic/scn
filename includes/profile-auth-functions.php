<?php
/**
 * Profile-Only Authentication Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Authenticate profile using username and password
 */
function scn_authenticate_profile($username, $password) {
    // Find profile by username
    $profiles = get_posts([
        'post_type' => 'profile',
        'meta_query' => [
            [
                'key' => 'scn_username',
                'value' => $username,
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ]);
    
    if (empty($profiles)) {
        return new WP_Error('invalid_username', 'Invalid username');
    }
    
    $profile = $profiles[0];
    $stored_password = get_post_meta($profile->ID, 'scn_password', true);
    
    if (!wp_check_password($password, $stored_password)) {
        return new WP_Error('invalid_password', 'Invalid password');
    }
    
    return $profile;
}

/**
 * Set profile session
 */
function scn_set_profile_session($profile_id) {
    if (!headers_sent()) {
        if (!session_id()) {
            session_start();
        }
        $_SESSION['profile_id'] = $profile_id;
        $_SESSION['profile_authenticated'] = true;
    } else {
        // Fallback to cookies if session can't be started
        setcookie('profile_id', $profile_id, time() + 3600, '/');
        setcookie('profile_authenticated', '1', time() + 3600, '/');
    }
}

/**
 * Check if profile is authenticated
 */
function scn_is_profile_authenticated() {
    if (!headers_sent()) {
        if (!session_id()) {
            session_start();
        }
        return isset($_SESSION['profile_authenticated']) && $_SESSION['profile_authenticated'];
    } else {
        // Fallback to cookies
        return isset($_COOKIE['profile_authenticated']) && $_COOKIE['profile_authenticated'] === '1';
    }
}

/**
 * Get current authenticated profile
 */
function scn_get_current_profile() {
    if (!scn_is_profile_authenticated()) {
        return null;
    }
    
    $profile_id = null;
    
    if (!headers_sent()) {
        if (!session_id()) {
            session_start();
        }
        $profile_id = $_SESSION['profile_id'] ?? null;
    } else {
        // Fallback to cookies
        $profile_id = $_COOKIE['profile_id'] ?? null;
    }
    
    if (!$profile_id) {
        return null;
    }
    
    return get_post($profile_id);
}

/**
 * Logout profile
 */
function scn_logout_profile() {
    if (!headers_sent()) {
        if (!session_id()) {
            session_start();
        }
        unset($_SESSION['profile_id']);
        unset($_SESSION['profile_authenticated']);
    }
    
    // Clear cookies
    setcookie('profile_id', '', time() - 3600, '/');
    setcookie('profile_authenticated', '', time() - 3600, '/');
}

/**
 * Get profile by ID
 */
function scn_get_profile($profile_id) {
    $profile = get_post($profile_id);
    if (!$profile || $profile->post_type !== 'profile') {
        return null;
    }
    return $profile;
}
