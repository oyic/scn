<?php
/**
 * Flush Rewrite Rules Script
 * Run this to flush WordPress rewrite rules for the authentication URLs
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "Flushing rewrite rules...\n";

// Flush rewrite rules
flush_rewrite_rules(true);

echo "Rewrite rules flushed successfully!\n";
echo "\nAuthentication URLs should now be accessible:\n";
echo "- " . home_url('/member-login/') . "\n";
echo "- " . home_url('/member-register/') . "\n";
echo "- " . home_url('/member-dashboard/') . "\n";
echo "- " . home_url('/test-auth/') . "\n";

// Check if rewrite rules are working
$rules = get_option('rewrite_rules');
$auth_rules = [
    '^member-login/?$' => 'index.php?scn_member_login=1',
    '^member-register/?$' => 'index.php?scn_member_register=1',
    '^member-dashboard/?$' => 'index.php?scn_member_dashboard=1',
    '^test-auth/?$' => 'index.php?scn_test_auth=1'
];

echo "\nChecking rewrite rules...\n";
foreach ($auth_rules as $pattern => $replacement) {
    if (isset($rules[$pattern]) && $rules[$pattern] === $replacement) {
        echo "✓ $pattern -> $replacement\n";
    } else {
        echo "✗ Missing: $pattern -> $replacement\n";
        echo "  Found: " . (isset($rules[$pattern]) ? $rules[$pattern] : 'Not found') . "\n";
    }
}

echo "\nDone!\n";
?>
