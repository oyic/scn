<?php
/**
 * Test URL Accessibility
 * This script tests if the authentication URLs are working
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "=== SCN Authentication URL Test ===\n\n";

// Test URLs
$test_urls = [
    '/member-login/' => 'Member Login Page',
    '/member-register/' => 'Member Registration Page',
    '/member-dashboard/' => 'Member Dashboard',
    '/test-auth/' => 'Test Authentication Page'
];

echo "Testing URL accessibility:\n";
echo str_repeat('-', 50) . "\n";

foreach ($test_urls as $url => $description) {
    $full_url = home_url($url);
    echo "Testing: $description\n";
    echo "URL: $full_url\n";
    
    // Check if rewrite rule exists
    $rules = get_option('rewrite_rules');
    $pattern = '^' . trim($url, '/') . '/?$';
    
    if (isset($rules[$pattern])) {
        echo "✓ Rewrite rule exists: $pattern -> " . $rules[$pattern] . "\n";
    } else {
        echo "✗ Rewrite rule missing for: $pattern\n";
    }
    
    echo "\n";
}

echo "Current rewrite rules related to SCN:\n";
echo str_repeat('-', 50) . "\n";

$rules = get_option('rewrite_rules');
$scn_rules = array_filter($rules, function($key) {
    return strpos($key, 'member-') !== false || strpos($key, 'scn_') !== false;
}, ARRAY_FILTER_USE_KEY);

if (empty($scn_rules)) {
    echo "No SCN-related rewrite rules found.\n";
} else {
    foreach ($scn_rules as $pattern => $replacement) {
        echo "$pattern -> $replacement\n";
    }
}

echo "\n=== Plugin Status ===\n";
echo "Plugin Active: " . (is_plugin_active('scn-membership/scn-membership.php') ? 'Yes' : 'No') . "\n";
echo "Auth Module Loaded: " . (class_exists('SCN\\Membership\\Modules\\Auth\\AuthModule') ? 'Yes' : 'No') . "\n";

echo "\n=== Recommendations ===\n";
echo "1. Go to WordPress Admin > Settings > Permalinks and click 'Save Changes'\n";
echo "2. Or run: wp rewrite flush\n";
echo "3. Or deactivate and reactivate the plugin\n";
echo "4. Or visit: " . admin_url('options-permalink.php') . "\n";

echo "\nDone!\n";
?>
