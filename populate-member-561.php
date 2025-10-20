<?php
/**
 * Populate member 561 with courses and videos
 */

require_once(__DIR__ . '/../../../wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

echo "<h1>Populate Member 561 with Courses & Videos</h1>";

$member_id = 561;

// Real YouTube/Vimeo videos
$videos = [
    [
        'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'title' => 'Introduction to Modern Dental Techniques',
        'thumbnail' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg'
    ],
    [
        'url' => 'https://vimeo.com/148751763',
        'title' => 'Advanced Implant Surgery Workshop',
        'thumbnail' => 'https://i.vimeocdn.com/video/556166838-1920x1080.jpg'
    ],
    [
        'url' => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
        'title' => 'Cosmetic Dentistry Masterclass',
        'thumbnail' => 'https://img.youtube.com/vi/jNQXAC9IVRw/maxresdefault.jpg'
    ]
];

echo "<h2>Adding Videos to Member Gallery...</h2>";

// Get existing gallery
$gallery_images = get_field('gallery_images', $member_id);
if (!is_array($gallery_images)) {
    $gallery_images = [];
}

// Add video thumbnails as gallery images
foreach ($videos as $video) {
    // Download thumbnail
    $tmp = download_url($video['thumbnail']);
    if (is_wp_error($tmp)) {
        echo "<p style='color:orange;'>⚠️ Skipped video thumbnail: {$video['title']}</p>";
        continue;
    }
    
    $file_array = [
        'name' => sanitize_title($video['title']) . '.jpg',
        'tmp_name' => $tmp
    ];
    
    $attachment_id = media_handle_sideload($file_array, $member_id, $video['title']);
    @unlink($tmp);
    
    if (!is_wp_error($attachment_id)) {
        $gallery_images[] = $attachment_id;
        echo "<p>✅ Added video thumbnail: {$video['title']}</p>";
    }
}

// Update gallery
update_field('gallery_images', $gallery_images, $member_id);

// Add videos to member_videos ACF repeater field
$member_videos_data = [];
foreach ($videos as $video) {
    $member_videos_data[] = [
        'title' => $video['title'],
        'url' => $video['url'],
        'thumbnail' => $video['thumbnail']
    ];
}
update_field('member_videos', $member_videos_data, $member_id);

echo "<p style='color:green;'>✅ Updated gallery with " . count($videos) . " video thumbnails</p>";
echo "<p style='color:green;'>✅ Added " . count($videos) . " videos to member_videos field</p>";

// Create sample courses
echo "<h2>Creating Sample Courses...</h2>";

$courses = [
    [
        'title' => 'Dental Implant Surgery Fundamentals',
        'subtitle' => 'Master the basics of dental implant placement',
        'description' => 'Learn essential techniques for successful dental implant surgery, from case selection to final restoration.',
        'outcomes' => [
            'Proper case selection and treatment planning',
            'Surgical placement techniques',
            'Handling complications'
        ]
    ],
    [
        'title' => 'Cosmetic Dentistry Excellence',
        'subtitle' => 'Advanced aesthetic procedures',
        'description' => 'Comprehensive training in veneers, bonding, and smile design for natural-looking results.',
        'outcomes' => [
            'Veneer preparation and cementation',
            'Shade matching and selection',
            'Digital smile design'
        ]
    ],
    [
        'title' => 'Endodontics: Modern Root Canal Therapy',
        'subtitle' => 'Contemporary endo techniques',
        'description' => 'Up-to-date endodontic procedures using the latest materials and technologies.',
        'outcomes' => [
            'Canal preparation with rotary instruments',
            'Bioceramic obturation',
            'Managing complex anatomy'
        ]
    ]
];

$created_course_ids = [];

foreach ($courses as $course_data) {
    // Create course
    $course_id = wp_insert_post([
        'post_type' => 'course',
        'post_title' => $course_data['title'],
        'post_status' => 'publish',
        'post_author' => 1 // Set to admin initially
    ]);
    
    if (is_wp_error($course_id)) {
        echo "<p style='color:red;'>❌ Failed: {$course_data['title']}</p>";
        continue;
    }
    
    // Set course meta
    update_post_meta($course_id, 'course_subtitle', $course_data['subtitle']);
    update_post_meta($course_id, 'course_description', $course_data['description']);
    update_post_meta($course_id, 'course_outcomes', $course_data['outcomes']);
    update_post_meta($course_id, 'course_ce_enabled', 1);
    update_post_meta($course_id, 'course_ce_hours', rand(4, 8));
    update_post_meta($course_id, 'course_formats', ['live', 'ondemand']);
    
    $created_course_ids[] = $course_id;
    echo "<p>✅ Created: {$course_data['title']} (ID: {$course_id})</p>";
}

// Link courses to member via ACF relationship
update_field('member_courses', $created_course_ids, $member_id);

echo "<h2>Complete!</h2>";
echo "<p style='color:green;'>✅ Created " . count($created_course_ids) . " courses</p>";
echo "<p style='color:green;'>✅ Linked courses to member {$member_id}</p>";
echo "<p style='color:green;'>✅ Added " . count($videos) . " video thumbnails to gallery</p>";
echo "<p><a href='/member/boyic-alberto-mba/'>View Member Profile</a> | <a href='/member/boyic-alberto-mba/#courses'>View Courses Tab</a></p>";

echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h1 { color: #333; }
h2 { color: #666; margin-top: 30px; }
</style>";

