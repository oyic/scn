<?php
/**
 * Fix Authentication URLs - WordPress Version
 * This script includes WordPress and flushes rewrite rules
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "=== SCN Authentication URL Fix ===\n\n";

// Check if we're in WordPress
if (!function_exists('get_option')) {
    echo "✗ WordPress not loaded properly\n";
    exit;
}

echo "✓ WordPress loaded successfully\n";

// Check current rewrite rules
$rules = get_option('rewrite_rules');
echo "Current rewrite rules: " . (is_array($rules) ? count($rules) : 'none') . " rules\n\n";

// Define our authentication rules
$auth_rules = [
    '^member-login/?$' => 'index.php?scn_member_login=1',
    '^member-register/?$' => 'index.php?scn_member_register=1',
    '^member-dashboard/?$' => 'index.php?scn_member_dashboard=1',
    '^member-logout/?$' => 'index.php?scn_member_logout=1',
    '^test-auth/?$' => 'index.php?scn_test_auth=1'
];

echo "Checking for SCN authentication rules:\n";
echo str_repeat('-', 50) . "\n";

$all_present = true;
foreach ($auth_rules as $pattern => $replacement) {
    if (is_array($rules) && isset($rules[$pattern]) && $rules[$pattern] === $replacement) {
        echo "✓ $pattern\n";
    } else {
        echo "✗ Missing: $pattern\n";
        $all_present = false;
    }
}

if (!$all_present || !is_array($rules)) {
    echo "\nFlushing rewrite rules...\n";
    
    // Force rewrite rules to be regenerated
    delete_option('rewrite_rules');
    
    // Add our rules manually
    add_rewrite_rule('^member-login/?$', 'index.php?scn_member_login=1', 'top');
    add_rewrite_rule('^member-register/?$', 'index.php?scn_member_register=1', 'top');
    add_rewrite_rule('^member-dashboard/?$', 'index.php?scn_member_dashboard=1', 'top');
    add_rewrite_rule('^member-logout/?$', 'index.php?scn_member_logout=1', 'top');
    add_rewrite_rule('^test-auth/?$', 'index.php?scn_test_auth=1', 'top');
    
    // Flush rewrite rules
    flush_rewrite_rules(true);
    
    echo "✓ Rewrite rules flushed and updated\n";
} else {
    echo "\n✓ All authentication rules are present!\n";
}

// Check if plugin is active
$active_plugins = get_option('active_plugins', []);
$scn_active = false;

foreach ($active_plugins as $plugin) {
    if (strpos($plugin, 'scn-membership') !== false) {
        $scn_active = true;
        echo "\n✓ SCN Membership plugin is active: $plugin\n";
        break;
    }
}

if (!$scn_active) {
    echo "\n⚠ SCN Membership plugin may not be active\n";
}

// Check if auth module is loaded
if (class_exists('SCN\\Membership\\Modules\\Auth\\AuthModule')) {
    echo "✓ Auth module is loaded\n";
} else {
    echo "⚠ Auth module not loaded\n";
}

echo "\n=== Test URLs ===\n";
echo "Try accessing these URLs in your browser:\n";
echo "- " . home_url('/member-login/') . "\n";
echo "- " . home_url('/member-register/') . "\n";
echo "- " . home_url('/member-dashboard/') . "\n";
echo "- " . home_url('/test-auth/') . "\n";

echo "\n=== If URLs Still Don't Work ===\n";
echo "1. Go to WordPress Admin > Settings > Permalinks\n";
echo "2. Click 'Save Changes' (don't change anything)\n";
echo "3. Or deactivate and reactivate the SCN Membership plugin\n";
echo "4. Check your .htaccess file has WordPress rewrite rules\n";

echo "\nDone!\n";
?>
