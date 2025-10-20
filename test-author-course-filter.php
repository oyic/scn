<?php
/**
 * Test Author-Course Filtering
 * This script helps debug the author-course filtering functionality
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
    <title>Test Author-Course Filtering</title>
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
    <h1>🧪 Test Author-Course Filtering</h1>
    
    <?php
    echo '<div class="info">Testing AJAX endpoint and field detection...</div>';
    
    // Test 1: Check if AJAX endpoint is registered
    echo '<h2>1. AJAX Endpoint Check</h2>';
    global $wp_filter;
    if (isset($wp_filter['wp_ajax_scn_get_member_courses'])) {
        echo '<div class="success">✅ AJAX endpoint "scn_get_member_courses" is registered</div>';
    } else {
        echo '<div class="error">❌ AJAX endpoint "scn_get_member_courses" is NOT registered</div>';
    }
    
    // Test 2: Check if ACF is available
    echo '<h2>2. ACF Availability</h2>';
    if (function_exists('acf_get_field_groups')) {
        echo '<div class="success">✅ ACF is available</div>';
    } else {
        echo '<div class="error">❌ ACF is not available</div>';
    }
    
    // Test 3: Check event field group
    echo '<h2>3. Event Field Group</h2>';
    $event_group = acf_get_field_group('group_scn_event_fields');
    if ($event_group) {
        echo '<div class="success">✅ Event field group found: ' . $event_group['title'] . '</div>';
        
        // Get fields
        $fields = acf_get_fields($event_group['key']);
        if ($fields) {
            echo '<div class="info">Fields in event group:</div>';
            echo '<ul>';
            foreach ($fields as $field) {
                echo '<li><strong>' . $field['label'] . '</strong> (name: ' . $field['name'] . ', key: ' . $field['key'] . ')</li>';
            }
            echo '</ul>';
        }
    } else {
        echo '<div class="error">❌ Event field group not found</div>';
    }
    
    // Test 4: Test AJAX endpoint manually
    echo '<h2>4. Test AJAX Endpoint</h2>';
    if (isset($_POST['test_ajax'])) {
        $profile_id = intval($_POST['profile_id']);
        
        // Simulate AJAX call
        $_POST['_wpnonce'] = wp_create_nonce('event_nonce');
        $_POST['profile_id'] = $profile_id;
        
        echo '<div class="info">Testing AJAX with profile_id: ' . $profile_id . '</div>';
        
        // Capture output
        ob_start();
        
        try {
            // Call the handler directly
            $instance = new SCN_Membership_Bootstrap();
            $instance->handleGetMemberCoursesAjax();
        } catch (Exception $e) {
            echo '<div class="error">❌ AJAX Error: ' . $e->getMessage() . '</div>';
        }
        
        $output = ob_get_clean();
        if ($output) {
            echo '<div class="info">AJAX Output:</div>';
            echo '<pre>' . esc_html($output) . '</pre>';
        }
    } else {
        echo '<form method="post">';
        echo '<label>Profile ID to test: <input type="number" name="profile_id" value="561" required></label><br><br>';
        echo '<button type="submit" name="test_ajax" class="button">Test AJAX Endpoint</button>';
        echo '</form>';
    }
    
    // Test 5: Check member courses
    echo '<h2>5. Check Member Courses</h2>';
    if (isset($_POST['check_courses'])) {
        $profile_id = intval($_POST['profile_id']);
        
        echo '<div class="info">Checking courses for profile_id: ' . $profile_id . '</div>';
        
        // Get member user ID
        $member_user_id = get_post_meta($profile_id, 'scn_user_id', true);
        echo '<div class="info">Member user ID: ' . $member_user_id . '</div>';
        
        // Get courses by author meta
        $courses_by_meta = get_posts([
            'post_type' => 'course',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'author',
                    'value' => $profile_id,
                    'compare' => 'LIKE'
                ]
            ]
        ]);
        
        echo '<div class="info">Courses by author meta: ' . count($courses_by_meta) . '</div>';
        if ($courses_by_meta) {
            echo '<ul>';
            foreach ($courses_by_meta as $course) {
                echo '<li>' . $course->post_title . ' (ID: ' . $course->ID . ')</li>';
            }
            echo '</ul>';
        }
        
        // Get courses by post_author
        if ($member_user_id) {
            $courses_by_author = get_posts([
                'post_type' => 'course',
                'posts_per_page' => -1,
                'author' => $member_user_id
            ]);
            
            echo '<div class="info">Courses by post_author: ' . count($courses_by_author) . '</div>';
            if ($courses_by_author) {
                echo '<ul>';
                foreach ($courses_by_author as $course) {
                    echo '<li>' . $course->post_title . ' (ID: ' . $course->ID . ')</li>';
                }
                echo '</ul>';
            }
        }
    } else {
        echo '<form method="post">';
        echo '<label>Profile ID to check: <input type="number" name="profile_id" value="561" required></label><br><br>';
        echo '<button type="submit" name="check_courses" class="button">Check Member Courses</button>';
        echo '</form>';
    }
    
    // Test 6: JavaScript console test
    echo '<h2>6. JavaScript Console Test</h2>';
    echo '<div class="info">Open browser console and run this JavaScript to test field detection:</div>';
    echo '<pre>
// Test ACF field detection
console.log("ACF available:", typeof acf !== "undefined");
if (typeof acf !== "undefined") {
    console.log("All fields:", acf.getFields());
    console.log("Author field:", acf.getField("author"));
    console.log("Courses field:", acf.getField("courses"));
}
    </pre>';
    
    echo '<div class="info">Then try changing the author field and check console for SCN messages.</div>';
    ?>
</div>
</body>
</html>
