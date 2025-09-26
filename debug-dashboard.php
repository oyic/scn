<?php
/**
 * Debug Dashboard Issues
 * This will help us understand what's wrong with the dashboard loading
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔍 Debug Dashboard Issues</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { color: orange; background: #fff8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Include authentication functions
if (file_exists(SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions.php')) {
    require_once SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions.php';
    echo "<div class='success'>";
    echo "<p>✅ Profile authentication functions loaded</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ Profile authentication functions missing</p>";
    echo "</div>";
}

// Test authentication functions
echo "<div class='info'>";
echo "<h2>🧪 Test Authentication Functions</h2>";
echo "</div>";

if (function_exists('scn_is_profile_authenticated')) {
    $is_authenticated = scn_is_profile_authenticated();
    echo "<div class='info'>";
    echo "<p>Profile authenticated: " . ($is_authenticated ? 'Yes' : 'No') . "</p>";
    echo "</div>";
    
    if ($is_authenticated && function_exists('scn_get_current_profile')) {
        $profile = scn_get_current_profile();
        if ($profile) {
            echo "<div class='success'>";
            echo "<p>✅ Current profile: " . $profile->post_title . " (ID: " . $profile->ID . ")</p>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<p>❌ No current profile found</p>";
            echo "</div>";
        }
    }
} else {
    echo "<div class='error'>";
    echo "<p>❌ scn_is_profile_authenticated function not available</p>";
    echo "</div>";
}

// Check dashboard template file
echo "<div class='info'>";
echo "<h2>📄 Check Dashboard Template</h2>";
echo "</div>";

$dashboard_template = SCN_MEMBERSHIP_PATH . 'templates/profiles/profile-dashboard.php';
if (file_exists($dashboard_template)) {
    echo "<div class='success'>";
    echo "<p>✅ Dashboard template exists: $dashboard_template</p>";
    echo "</div>";
    
    // Check template content
    $template_content = file_get_contents($dashboard_template);
    if (strpos($template_content, 'scn_is_profile_authenticated') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ Template uses authentication functions</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠️ Template doesn't use authentication functions</p>";
        echo "</div>";
    }
} else {
    echo "<div class='error'>";
    echo "<p>❌ Dashboard template missing: $dashboard_template</p>";
    echo "</div>";
}

// Check AuthService template mapping
echo "<div class='info'>";
echo "<h2>🔧 Check AuthService Template Mapping</h2>";
echo "</div>";

$auth_service_file = SCN_MEMBERSHIP_PATH . 'src/Modules/Auth/AuthService.php';
if (file_exists($auth_service_file)) {
    $content = file_get_contents($auth_service_file);
    
    if (strpos($content, 'profile-dashboard.php') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ AuthService maps to profile-dashboard.php</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠️ AuthService doesn't map to profile-dashboard.php</p>";
        echo "</div>";
        
        // Show what it's actually mapping to
        if (preg_match('/return SCN_MEMBERSHIP_PATH \. \'([^\']+)\';/', $content, $matches)) {
            echo "<p>Currently mapping to: " . $matches[1] . "</p>";
        }
    }
} else {
    echo "<div class='error'>";
    echo "<p>❌ AuthService file missing</p>";
    echo "</div>";
}

// Test direct dashboard loading
echo "<div class='info'>";
echo "<h2>🧪 Test Direct Dashboard Loading</h2>";
echo "</div>";

// Simulate what happens when dashboard loads
echo "<div class='info'>";
echo "<h3>Simulating dashboard load...</h3>";
echo "</div>";

// Check if we can include the template directly
if (file_exists($dashboard_template)) {
    echo "<div class='info'>";
    echo "<h3>Attempting to include template...</h3>";
    echo "</div>";
    
    // Capture any errors
    ob_start();
    $error_occurred = false;
    
    try {
        // Set up a fake profile session for testing
        if (function_exists('scn_set_profile_session')) {
            // Find the dd profile
            $dd_profile = get_posts([
                'post_type' => 'scn_profile',
                'meta_query' => [
                    [
                        'key' => 'scn_username',
                        'value' => 'dd',
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1,
                'post_status' => 'publish'
            ]);
            
            if (!empty($dd_profile)) {
                scn_set_profile_session($dd_profile[0]->ID);
                echo "<div class='success'>";
                echo "<p>✅ Set test session for profile: " . $dd_profile[0]->post_title . "</p>";
                echo "</div>";
            }
        }
        
        // Try to include the template
        include $dashboard_template;
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<p>❌ Error including template: " . $e->getMessage() . "</p>";
        echo "</div>";
        $error_occurred = true;
    }
    
    $output = ob_get_clean();
    
    if ($error_occurred) {
        echo "<div class='error'>";
        echo "<p>❌ Template inclusion failed</p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<p>✅ Template included successfully</p>";
        echo "<p>Output length: " . strlen($output) . " characters</p>";
        echo "</div>";
        
        // Show first part of output
        if (strlen($output) > 0) {
            echo "<div class='info'>";
            echo "<h3>Template Output Preview:</h3>";
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;'>";
            echo htmlspecialchars(substr($output, 0, 500));
            if (strlen($output) > 500) {
                echo "\n... (truncated)";
            }
            echo "</pre>";
            echo "</div>";
        }
    }
}

// Check for common issues
echo "<div class='info'>";
echo "<h2>🔍 Common Issues Check</h2>";
echo "</div>";

// Check if get_header() and get_footer() are available
if (function_exists('get_header')) {
    echo "<div class='success'>";
    echo "<p>✅ get_header() function available</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ get_header() function not available</p>";
    echo "</div>";
}

if (function_exists('get_footer')) {
    echo "<div class='success'>";
    echo "<p>✅ get_footer() function available</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ get_footer() function not available</p>";
    echo "</div>";
}

// Check if SCN_MEMBERSHIP_PATH is defined
if (defined('SCN_MEMBERSHIP_PATH')) {
    echo "<div class='success'>";
    echo "<p>✅ SCN_MEMBERSHIP_PATH defined: " . SCN_MEMBERSHIP_PATH . "</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ SCN_MEMBERSHIP_PATH not defined</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><em>Dashboard debug completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
