<?php
/**
 * Create test courses for member 561
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

echo "<h1>Create Test Courses for Member</h1>";

$member_id = 561; // Boyic Alberto

// Sample course data
$courses_data = [
    [
        'subtitle' => 'Advanced Implant Dentistry',
        'description' => 'Master the latest techniques in dental implant surgery and restoration.',
        'ce_enabled' => 1,
        'ce_hours' => 8,
        'formats' => ['live', 'ondemand'],
        'outcomes' => [
            'Understand implant placement protocols',
            'Master bone grafting techniques',
            'Learn about immediate loading protocols'
        ],
        'ondemand_title' => 'Implant Mastery Course',
        'ondemand_school' => 'SCN Academy',
        'ondemand_link' => 'https://scn.example/courses/implants',
        'image_seed' => 100
    ],
    [
        'subtitle' => 'Cosmetic Dentistry Essentials',
        'description' => 'Learn comprehensive cosmetic dentistry procedures from veneers to smile design.',
        'ce_enabled' => 1,
        'ce_hours' => 6,
        'formats' => ['live'],
        'outcomes' => [
            'Master veneer preparation techniques',
            'Understand shade selection and matching',
            'Create comprehensive smile designs'
        ],
        'ondemand_title' => '',
        'ondemand_school' => '',
        'ondemand_link' => '',
        'image_seed' => 200
    ],
    [
        'subtitle' => 'Endodontics: Root Canal Therapy',
        'description' => 'Comprehensive training in modern endodontic techniques and materials.',
        'ce_enabled' => 1,
        'ce_hours' => 4,
        'formats' => ['ondemand'],
        'outcomes' => [
            'Perfect canal preparation techniques',
            'Master obturation methods',
            'Handle complex canal anatomy'
        ],
        'ondemand_title' => 'Endo Excellence',
        'ondemand_school' => 'Dental CE Center',
        'ondemand_link' => 'https://dentalce.example/endo',
        'image_seed' => null // No image - will use default
    ],
    [
        'subtitle' => 'Practice Management Fundamentals',
        'description' => 'Build and manage a successful dental practice with proven strategies.',
        'ce_enabled' => 0,
        'ce_hours' => 0,
        'formats' => ['live', 'ondemand'],
        'outcomes' => [
            'Develop effective marketing strategies',
            'Optimize team productivity',
            'Improve patient retention'
        ],
        'ondemand_title' => 'Practice Success',
        'ondemand_school' => 'Business Dental Institute',
        'ondemand_link' => 'https://business.example/practice',
        'image_seed' => 300
    ]
];

// Get or create topics
$topics_map = [
    'Implantology' => 'Advanced dental implant techniques and procedures',
    'Cosmetic Dentistry' => 'Aesthetic and cosmetic dental procedures',
    'Endodontics' => 'Root canal therapy and endodontic treatments',
    'Practice Management' => 'Business and management for dental practices'
];

$topic_ids = [];
foreach ($topics_map as $topic_name => $topic_desc) {
    $term = get_term_by('name', $topic_name, 'scn_topic');
    if (!$term) {
        $result = wp_insert_term($topic_name, 'scn_topic', ['description' => $topic_desc]);
        if (!is_wp_error($result)) {
            $topic_ids[$topic_name] = $result['term_id'];
            echo "<p>✅ Created topic: {$topic_name}</p>";
        }
    } else {
        $topic_ids[$topic_name] = $term->term_id;
        echo "<p>ℹ️ Topic already exists: {$topic_name}</p>";
    }
}

// Create default SCN placeholder image
function getDefaultCourseImage() {
    // Check if default image already exists
    $existing = get_posts([
        'post_type' => 'attachment',
        'meta_key' => 'scn_default_course_image',
        'meta_value' => '1',
        'posts_per_page' => 1
    ]);
    
    if (!empty($existing)) {
        return $existing[0]->ID;
    }
    
    // Create a simple placeholder using Lorem Picsum with SCN branding
    $image_url = 'https://picsum.photos/seed/scn-default/1200/800';
    $tmp = download_url($image_url);
    
    if (is_wp_error($tmp)) {
        return null;
    }
    
    $file_array = [
        'name' => 'scn-default-course-image.jpg',
        'tmp_name' => $tmp
    ];
    
    $attachment_id = media_handle_sideload($file_array, 0, 'SCN Default Course Image');
    @unlink($tmp);
    
    if (!is_wp_error($attachment_id)) {
        update_post_meta($attachment_id, 'scn_default_course_image', '1');
        update_post_meta($attachment_id, '_wp_attachment_image_alt', 'SCN Course');
        return $attachment_id;
    }
    
    return null;
}

$default_image_id = getDefaultCourseImage();
if ($default_image_id) {
    echo "<p>✅ Default course image ready (ID: {$default_image_id})</p>";
}

echo "<h2>Creating Courses...</h2>";

$created = 0;
$topic_names = array_keys($topics_map);

foreach ($courses_data as $index => $course) {
    // Create course post using ACF post type
    $course_id = wp_insert_post([
        'post_type' => 'course',
        'post_title' => $course['subtitle'],
        'post_status' => 'publish',
        'post_author' => $member_id
    ]);
    
    if (is_wp_error($course_id)) {
        echo "<p style='color:red;'>❌ Failed to create course: {$course['subtitle']}</p>";
        continue;
    }
    
    // Set course fields
    update_post_meta($course_id, 'course_subtitle', $course['subtitle']);
    update_post_meta($course_id, 'course_description', $course['description']);
    update_post_meta($course_id, 'course_ce_enabled', $course['ce_enabled']);
    update_post_meta($course_id, 'course_ce_hours', $course['ce_hours']);
    update_post_meta($course_id, 'course_formats', $course['formats']);
    update_post_meta($course_id, 'course_outcomes', $course['outcomes']);
    update_post_meta($course_id, 'scn_ondemand_title', $course['ondemand_title']);
    update_post_meta($course_id, 'scn_ondemand_school', $course['ondemand_school']);
    update_post_meta($course_id, 'scn_ondemand_link', $course['ondemand_link']);
    
    // Assign topic
    if (!empty($topic_names[$index])) {
        $topic_id = $topic_ids[$topic_names[$index]];
        wp_set_object_terms($course_id, $topic_id, 'scn_topic');
    }
    
    // Handle course image
    if ($course['image_seed'] !== null) {
        // Create unique course image
        $image_url = "https://picsum.photos/seed/course-{$course['image_seed']}/1200/800";
        $tmp = download_url($image_url);
        
        if (!is_wp_error($tmp)) {
            $file_array = [
                'name' => sanitize_title($course['subtitle']) . '.jpg',
                'tmp_name' => $tmp
            ];
            
            $attachment_id = media_handle_sideload($file_array, $course_id, $course['subtitle']);
            @unlink($tmp);
            
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($course_id, $attachment_id);
                echo "<p>✅ Created course with image: {$course['subtitle']} (ID: {$course_id})</p>";
            }
        }
    } else {
        // Use default image
        if ($default_image_id) {
            set_post_thumbnail($course_id, $default_image_id);
        }
        echo "<p>✅ Created course with default image: {$course['subtitle']} (ID: {$course_id})</p>";
    }
    
    $created++;
    sleep(1); // Rate limiting
}

echo "<h2>Complete!</h2>";
echo "<p>Created {$created} test courses for member {$member_id}</p>";
echo "<p><a href='/member/boyic-alberto-mba/#courses'>View Member Courses Tab</a></p>";

echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h1 { color: #333; }
h2 { color: #666; margin-top: 30px; }
</style>";

