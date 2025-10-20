<?php
/**
 * Admin Page: Fix Member 561 Course Relationships
 * 
 * Add to WordPress admin as a tools page
 */

// Hook into admin menu
add_action('admin_menu', 'scn_add_fix_member_561_page');

function scn_add_fix_member_561_page() {
    add_management_page(
        'Fix Member 561 Courses',
        'Fix Member 561',
        'manage_options',
        'fix-member-561',
        'scn_render_fix_member_561_page'
    );
}

function scn_render_fix_member_561_page() {
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    
    $member_id = 561;
    $action_done = false;
    
    // Process form submission
    if (isset($_POST['fix_relationships']) && check_admin_referer('fix_member_561')) {
        $action_done = true;
        $results = scn_fix_member_561_relationships();
    }
    
    ?>
    <div class="wrap">
        <h1>Fix Member 561 Course Relationships</h1>
        
        <?php if ($action_done && !empty($results)): ?>
            <div class="notice notice-success">
                <p><strong>✅ Relationships Fixed!</strong></p>
                <?php echo $results; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Current Status</h2>
            <?php
            $member = get_post($member_id);
            $member_user_id = get_post_meta($member_id, 'scn_user_id', true);
            $current_courses = get_field('member_courses', $member_id);
            
            echo "<p><strong>Member:</strong> " . $member->post_title . " (ID: {$member_id})</p>";
            echo "<p><strong>User ID:</strong> " . ($member_user_id ?: 'Not set') . "</p>";
            echo "<p><strong>ACF Courses Field:</strong> " . (is_array($current_courses) ? count($current_courses) . " courses" : "Empty") . "</p>";
            
            if ($current_courses) {
                echo "<ul>";
                foreach ($current_courses as $course_id) {
                    $course = get_post($course_id);
                    if ($course) {
                        echo "<li>{$course->post_title} (ID: {$course_id})</li>";
                    }
                }
                echo "</ul>";
            }
            
            // Check courses by author
            if ($member_user_id) {
                $courses_by_author = get_posts([
                    'post_type' => 'course',
                    'author' => $member_user_id,
                    'posts_per_page' => -1
                ]);
                
                echo "<p><strong>Courses by WP Author:</strong> " . count($courses_by_author) . "</p>";
                if ($courses_by_author) {
                    echo "<ul>";
                    foreach ($courses_by_author as $course) {
                        $has_thumb = has_post_thumbnail($course->ID);
                        echo "<li>{$course->post_title} (ID: {$course->ID}) - Image: " . ($has_thumb ? '✓' : '✗') . "</li>";
                    }
                    echo "</ul>";
                }
            }
            ?>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Fix Relationships & Populate Course Data</h2>
            <p>This will:</p>
            <ul>
                <li>Sync the bi-directional relationship between Member 561 and their courses</li>
                <li>Update the ACF <code>member_courses</code> field on the member</li>
                <li>Update the ACF <code>author</code> field on each course</li>
                <li>Add featured images to courses that don't have them</li>
                <li>Fill in missing course fields (subtitle, CE hours, formats, etc.)</li>
            </ul>
            
            <form method="post">
                <?php wp_nonce_field('fix_member_561'); ?>
                <button type="submit" name="fix_relationships" class="button button-primary button-hero">
                    🔧 Fix Relationships & Populate Course Data
                </button>
            </form>
        </div>
    </div>
    
    <style>
        .card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; max-width: 1000px; }
        .card h2 { margin-top: 0; }
        .card ul { line-height: 1.8; }
        .button-hero { padding: 12px 36px !important; height: auto !important; font-size: 14px !important; }
    </style>
    <?php
}

function scn_fix_member_561_relationships() {
    $member_id = 561;
    $output = "";
    
    $member_user_id = get_post_meta($member_id, 'scn_user_id', true);
    
    // Get courses by author
    $courses_by_author = get_posts([
        'post_type' => 'course',
        'author' => $member_user_id,
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    $course_ids = [];
    foreach ($courses_by_author as $course) {
        $course_ids[] = $course->ID;
    }
    
    if (empty($course_ids)) {
        $output .= "<p>No courses found to sync.</p>";
        return $output;
    }
    
    // Update member's courses field
    update_field('member_courses', $course_ids, $member_id);
    $output .= "<p>✅ Updated member_courses field with " . count($course_ids) . " courses</p>";
    
    // Update each course's author field
    foreach ($course_ids as $course_id) {
        update_field('author', [$member_id], $course_id);
    }
    $output .= "<p>✅ Updated author field on " . count($course_ids) . " courses</p>";
    
    // Populate course images and fields
    $output .= "<h3>Course Updates:</h3><ul>";
    
    foreach ($course_ids as $course_id) {
        $course = get_post($course_id);
        $output .= "<li><strong>{$course->post_title}</strong><ul>";
        
        // Add featured image if missing
        if (!has_post_thumbnail($course_id)) {
            // Use placeholder image service
            $image_url = "https://picsum.photos/seed/course{$course_id}/800/450";
            
            $tmp = download_url($image_url);
            if (!is_wp_error($tmp)) {
                $file_array = [
                    'name' => 'course-' . $course_id . '-' . time() . '.jpg',
                    'tmp_name' => $tmp
                ];
                
                $attachment_id = media_handle_sideload($file_array, $course_id);
                
                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($course_id, $attachment_id);
                    $output .= "<li>✅ Added featured image</li>";
                } else {
                    @unlink($tmp);
                    $output .= "<li>⚠️ Could not add image</li>";
                }
            }
        } else {
            $output .= "<li>✓ Already has featured image</li>";
        }
        
        // Fill in missing fields
        if (!get_post_meta($course_id, 'course_subtitle', true)) {
            update_post_meta($course_id, 'course_subtitle', 'Professional Development Course');
            $output .= "<li>✅ Added subtitle</li>";
        }
        
        if (!get_post_meta($course_id, 'course_ce_enabled', true)) {
            update_post_meta($course_id, 'course_ce_enabled', 1);
            $output .= "<li>✅ Enabled CE credits</li>";
        }
        
        if (!get_post_meta($course_id, 'course_ce_hours', true)) {
            $ce_hours = rand(2, 8);
            update_post_meta($course_id, 'course_ce_hours', $ce_hours);
            $output .= "<li>✅ Added {$ce_hours} CE hours</li>";
        }
        
        if (!get_post_meta($course_id, 'course_formats', true)) {
            update_post_meta($course_id, 'course_formats', ['live', 'ondemand']);
            $output .= "<li>✅ Added course formats</li>";
        }
        
        $output .= "</ul></li>";
    }
    
    $output .= "</ul>";
    
    return $output;
}

