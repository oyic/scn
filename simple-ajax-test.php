<?php
/**
 * Simple AJAX Test
 * Test the AJAX endpoint with minimal code
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Add a simple AJAX handler for testing
add_action('wp_ajax_test_simple_ajax', function() {
    wp_send_json_success(['message' => 'Simple AJAX test works']);
});

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple AJAX Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Simple AJAX Test</h1>
    
    <div class="info">Testing basic AJAX functionality...</div>
    
    <button onclick="testSimpleAjax()" class="button">Test Simple AJAX</button>
    <button onclick="testMemberCoursesAjax()" class="button">Test Member Courses AJAX</button>
    
    <div id="result"></div>
    
    <script>
    function testSimpleAjax() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="info">Testing simple AJAX...</div>';
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=test_simple_ajax'
        })
        .then(response => {
            console.log('Simple AJAX Response status:', response.status);
            return response.text().then(text => {
                console.log('Simple AJAX Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Simple AJAX JSON parse error:', e);
                    throw new Error('Invalid JSON: ' + text.substring(0, 200));
                }
            });
        })
        .then(result => {
            console.log('Simple AJAX Result:', result);
            if (result.success) {
                resultDiv.innerHTML = '<div class="success">✅ Simple AJAX works: ' + result.data.message + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="error">❌ Simple AJAX failed: ' + (result.data || 'Unknown error') + '</div>';
            }
        })
        .catch(error => {
            console.error('Simple AJAX Error:', error);
            resultDiv.innerHTML = '<div class="error">❌ Simple AJAX Error: ' + error.message + '</div>';
        });
    }
    
    function testMemberCoursesAjax() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="info">Testing member courses AJAX...</div>';
        
        const formData = new FormData();
        formData.append('action', 'scn_get_member_courses');
        formData.append('profile_id', '561');
        formData.append('_wpnonce', '<?php echo wp_create_nonce('event_nonce'); ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Member Courses AJAX Response status:', response.status);
            return response.text().then(text => {
                console.log('Member Courses AJAX Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Member Courses AJAX JSON parse error:', e);
                    throw new Error('Invalid JSON: ' + text.substring(0, 200));
                }
            });
        })
        .then(result => {
            console.log('Member Courses AJAX Result:', result);
            if (result.success) {
                resultDiv.innerHTML = '<div class="success">✅ Member Courses AJAX works: ' + JSON.stringify(result.data) + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="error">❌ Member Courses AJAX failed: ' + (result.data || 'Unknown error') + '</div>';
            }
        })
        .catch(error => {
            console.error('Member Courses AJAX Error:', error);
            resultDiv.innerHTML = '<div class="error">❌ Member Courses AJAX Error: ' + error.message + '</div>';
        });
    }
    </script>
</body>
</html>
