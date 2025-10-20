<?php
/**
 * Import dental industry images from Unsplash to WordPress media library
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

// Load WordPress file handling functions
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

// You can get a free API key from https://unsplash.com/developers
$unsplash_access_key = 'YOUR_UNSPLASH_ACCESS_KEY'; // Replace with your key

// Or use Lorem Picsum for random images (no API key needed)
$use_lorem_picsum = true; // Set to true to use Lorem Picsum instead

echo "<h1>Import Dental Industry Images</h1>";

// Dental-related search terms
$dental_terms = [
    'dental clinic',
    'dentist',
    'dental office',
    'teeth',
    'dental hygiene',
    'dental equipment',
    'dental chair',
    'smile',
    'orthodontics',
    'dental care'
];

// Number of images to import per term
$images_per_term = 1;

echo "<h2>Starting Import...</h2>";

$imported = 0;
$failed = 0;

foreach ($dental_terms as $term) {
    echo "<h3>Fetching images for: {$term}</h3>";
    
    for ($i = 1; $i <= $images_per_term; $i++) {
        try {
            if ($use_lorem_picsum) {
                // Use Lorem Picsum for random images (no dental context but works without API)
                $random_id = rand(1, 1000);
                $image_url = "https://picsum.photos/seed/{$random_id}/1200/800";
                $filename = sanitize_title($term) . "-{$i}.jpg";
            } else {
                // Use Unsplash API (requires API key)
                if (empty($unsplash_access_key) || $unsplash_access_key === 'YOUR_UNSPLASH_ACCESS_KEY') {
                    echo "<p style='color:red;'>❌ Please set your Unsplash API key</p>";
                    break 2;
                }
                
                $api_url = "https://api.unsplash.com/search/photos?query=" . urlencode($term) . "&per_page=1&page={$i}&client_id={$unsplash_access_key}";
                $response = wp_remote_get($api_url);
                
                if (is_wp_error($response)) {
                    throw new Exception($response->get_error_message());
                }
                
                $body = json_decode(wp_remote_retrieve_body($response), true);
                
                if (empty($body['results'][0]['urls']['regular'])) {
                    echo "<p style='color:orange;'>⚠️ No image found for: {$term} #{$i}</p>";
                    continue;
                }
                
                $image_url = $body['results'][0]['urls']['regular'];
                $filename = sanitize_title($term) . "-{$i}.jpg";
            }
            
            // Download image
            $tmp = download_url($image_url);
            
            if (is_wp_error($tmp)) {
                throw new Exception($tmp->get_error_message());
            }
            
            // Prepare file array
            $file_array = [
                'name' => $filename,
                'tmp_name' => $tmp
            ];
            
            // Import to media library
            $attachment_id = media_handle_sideload($file_array, 0, "Dental: {$term}");
            
            // Clean up temp file
            @unlink($tmp);
            
            if (is_wp_error($attachment_id)) {
                throw new Exception($attachment_id->get_error_message());
            }
            
            // Set alt text
            update_post_meta($attachment_id, '_wp_attachment_image_alt', ucwords($term));
            
            echo "<p style='color:green;'>✅ Imported: {$filename} (ID: {$attachment_id})</p>";
            $imported++;
            
            // Rate limiting
            sleep(1);
            
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Failed to import {$term} #{$i}: {$e->getMessage()}</p>";
            $failed++;
        }
    }
}

echo "<h2>Import Complete</h2>";
echo "<p><strong>Imported:</strong> {$imported} images</p>";
echo "<p><strong>Failed:</strong> {$failed} images</p>";
echo "<p><a href='/wp-admin/upload.php'>View Media Library</a></p>";

echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h1 { color: #333; }
h2 { color: #666; margin-top: 30px; }
h3 { color: #999; margin-top: 20px; }
</style>";

