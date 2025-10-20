<?php
/**
 * Minimal AJAX Test
 * Test AJAX with the absolute minimum code
 */

// Load WordPress
require_once('../../../wp-load.php');

// Add a minimal AJAX handler
add_action('wp_ajax_test_minimal', function() {
    wp_send_json_success(['test' => 'minimal ajax works']);
});

// Add the member courses AJAX handler with minimal code
add_action('wp_ajax_test_member_courses', function() {
    try {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
            return;
        }
        
        // Get profile_id from POST
        $profile_id = isset($_POST['profile_id']) ? intval($_POST['profile_id']) : 0;
        
        if ($profile_id <= 0) {
            wp_send_json_error('Invalid profile ID');
            return;
        }
        
        // Get member user ID
        $member_user_id = get_post_meta($profile_id, 'scn_user_id', true);
        
        if (!$member_user_id) {
            wp_send_json_error('Member not found');
            return;
        }
        
        // Get courses by post_author (simplified)
        $courses = get_posts([
            'post_type' => 'course',
            'posts_per_page' => 5,
            'author' => $member_user_id
        ]);
        
        $data = array_map(function($course) {
            return [
                'ID' => $course->ID,
                'post_title' => $course->post_title
            ];
        }, $courses);
        
        wp_send_json_success($data);
        
    } catch (Exception $e) {
        wp_send_json_error('Exception: ' . $e->getMessage());
    } catch (Error $e) {
        wp_send_json_error('Fatal Error: ' . $e->getMessage());
    }
});

?>
<!DOCTYPE html>
<html>
<head>
    <title>Minimal AJAX Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
    </style>
</head>
<body>
    <h1>Minimal AJAX Test</h1>
    
    <div class="info">Testing AJAX with minimal code...</div>
    
    <button onclick="testMinimal()" class="button">Test Minimal AJAX</button>
    <button onclick="testMemberCourses()" class="button">Test Member Courses (Minimal)</button>
    <button onclick="testOriginal()" class="button">Test Original Member Courses</button>
    
    <div id="result"></div>
    
    <script>
    function testMinimal() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="info">Testing minimal AJAX...</div>';
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=test_minimal'
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                resultDiv.innerHTML = '<div class="success">✅ Minimal AJAX works: ' + JSON.stringify(result.data) + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="error">❌ Minimal AJAX failed: ' + result.data + '</div>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="error">❌ Minimal AJAX Error: ' + error.message + '</div>';
        });
    }
    
    function testMemberCourses() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="info">Testing member courses (minimal)...</div>';
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=test_member_courses&profile_id=561'
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                resultDiv.innerHTML = '<div class="success">✅ Member Courses (minimal) works: ' + JSON.stringify(result.data) + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="error">❌ Member Courses (minimal) failed: ' + result.data + '</div>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="error">❌ Member Courses (minimal) Error: ' + error.message + '</div>';
        });
    }
    
    function testOriginal() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="info">Testing original member courses...</div>';
        
        const formData = new FormData();
        formData.append('action', 'scn_get_member_courses');
        formData.append('profile_id', '561');
        formData.append('_wpnonce', '<?php echo wp_create_nonce('event_nonce'); ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Original AJAX Response status:', response.status);
            return response.text().then(text => {
                console.log('Original AJAX Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid JSON: ' + text.substring(0, 200));
                }
            });
        })
        .then(result => {
            if (result.success) {
                resultDiv.innerHTML = '<div class="success">✅ Original Member Courses works: ' + JSON.stringify(result.data) + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="error">❌ Original Member Courses failed: ' + result.data + '</div>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="error">❌ Original Member Courses Error: ' + error.message + '</div>';
        });
    }
    </script>
</body>
</html>
