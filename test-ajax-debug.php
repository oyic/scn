<?php
/**
 * Test AJAX Debug
 * This script helps debug the AJAX 500 error
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test AJAX Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
<div class="container">
    <h1>🧪 Test AJAX Debug</h1>
    
    <?php
    echo '<div class="info">Testing AJAX endpoint directly...</div>';
    
    // Test 1: Check if user is logged in
    echo '<h2>1. User Status</h2>';
    if (is_user_logged_in()) {
        echo '<div class="success">✅ User is logged in (ID: ' . get_current_user_id() . ')</div>';
    } else {
        echo '<div class="error">❌ User is not logged in</div>';
    }
    
    // Test 2: Check AJAX action registration
    echo '<h2>2. AJAX Action Registration</h2>';
    global $wp_filter;
    if (isset($wp_filter['wp_ajax_scn_get_member_courses'])) {
        echo '<div class="success">✅ AJAX action "scn_get_member_courses" is registered</div>';
    } else {
        echo '<div class="error">❌ AJAX action "scn_get_member_courses" is NOT registered</div>';
    }
    
    // Test 3: Test nonce generation
    echo '<h2>3. Nonce Test</h2>';
    $nonce = wp_create_nonce('event_nonce');
    echo '<div class="info">Generated nonce: ' . $nonce . '</div>';
    
    if (wp_verify_nonce($nonce, 'event_nonce')) {
        echo '<div class="success">✅ Nonce verification works</div>';
    } else {
        echo '<div class="error">❌ Nonce verification failed</div>';
    }
    
    // Test 4: Test AJAX call directly
    echo '<h2>4. Direct AJAX Test</h2>';
    if (isset($_POST['test_direct_ajax'])) {
        $profile_id = intval($_POST['profile_id']);
        
        echo '<div class="info">Testing with profile_id: ' . $profile_id . '</div>';
        
        // Simulate AJAX call
        $_POST['_wpnonce'] = wp_create_nonce('event_nonce');
        $_POST['profile_id'] = $profile_id;
        
        echo '<div class="info">Simulating AJAX call...</div>';
        
        // Capture output
        ob_start();
        
        try {
            // Call the handler directly
            $instance = new SCN_Membership_Bootstrap();
            $instance->handleGetMemberCoursesAjax();
        } catch (Exception $e) {
            echo '<div class="error">❌ Exception: ' . $e->getMessage() . '</div>';
            echo '<div class="error">Trace: ' . $e->getTraceAsString() . '</div>';
        } catch (Error $e) {
            echo '<div class="error">❌ Fatal Error: ' . $e->getMessage() . '</div>';
            echo '<div class="error">Trace: ' . $e->getTraceAsString() . '</div>';
        }
        
        $output = ob_get_clean();
        if ($output) {
            echo '<div class="info">AJAX Output:</div>';
            echo '<pre>' . esc_html($output) . '</pre>';
        }
    } else {
        echo '<form method="post">';
        echo '<label>Profile ID to test: <input type="number" name="profile_id" value="561" required></label><br><br>';
        echo '<button type="submit" name="test_direct_ajax" class="button">Test Direct AJAX Call</button>';
        echo '</form>';
    }
    
    // Test 5: JavaScript AJAX test
    echo '<h2>5. JavaScript AJAX Test</h2>';
    echo '<div class="info">Click the button below to test AJAX via JavaScript:</div>';
    echo '<button onclick="testAjax()" class="button">Test JavaScript AJAX</button>';
    echo '<div id="ajax-result"></div>';
    
    // Test 6: Check error logs
    echo '<h2>6. Recent Error Logs</h2>';
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file)) {
        $logs = file_get_contents($log_file);
        $recent_logs = array_slice(explode("\n", $logs), -20);
        echo '<div class="info">Last 20 log entries:</div>';
        echo '<pre>' . esc_html(implode("\n", $recent_logs)) . '</pre>';
    } else {
        echo '<div class="info">No debug.log file found</div>';
    }
    ?>
    
    <script>
    function testAjax() {
        const resultDiv = document.getElementById('ajax-result');
        resultDiv.innerHTML = '<div class="info">Testing AJAX...</div>';
        
        const formData = new FormData();
        formData.append('action', 'scn_get_member_courses');
        formData.append('profile_id', '561');
        formData.append('_wpnonce', '<?php echo wp_create_nonce('event_nonce'); ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                }
            });
        })
        .then(result => {
            console.log('Parsed result:', result);
            if (result.success) {
                resultDiv.innerHTML = '<div class="success">✅ AJAX Success: ' + JSON.stringify(result.data) + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="error">❌ AJAX Error: ' + (result.data || 'Unknown error') + '</div>';
            }
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            resultDiv.innerHTML = '<div class="error">❌ AJAX Failed: ' + error.message + '</div>';
        });
    }
    </script>
</div>
</body>
</html>
