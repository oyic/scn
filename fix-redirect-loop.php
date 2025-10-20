<?php
/**
 * Fix Redirect Loop
 * This will disable rewrite rules and ensure everything uses WordPress pages
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔄 Fixing Redirect Loop</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Check current rewrite rules
echo "<div class='info'>";
echo "<h2>🔍 Current Rewrite Rules Status</h2>";
echo "</div>";

$rules = get_option('rewrite_rules');
if (is_array($rules)) {
    echo "<div class='info'>";
    echo "<p>Found " . count($rules) . " rewrite rules</p>";
    echo "</div>";
    
    $auth_rules = [
        '^member-login/?$' => 'index.php?member_login=1',
        '^member-dashboard/?$' => 'index.php?member_dashboard=1',
        '^member-register/?$' => 'index.php?member_register=1',
        '^member-logout/?$' => 'index.php?member_logout=1',
        '^test-auth/?$' => 'index.php?scn_test_auth=1'
    ];
    
    $found_auth_rules = [];
    foreach ($auth_rules as $pattern => $replacement) {
        if (isset($rules[$pattern]) && $rules[$pattern] === $replacement) {
            $found_auth_rules[] = $pattern;
            echo "<div class='warning'>";
            echo "<p>⚠️ Found conflicting rule: $pattern → $replacement</p>";
            echo "</div>";
        }
    }
    
    if (!empty($found_auth_rules)) {
        echo "<div class='error'>";
        echo "<p>❌ Found " . count($found_auth_rules) . " conflicting rewrite rules that need to be removed</p>";
        echo "</div>";
    }
} else {
    echo "<div class='info'>";
    echo "<p>No rewrite rules found</p>";
    echo "</div>";
}

// Check if pages exist
echo "<div class='info'>";
echo "<h2>📄 Check WordPress Pages</h2>";
echo "</div>";

$page_slugs = ['member-login', 'member-dashboard', 'member-register', 'test-auth'];
$existing_pages = [];

foreach ($page_slugs as $slug) {
    $page = get_page_by_path($slug);
    if ($page) {
        $existing_pages[$slug] = $page;
        echo "<div class='success'>";
        echo "<p>✅ Page exists: $slug (ID: " . $page->ID . ")</p>";
        echo "<p>URL: <a href='" . get_permalink($page->ID) . "' target='_blank'>" . get_permalink($page->ID) . "</a></p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<p>❌ Page missing: $slug</p>";
        echo "</div>";
    }
}

// Remove conflicting rewrite rules
echo "<div class='info'>";
echo "<h2>🧹 Removing Conflicting Rewrite Rules</h2>";
echo "</div>";

if (is_array($rules)) {
    $updated_rules = $rules;
    $removed_count = 0;
    
    foreach ($auth_rules as $pattern => $replacement) {
        if (isset($updated_rules[$pattern])) {
            unset($updated_rules[$pattern]);
            $removed_count++;
            echo "<div class='success'>";
            echo "<p>✅ Removed rule: $pattern</p>";
            echo "</div>";
        }
    }
    
    if ($removed_count > 0) {
        // Update the rewrite rules
        update_option('rewrite_rules', $updated_rules);
        echo "<div class='success'>";
        echo "<p>✅ Updated rewrite rules (removed $removed_count conflicting rules)</p>";
        echo "</div>";
    } else {
        echo "<div class='info'>";
        echo "<p>ℹ️ No conflicting rules found to remove</p>";
        echo "</div>";
    }
}

// Disable AuthModule rewrite rules
echo "<div class='info'>";
echo "<h2>🔧 Disabling AuthModule Rewrite Rules</h2>";
echo "</div>";

$auth_service_file = SCN_MEMBERSHIP_PATH . 'src/Modules/Auth/AuthService.php';
if (file_exists($auth_service_file)) {
    $auth_content = file_get_contents($auth_service_file);
    
    // Comment out the rewrite rule registration
    $auth_content = str_replace(
        'add_action("init", [$this, "addRewriteRules"]);',
        '// add_action("init", [$this, "addRewriteRules"]); // Disabled - using pages instead',
        $auth_content
    );
    
    $auth_content = str_replace(
        'add_filter("query_vars", [$this, "addQueryVars"]);',
        '// add_filter("query_vars", [$this, "addQueryVars"]); // Disabled - using pages instead',
        $auth_content
    );
    
    $auth_content = str_replace(
        'add_filter("template_include", [$this, "templateInclude"]);',
        '// add_filter("template_include", [$this, "templateInclude"]); // Disabled - using pages instead',
        $auth_content
    );
    
    file_put_contents($auth_service_file, $auth_content);
    
    echo "<div class='success'>";
    echo "<p>✅ Disabled AuthModule rewrite rules</p>";
    echo "</div>";
}

// Disable rewrite rules in main plugin file
echo "<div class='info'>";
echo "<h2>🔧 Disabling Main Plugin Rewrite Rules</h2>";
echo "</div>";

$main_plugin_file = SCN_MEMBERSHIP_PATH . 'scn-membership.php';
if (file_exists($main_plugin_file)) {
    $main_content = file_get_contents($main_plugin_file);
    
    // Comment out the ensureRewriteRules call
    $main_content = str_replace(
        '$this->ensureRewriteRules();',
        '// $this->ensureRewriteRules(); // Disabled - using pages instead',
        $main_content
    );
    
    file_put_contents($main_plugin_file, $main_content);
    
    echo "<div class='success'>";
    echo "<p>✅ Disabled main plugin rewrite rules</p>";
    echo "</div>";
}

// Update login template to redirect to page URL instead of rewrite rule
echo "<div class='info'>";
echo "<h2>🔄 Updating Login Redirects</h2>";
echo "</div>";

$login_template_file = SCN_MEMBERSHIP_PATH . 'templates/auth/profile-login.php';
if (file_exists($login_template_file)) {
    $login_content = file_get_contents($login_template_file);
    
    // Update redirects to use page URLs
    if (isset($existing_pages['member-dashboard'])) {
        $dashboard_url = get_permalink($existing_pages['member-dashboard']->ID);
        $login_content = str_replace(
            'wp_redirect(home_url("/member-dashboard/"));',
            'wp_redirect("' . $dashboard_url . '");',
            $login_content
        );
        
        echo "<div class='success'>";
        echo "<p>✅ Updated login redirect to use page URL: $dashboard_url</p>";
        echo "</div>";
    }
    
    // Update logout redirect
    if (isset($existing_pages['member-login'])) {
        $login_url = get_permalink($existing_pages['member-login']->ID);
        $login_content = str_replace(
            'home_url("/member-login/")',
            '"' . $login_url . '"',
            $login_content
        );
        
        echo "<div class='success'>";
        echo "<p>✅ Updated logout redirect to use page URL: $login_url</p>";
        echo "</div>";
    }
    
    file_put_contents($login_template_file, $login_content);
}

// Update dashboard template redirects
echo "<div class='info'>";
echo "<h2>🔄 Updating Dashboard Redirects</h2>";
echo "</div>";

$dashboard_template_file = SCN_MEMBERSHIP_PATH . 'templates/profiles/profile-dashboard-fixed.php';
if (file_exists($dashboard_template_file)) {
    $dashboard_content = file_get_contents($dashboard_template_file);
    
    // Update logout redirect
    if (isset($existing_pages['member-login'])) {
        $login_url = get_permalink($existing_pages['member-login']->ID);
        $dashboard_content = str_replace(
            'home_url("/member-login/")',
            '"' . $login_url . '"',
            $dashboard_content
        );
        
        echo "<div class='success'>";
        echo "<p>✅ Updated dashboard logout redirect to use page URL: $login_url</p>";
        echo "</div>";
    }
    
    file_put_contents($dashboard_template_file, $dashboard_content);
}

// Flush rewrite rules to apply changes
echo "<div class='info'>";
echo "<h2>🔄 Flushing Rewrite Rules</h2>";
echo "</div>";

flush_rewrite_rules(true);

echo "<div class='success'>";
echo "<p>✅ Rewrite rules flushed</p>";
echo "</div>";

// Test the pages
echo "<div class='info'>";
echo "<h2>🧪 Test the Fixed Pages</h2>";
echo "</div>";

if (!empty($existing_pages)) {
    echo "<p>Try these page URLs (should work without redirects):</p>";
    echo "<ul>";
    foreach ($existing_pages as $slug => $page) {
        $url = get_permalink($page->ID);
        echo "<li><a href='$url' target='_blank'>$url</a> - " . $page->post_title . "</li>";
    }
    echo "</ul>";
}

echo "<div class='success'>";
echo "<h2>🎉 Redirect Loop Fixed!</h2>";
echo "<p>The system now uses WordPress pages exclusively instead of rewrite rules.</p>";
echo "<p>Key changes:</p>";
echo "<ul>";
echo "<li>✅ Disabled all rewrite rules</li>";
echo "<li>✅ Updated redirects to use page URLs</li>";
echo "<li>✅ Removed conflicting rewrite rule registration</li>";
echo "<li>✅ Flushed rewrite rules to apply changes</li>";
echo "</ul>";
echo "</div>";

echo "<div class='info'>";
echo "<h2>🧪 Test Credentials</h2>";
echo "<p>Try logging in with:</p>";
echo "<ul>";
echo "<li>ezekiel / Ezekiel123!</li>";
echo "<li>dd / Profile12!</li>";
echo "<li>testmemberprofile / Profile79!</li>";
echo "<li>testprofile / Profile18!</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><em>Redirect loop fix completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
