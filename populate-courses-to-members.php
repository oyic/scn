<?php
/**
 * Populate courses with field data and assign randomly to members
 */

require_once(__DIR__ . '/../../../wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

echo "<h1>Populate Courses and Assign to Members</h1>";

// Get all members
$members = get_posts([
    'post_type' => 'member',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

if (empty($members)) {
    echo "<p style='color:red;'>❌ No members found</p>";
    exit;
}

echo "<p>Found " . count($members) . " members</p>";

// Sample course data
$course_templates = [
    [
        'title' => 'Advanced Dental Implantology',
        'subtitle' => 'Master surgical implant placement',
        'description' => 'Comprehensive training in dental implant surgery, from diagnosis to final restoration. Learn surgical techniques, bone grafting, and immediate loading protocols.',
        'ce_enabled' => 1,
        'ce_hours' => 8,
        'formats' => ['live', 'ondemand'],
        'outcomes' => [
            'Master implant placement in various bone types',
            'Understand bone grafting and augmentation procedures',
            'Learn immediate loading protocols and criteria'
        ],
        'topic' => 'Implantology'
    ],
    [
        'title' => 'Cosmetic Dentistry Excellence',
        'subtitle' => 'Create beautiful, natural smiles',
        'description' => 'Learn the art and science of cosmetic dentistry including veneers, bonding, and smile design using digital tools.',
        'ce_enabled' => 1,
        'ce_hours' => 6,
        'formats' => ['live'],
        'outcomes' => [
            'Master veneer preparation and cementation',
            'Perfect shade selection and matching techniques',
            'Use digital smile design software effectively'
        ],
        'topic' => 'Cosmetic Dentistry'
    ],
    [
        'title' => 'Modern Endodontics',
        'subtitle' => 'Contemporary root canal therapy',
        'description' => 'Up-to-date endodontic procedures using rotary instruments, bioceramic materials, and advanced irrigation techniques.',
        'ce_enabled' => 1,
        'ce_hours' => 5,
        'formats' => ['ondemand'],
        'outcomes' => [
            'Master rotary instrumentation techniques',
            'Understand bioceramic obturation materials',
            'Handle complex canal anatomy and calcifications'
        ],
        'topic' => 'Endodontics'
    ],
    [
        'title' => 'Periodontal Surgery Techniques',
        'subtitle' => 'Surgical management of periodontal disease',
        'description' => 'Learn surgical approaches to treating periodontal disease including flap surgery, bone grafting, and regenerative procedures.',
        'ce_enabled' => 1,
        'ce_hours' => 7,
        'formats' => ['live', 'ondemand'],
        'outcomes' => [
            'Perform periodontal flap surgery',
            'Master guided tissue regeneration',
            'Understand bone grafting materials and techniques'
        ],
        'topic' => 'Periodontics'
    ],
    [
        'title' => 'Digital Dentistry Workflow',
        'subtitle' => 'Integrate digital technology in practice',
        'description' => 'Comprehensive guide to digital dentistry including intraoral scanning, CAD/CAM, and 3D printing applications.',
        'ce_enabled' => 1,
        'ce_hours' => 4,
        'formats' => ['ondemand'],
        'outcomes' => [
            'Master intraoral scanning techniques',
            'Design restorations using CAD software',
            'Understand 3D printing applications in dentistry'
        ],
        'topic' => 'Digital Dentistry'
    ],
    [
        'title' => 'Pediatric Dentistry Essentials',
        'subtitle' => 'Treating young patients effectively',
        'description' => 'Learn behavior management, preventive care, and restorative techniques specifically for pediatric patients.',
        'ce_enabled' => 1,
        'ce_hours' => 5,
        'formats' => ['live'],
        'outcomes' => [
            'Master behavior management techniques',
            'Perform pulp therapy on primary teeth',
            'Implement preventive care protocols'
        ],
        'topic' => 'Pediatric Dentistry'
    ],
    [
        'title' => 'Practice Management & Growth',
        'subtitle' => 'Build a thriving dental practice',
        'description' => 'Business strategies for practice success including marketing, team management, and patient retention.',
        'ce_enabled' => 0,
        'ce_hours' => 0,
        'formats' => ['live', 'ondemand'],
        'outcomes' => [
            'Develop effective marketing strategies',
            'Build and manage high-performing teams',
            'Implement patient retention systems'
        ],
        'topic' => 'Practice Management'
    ],
    [
        'title' => 'Orthodontics for General Practitioners',
        'subtitle' => 'Clear aligner therapy basics',
        'description' => 'Introduction to clear aligner orthodontics for general dentists including case selection and treatment planning.',
        'ce_enabled' => 1,
        'ce_hours' => 6,
        'formats' => ['ondemand'],
        'outcomes' => [
            'Select appropriate cases for aligner therapy',
            'Create effective treatment plans',
            'Monitor treatment progress and refinements'
        ],
        'topic' => 'Orthodontics'
    ]
];

// Ensure topics exist
$topic_ids = [];
$unique_topics = array_unique(array_column($course_templates, 'topic'));

foreach ($unique_topics as $topic_name) {
    $term = get_term_by('name', $topic_name, 'scn_topic');
    if (!$term) {
        $result = wp_insert_term($topic_name, 'scn_topic');
        if (!is_wp_error($result)) {
            $topic_ids[$topic_name] = $result['term_id'];
        }
    } else {
        $topic_ids[$topic_name] = $term->term_id;
    }
}

// Get or create default course image
$default_image_id = null;
$existing_default = get_posts([
    'post_type' => 'attachment',
    'meta_key' => 'scn_default_course_image',
    'meta_value' => '1',
    'posts_per_page' => 1
]);

if (!empty($existing_default)) {
    $default_image_id = $existing_default[0]->ID;
} else {
    $image_url = 'https://picsum.photos/seed/scn-default/1200/800';
    $tmp = download_url($image_url);
    if (!is_wp_error($tmp)) {
        $file_array = ['name' => 'scn-default-course.jpg', 'tmp_name' => $tmp];
        $default_image_id = media_handle_sideload($file_array, 0, 'SCN Default Course Image');
        @unlink($tmp);
        if (!is_wp_error($default_image_id)) {
            update_post_meta($default_image_id, 'scn_default_course_image', '1');
        }
    }
}

echo "<h2>Creating Courses...</h2>";

$created_courses = [];
foreach ($course_templates as $index => $course_data) {
    $course_id = wp_insert_post([
        'post_type' => 'course',
        'post_title' => $course_data['title'],
        'post_status' => 'publish',
        'post_author' => 1
    ]);
    
    if (is_wp_error($course_id)) {
        echo "<p style='color:red;'>❌ Failed: {$course_data['title']}</p>";
        continue;
    }
    
    // Set course fields
    update_post_meta($course_id, 'course_subtitle', $course_data['subtitle']);
    update_post_meta($course_id, 'course_description', $course_data['description']);
    update_post_meta($course_id, 'course_ce_enabled', $course_data['ce_enabled']);
    update_post_meta($course_id, 'course_ce_hours', $course_data['ce_hours']);
    update_post_meta($course_id, 'course_formats', $course_data['formats']);
    update_post_meta($course_id, 'course_outcomes', $course_data['outcomes']);
    
    // Set topic
    if (isset($topic_ids[$course_data['topic']])) {
        wp_set_object_terms($course_id, $topic_ids[$course_data['topic']], 'scn_topic');
    }
    
    // Create unique course image or use default
    if ($index < 5) {
        $image_url = "https://picsum.photos/seed/course-{$index}/1200/800";
        $tmp = download_url($image_url);
        if (!is_wp_error($tmp)) {
            $file_array = ['name' => sanitize_title($course_data['title']) . '.jpg', 'tmp_name' => $tmp];
            $attachment_id = media_handle_sideload($file_array, $course_id);
            @unlink($tmp);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($course_id, $attachment_id);
            }
        }
    } else if ($default_image_id) {
        set_post_thumbnail($course_id, $default_image_id);
    }
    
    $created_courses[] = $course_id;
    echo "<p>✅ Created: {$course_data['title']} (ID: {$course_id})</p>";
    sleep(1);
}

echo "<h2>Assigning Courses to Members...</h2>";

// Shuffle courses for random distribution
shuffle($created_courses);

// Assign courses to members randomly
foreach ($members as $member) {
    // Randomly assign 1-3 courses per member
    $num_courses = rand(1, 3);
    $member_course_ids = array_slice($created_courses, 0, $num_courses);
    
    // Rotate the array so next member gets different courses
    $created_courses = array_merge(array_slice($created_courses, $num_courses), array_slice($created_courses, 0, $num_courses));
    
    // Use ACF relationship field
    update_field('member_courses', $member_course_ids, $member->ID);
    
    echo "<p>✅ Assigned {$num_courses} courses to: {$member->post_title}</p>";
}

echo "<h2>Complete!</h2>";
echo "<p style='color:green;'>✅ Created " . count($course_templates) . " courses</p>";
echo "<p style='color:green;'>✅ Assigned courses to " . count($members) . " members</p>";
echo "<p><a href='/wp-admin/edit.php?post_type=course'>View All Courses</a></p>";

echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
h1 { color: #333; }
h2 { color: #666; margin-top: 30px; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
p { line-height: 1.6; }
</style>";

