<?php
/**
 * Debug Template Loading
 * This will help us understand why the dashboard template isn't loading correctly
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>🔍 Debug Template Loading</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { color: orange; background: #fff8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Check if the dashboard template file exists and is readable
echo "<div class='info'>";
echo "<h2>📄 Template File Check</h2>";
echo "</div>";

$dashboard_template = SCN_MEMBERSHIP_PATH . 'templates/profiles/profile-dashboard.php';
echo "<p><strong>Template Path:</strong> $dashboard_template</p>";

if (file_exists($dashboard_template)) {
    echo "<div class='success'>";
    echo "<p>✅ Template file exists</p>";
    echo "</div>";
    
    if (is_readable($dashboard_template)) {
        echo "<div class='success'>";
        echo "<p>✅ Template file is readable</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<p>❌ Template file is not readable</p>";
        echo "</div>";
    }
    
    $file_size = filesize($dashboard_template);
    echo "<div class='info'>";
    echo "<p>File size: $file_size bytes</p>";
    echo "</div>";
    
} else {
    echo "<div class='error'>";
    echo "<p>❌ Template file does not exist</p>";
    echo "</div>";
}

// Check template syntax
echo "<div class='info'>";
echo "<h2>🔍 Template Syntax Check</h2>";
echo "</div>";

if (file_exists($dashboard_template)) {
    $template_content = file_get_contents($dashboard_template);
    
    // Check for PHP syntax errors
    $syntax_check = shell_exec("php -l " . escapeshellarg($dashboard_template) . " 2>&1");
    
    if (strpos($syntax_check, 'No syntax errors') !== false) {
        echo "<div class='success'>";
        echo "<p>✅ Template has no PHP syntax errors</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<p>❌ Template has PHP syntax errors:</p>";
        echo "<pre>$syntax_check</pre>";
        echo "</div>";
    }
    
    // Check for required functions and includes
    $required_checks = [
        'ABSPATH' => 'ABSPATH constant check',
        'get_header' => 'get_header() function',
        'get_footer' => 'get_footer() function',
        'scn_is_profile_authenticated' => 'Authentication function',
        'home_url' => 'home_url() function'
    ];
    
    foreach ($required_checks as $check => $description) {
        if (strpos($template_content, $check) !== false) {
            echo "<div class='success'>";
            echo "<p>✅ $description found in template</p>";
            echo "</div>";
        } else {
            echo "<div class='warning'>";
            echo "<p>⚠️ $description not found in template</p>";
            echo "</div>";
        }
    }
}

// Test template inclusion
echo "<div class='info'>";
echo "<h2>🧪 Test Template Inclusion</h2>";
echo "</div>";

if (file_exists($dashboard_template)) {
    echo "<div class='info'>";
    echo "<h3>Attempting to include template...</h3>";
    echo "</div>";
    
    // Set up authentication for testing
    require_once SCN_MEMBERSHIP_PATH . 'includes/profile-auth-functions.php';
    
    // Set up a test session
    $test_profile = get_posts([
        'post_type' => 'scn_profile',
        'meta_query' => [
            [
                'key' => 'scn_username',
                'value' => 'ezekiel',
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ]);
    
    if (!empty($test_profile)) {
        scn_set_profile_session($test_profile[0]->ID);
        echo "<div class='success'>";
        echo "<p>✅ Set test authentication session</p>";
        echo "</div>";
    }
    
    // Try to include the template
    ob_start();
    $error_occurred = false;
    $error_message = '';
    
    try {
        include $dashboard_template;
    } catch (ParseError $e) {
        $error_occurred = true;
        $error_message = "Parse Error: " . $e->getMessage();
    } catch (Error $e) {
        $error_occurred = true;
        $error_message = "Error: " . $e->getMessage();
    } catch (Exception $e) {
        $error_occurred = true;
        $error_message = "Exception: " . $e->getMessage();
    }
    
    $output = ob_get_clean();
    
    if ($error_occurred) {
        echo "<div class='error'>";
        echo "<h3>❌ Template inclusion failed</h3>";
        echo "<p>$error_message</p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ Template included successfully</h3>";
        echo "<p>Output length: " . strlen($output) . " characters</p>";
        echo "</div>";
        
        // Check if output contains expected content
        $expected_content = [
            'Profile Dashboard' => 'Dashboard title',
            'Welcome' => 'Welcome message',
            'Profile Information' => 'Profile info section',
            'Logout' => 'Logout button'
        ];
        
        foreach ($expected_content as $content => $description) {
            if (strpos($output, $content) !== false) {
                echo "<div class='success'>";
                echo "<p>✅ $description found in output</p>";
                echo "</div>";
            } else {
                echo "<div class='warning'>";
                echo "<p>⚠️ $description not found in output</p>";
                echo "</div>";
            }
        }
        
        // Show a preview of the output
        if (strlen($output) > 0) {
            echo "<div class='info'>";
            echo "<h3>Template Output Preview (first 1000 characters):</h3>";
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto; white-space: pre-wrap;'>";
            echo htmlspecialchars(substr($output, 0, 1000));
            if (strlen($output) > 1000) {
                echo "\n... (truncated)";
            }
            echo "</pre>";
            echo "</div>";
        }
    }
}

// Check WordPress template loading system
echo "<div class='info'>";
echo "<h2>🔧 WordPress Template Loading Check</h2>";
echo "</div>";

// Check if we're in a WordPress context
if (function_exists('get_header')) {
    echo "<div class='success'>";
    echo "<p>✅ WordPress functions available</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>❌ WordPress functions not available</p>";
    echo "</div>";
}

// Check current query vars
echo "<div class='info'>";
echo "<h3>Current Query Variables</h3>";
echo "</div>";

$query_vars = [
    'scn_member_dashboard' => get_query_var('scn_member_dashboard'),
    'scn_member_login' => get_query_var('scn_member_login'),
    'scn_member_logout' => get_query_var('scn_member_logout')
];

foreach ($query_vars as $var => $value) {
    if ($value) {
        echo "<div class='success'>";
        echo "<p>✅ $var = $value</p>";
        echo "</div>";
    } else {
        echo "<div class='info'>";
        echo "<p>ℹ️ $var = (empty)</p>";
        echo "</div>";
    }
}

// Check if we're on the dashboard URL
echo "<div class='info'>";
echo "<h3>URL Check</h3>";
echo "</div>";

$current_url = home_url('/member-dashboard/');
echo "<p><strong>Dashboard URL:</strong> <a href='$current_url' target='_blank'>$current_url</a></p>";

// Test the actual URL
echo "<div class='info'>";
echo "<h2>🌐 Live URL Test</h2>";
echo "</div>";

echo "<p>Try accessing the dashboard URL directly:</p>";
echo "<p><a href='$current_url' target='_blank' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Dashboard URL</a></p>";

echo "<hr>";
echo "<p><em>Template loading debug completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
