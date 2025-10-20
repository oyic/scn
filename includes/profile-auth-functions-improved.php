<?php
/**
 * Improved Profile-Only Authentication Functions
 * This version uses WordPress transients and user meta for reliable session management
 */

if (!defined("ABSPATH")) {
    exit;
}

/**
 * Authenticate profile using username and password
 */
function scn_authenticate_profile($username, $password) {
    // Find profile by username
    $profiles = get_posts([
        "post_type" => "profile",
        "meta_query" => [
            [
                "key" => "scn_username",
                "value" => $username,
                "compare" => "="
            ]
        ],
        "posts_per_page" => 1,
        "post_status" => "publish"
    ]);
    
    if (empty($profiles)) {
        return new WP_Error("invalid_username", "Invalid username");
    }
    
    $profile = $profiles[0];
    $stored_password = get_post_meta($profile->ID, "scn_password", true);
    
    if (!wp_check_password($password, $stored_password)) {
        return new WP_Error("invalid_password", "Invalid password");
    }
    
    return $profile;
}

/**
 * Set profile session using WordPress transients and cookies
 */
function scn_set_profile_session($profile_id) {
    // Generate a unique session token
    $session_token = wp_generate_password(32, false);
    
    // Store session data in WordPress transient (expires in 1 hour)
    $session_data = [
        "profile_id" => $profile_id,
        "authenticated" => true,
        "created" => current_time("timestamp")
    ];
    
    set_transient("scn_session_" . $session_token, $session_data, 3600); // 1 hour
    
    // Set secure cookie with the session token
    if (!headers_sent()) {
        setcookie(
            "scn_session_token", 
            $session_token, 
            time() + 3600, // 1 hour
            "/", 
            "", 
            false, // not secure for local development
            true // httponly
        );
    }
    
    // Also store in user meta if profile has linked user
    $user_id = get_post_meta($profile_id, "scn_user_id", true);
    if ($user_id) {
        update_user_meta($user_id, "scn_current_session_token", $session_token);
    }
}

/**
 * Check if profile is authenticated
 */
function scn_is_profile_authenticated() {
    // Check cookie first
    if (isset($_COOKIE["scn_session_token"])) {
        $session_token = sanitize_text_field($_COOKIE["scn_session_token"]);
        $session_data = get_transient("scn_session_" . $session_token);
        
        if ($session_data && $session_data["authenticated"]) {
            return true;
        }
    }
    
    // Fallback: check if user is logged in and has session token
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $session_token = get_user_meta($current_user->ID, "scn_current_session_token", true);
        
        if ($session_token) {
            $session_data = get_transient("scn_session_" . $session_token);
            if ($session_data && $session_data["authenticated"]) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Get current authenticated profile
 */
function scn_get_current_profile() {
    if (!scn_is_profile_authenticated()) {
        return null;
    }
    
    $profile_id = null;
    
    // Check cookie first
    if (isset($_COOKIE["scn_session_token"])) {
        $session_token = sanitize_text_field($_COOKIE["scn_session_token"]);
        $session_data = get_transient("scn_session_" . $session_token);
        
        if ($session_data && $session_data["authenticated"]) {
            $profile_id = $session_data["profile_id"];
        }
    }
    
    // Fallback: check user meta
    if (!$profile_id && is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $session_token = get_user_meta($current_user->ID, "scn_current_session_token", true);
        
        if ($session_token) {
            $session_data = get_transient("scn_session_" . $session_token);
            if ($session_data && $session_data["authenticated"]) {
                $profile_id = $session_data["profile_id"];
            }
        }
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
    // Clear cookie
    if (isset($_COOKIE["scn_session_token"])) {
        $session_token = sanitize_text_field($_COOKIE["scn_session_token"]);
        
        // Delete transient
        delete_transient("scn_session_" . $session_token);
        
        // Clear cookie
        if (!headers_sent()) {
            setcookie("scn_session_token", "", time() - 3600, "/");
        }
    }
    
    // Clear user meta if logged in
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        delete_user_meta($current_user->ID, "scn_current_session_token");
    }
}

/**
 * Get profile by ID
 */
function scn_get_profile($profile_id) {
    $profile = get_post($profile_id);
    if (!$profile || $profile->post_type !== "profile") {
        return null;
    }
    return $profile;
}

/**
 * Extend session (renew for another hour)
 */
function scn_extend_session() {
    if (isset($_COOKIE["scn_session_token"])) {
        $session_token = sanitize_text_field($_COOKIE["scn_session_token"]);
        $session_data = get_transient("scn_session_" . $session_token);
        
        if ($session_data && $session_data["authenticated"]) {
            // Extend the session
            set_transient("scn_session_" . $session_token, $session_data, 3600); // 1 hour
            
            // Update cookie
            if (!headers_sent()) {
                setcookie(
                    "scn_session_token", 
                    $session_token, 
                    time() + 3600, 
                    "/", 
                    "", 
                    false, 
                    true
                );
            }
        }
    }
}
