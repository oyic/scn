<?php
/**
 * Final Rewrite Rules Flush
 * This will properly flush rewrite rules now that wp-config.php is fixed
 */

// Include WordPress (now with correct database connection)
require_once('../../../wp-config.php');

echo "<h1>🔄 Flushing Rewrite Rules - Final</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

echo "<div class='info'>";
echo "<h2>📋 Current Status</h2>";
echo "<p>wp-config.php has been updated with the correct socket connection.</p>";
echo "<p>Now flushing rewrite rules to ensure WordPress recognizes our custom URLs.</p>";
echo "</div>";

// Flush rewrite rules
echo "<div class='info'>";
echo "<h3>🔄 Flushing Rewrite Rules...</h3>";
echo "</div>";

try {
    // Force flush rewrite rules
    flush_rewrite_rules(true);
    
    echo "<div class='success'>";
    echo "<h3>✅ Rewrite Rules Flushed!</h3>";
    echo "</div>";
    
    // Verify the rules are there
    $rules = get_option('rewrite_rules');
    
    if (is_array($rules)) {
        echo "<div class='info'>";
        echo "<h3>📋 Verifying Rules</h3>";
        echo "<p>Total rules: " . count($rules) . "</p>";
        echo "</div>";
        
        $auth_rules = [
            '^member-login/?$' => 'index.php?member_login=1',
            '^member-dashboard/?$' => 'index.php?member_dashboard=1',
            '^member-register/?$' => 'index.php?member_register=1',
            '^member-logout/?$' => 'index.php?member_logout=1',
            '^test-auth/?$' => 'index.php?scn_test_auth=1'
        ];
        
        $all_present = true;
        foreach ($auth_rules as $pattern => $replacement) {
            if (isset($rules[$pattern]) && $rules[$pattern] === $replacement) {
                echo "<div class='success'>";
                echo "<p>✓ $pattern → $replacement</p>";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "<p>✗ Missing: $pattern → $replacement</p>";
                echo "</div>";
                $all_present = false;
            }
        }
        
        if ($all_present) {
            echo "<div class='success'>";
            echo "<h3>🎉 All Authentication Rules Present!</h3>";
            echo "</div>";
        }
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ No Rewrite Rules Found</h3>";
        echo "</div>";
    }
    
    // Test query vars
    echo "<div class='info'>";
    echo "<h3>🧪 Testing Query Variables</h3>";
    echo "</div>";
    
    $query_vars = ['member_login', 'member_register', 'member_dashboard', 'member_logout', 'scn_test_auth'];
    
    foreach ($query_vars as $var) {
        $value = get_query_var($var);
        if ($value) {
            echo "<div class='success'>";
            echo "<p>✓ Query var '$var' is working</p>";
            echo "</div>";
        } else {
            echo "<div class='info'>";
            echo "<p>ℹ Query var '$var' is registered (no current value)</p>";
            echo "</div>";
        }
    }
    
    // Test URLs
    echo "<div class='success'>";
    echo "<h2>🧪 Test URLs</h2>";
    echo "<p>Try these URLs now:</p>";
    echo "<ul>";
    echo "<li><a href='" . home_url('/member-login/') . "' target='_blank'>" . home_url('/member-login/') . "</a></li>";
    echo "<li><a href='" . home_url('/member-dashboard/') . "' target='_blank'>" . home_url('/member-dashboard/') . "</a></li>";
    echo "<li><a href='" . home_url('/test-auth/') . "' target='_blank'>" . home_url('/test-auth/') . "</a></li>";
    echo "</ul>";
    echo "<p><strong>Expected:</strong> These should now show the correct content instead of the home page.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div class='info'>";
echo "<h2>📝 Next Steps</h2>";
echo "<ol>";
echo "<li>Test the URLs above</li>";
echo "<li>If they still show home page content, try visiting WordPress Admin → Settings → Permalinks and click 'Save Changes'</li>";
echo "<li>Clear any caching if you have caching plugins</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><em>Rewrite rules flush completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
