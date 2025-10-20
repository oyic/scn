<?php
/**
 * AJAX handlers for media operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class SCN_Media_Ajax_Handlers {
    
    public function __construct() {
        // Gallery operations
        add_action('wp_ajax_save_gallery_images', [$this, 'save_gallery_images']);
        add_action('wp_ajax_update_gallery_images', [$this, 'update_gallery_images']);
        add_action('wp_ajax_remove_gallery_image', [$this, 'remove_gallery_image']);
        
        // Press kit operations
        add_action('wp_ajax_save_press_kit_files', [$this, 'save_press_kit_files']);
        add_action('wp_ajax_remove_press_kit_file', [$this, 'remove_press_kit_file']);
        
        // Video operations
        add_action('wp_ajax_add_member_video', [$this, 'add_member_video']);
        add_action('wp_ajax_remove_member_video', [$this, 'remove_member_video']);
    }
    
    /**
     * Save gallery images
     */
    public function save_gallery_images() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'save_gallery_images')) {
            wp_die('Security check failed');
        }
        
        $profile_id = intval($_POST['profile_id']);
        
        // Check permissions
        if (!$this->can_edit_profile($profile_id)) {
            wp_send_json_error('You do not have permission to edit this profile');
        }
        
        if (!isset($_FILES['gallery_images']) || empty($_FILES['gallery_images']['name'][0])) {
            wp_send_json_error('No images provided');
        }
        
        $uploaded_ids = [];
        $files = $_FILES['gallery_images'];
        
        // Handle multiple file uploads
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                $attachment_id = $this->handle_file_upload($file, $profile_id, 'gallery');
                if ($attachment_id) {
                    $uploaded_ids[] = $attachment_id;
                }
            }
        }
        
        if (empty($uploaded_ids)) {
            wp_send_json_error('Failed to upload any images');
        }
        
        // Get existing gallery images
        $existing_images = get_field('gallery_images', $profile_id);
        if (!is_array($existing_images)) {
            $existing_images = [];
        }
        
        // Add new images to existing ones
        $all_images = array_merge($existing_images, $uploaded_ids);
        
        // Update ACF field
        update_field('gallery_images', $all_images, $profile_id);
        
        wp_send_json_success([
            'message' => 'Gallery images saved successfully',
            'uploaded_count' => count($uploaded_ids)
        ]);
    }
    
    /**
     * Update gallery images (from WP media library)
     */
    public function update_gallery_images() {
        error_log('=== update_gallery_images AJAX handler called ===');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Verify nonce
        if (!isset($_POST['_wpnonce'])) {
            error_log('ERROR: No nonce provided');
            wp_send_json_error('No nonce provided');
            return;
        }
        
        if (!wp_verify_nonce($_POST['_wpnonce'], 'update_gallery_images')) {
            error_log('ERROR: Nonce verification failed');
            wp_send_json_error('Security check failed');
            return;
        }
        
        error_log('Nonce verified successfully');
        
        $profile_id = intval($_POST['profile_id']);
        
        // Check permissions
        if (!$this->can_edit_profile($profile_id)) {
            wp_send_json_error('You do not have permission to edit this profile');
        }
        
        // Get image IDs from JSON
        $image_ids = isset($_POST['image_ids']) ? json_decode(stripslashes($_POST['image_ids']), true) : [];
        
        if (!is_array($image_ids)) {
            $image_ids = [];
        }
        
        // Convert to integers and filter out invalid IDs
        $image_ids = array_map('intval', $image_ids);
        $image_ids = array_filter($image_ids, function($id) {
            return $id > 0;
        });
        
        // Get previous gallery images to determine which are new
        $existing_images = get_field('gallery_images', $profile_id);
        if (!is_array($existing_images)) {
            $existing_images = [];
        }
        
        // Extract IDs from existing images (they might be arrays)
        $existing_ids = array_map(function($img) {
            if (is_array($img)) {
                return isset($img['ID']) ? intval($img['ID']) : (isset($img['id']) ? intval($img['id']) : 0);
            }
            return intval($img);
        }, $existing_images);
        
        // Find new images that need renaming
        $new_image_ids = array_diff($image_ids, $existing_ids);
        
        // Update ACF field with the ordered image IDs
        error_log('Updating gallery_images for profile ' . $profile_id . ' with IDs: ' . print_r($image_ids, true));
        $update_result = update_field('gallery_images', array_values($image_ids), $profile_id);
        error_log('Update field result: ' . ($update_result ? 'SUCCESS' : 'FAILED'));
        
        // Verify it was saved
        $verify = get_field('gallery_images', $profile_id, false);
        error_log('Verification - gallery_images after save: ' . print_r($verify, true));
        
        // Process ALL gallery images to ensure proper sequential numbering
        // This handles both new images and reordering
        if (!empty($image_ids)) {
            // Get member details
            $basic_info = get_field('basic_info', $profile_id);
            $first_name = $basic_info['first_name'] ?? get_post_meta($profile_id, 'scn_first_name', true);
            $last_name = $basic_info['last_name'] ?? get_post_meta($profile_id, 'scn_last_name', true);
            $credentials = $basic_info['credentials'] ?? get_post_meta($profile_id, 'scn_credentials', true);
            
            if (!empty($first_name) && !empty($last_name)) {
                // Get the image processor
                $image_processor = new \SCN\Membership\Modules\Profiles\ImageProcessor();
                
                // Process each image with its position in the final array
                // This ensures proper sequential numbering (01, 02, 03...)
                foreach ($image_ids as $index => $image_id) {
                    $number = $index + 1;
                    
                    // Only process new images (rename files) but update alt text for all
                    if (in_array($image_id, $new_image_ids)) {
                        // New image - rename file and set alt text
                        $image_processor->processGalleryImage($image_id, $profile_id, $number);
                    } else {
                        // Existing image - only update alt text if position changed
                        $current_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                        $expected_alt = $credentials 
                            ? sprintf('%s %s, %s Image Photo %02d', $first_name, $last_name, $credentials, $number)
                            : sprintf('%s %s Image Photo %02d', $first_name, $last_name, $number);
                        
                        // Update alt text if it doesn't match expected format
                        if ($current_alt !== $expected_alt) {
                            update_post_meta($image_id, '_wp_attachment_image_alt', $expected_alt);
                        }
                    }
                }
            }
        }
        
        wp_send_json_success([
            'message' => 'Gallery updated successfully',
            'image_count' => count($image_ids),
            'new_images_processed' => count($new_image_ids)
        ]);
    }
    
    /**
     * Remove gallery image
     */
    public function remove_gallery_image() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'remove_gallery_image')) {
            wp_die('Security check failed');
        }
        
        $profile_id = intval($_POST['profile_id']);
        $image_id = intval($_POST['image_id']);
        
        // Check permissions
        if (!$this->can_edit_profile($profile_id)) {
            wp_send_json_error('You do not have permission to edit this profile');
        }
        
        // Get current gallery images
        $gallery_images = get_field('gallery_images', $profile_id);
        if (!is_array($gallery_images)) {
            wp_send_json_error('No gallery images found');
        }
        
        // Remove image from array
        $updated_gallery = array_filter($gallery_images, function($id) use ($image_id) {
            return intval($id) !== $image_id;
        });
        
        // Update ACF field
        update_field('gallery_images', array_values($updated_gallery), $profile_id);
        
        // Delete attachment
        wp_delete_attachment($image_id, true);
        
        wp_send_json_success('Image removed successfully');
    }
    
    /**
     * Save press kit files
     */
    public function save_press_kit_files() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'save_press_kit_files')) {
            wp_die('Security check failed');
        }
        
        $profile_id = intval($_POST['profile_id']);
        
        // Check permissions
        if (!$this->can_edit_profile($profile_id)) {
            wp_send_json_error('You do not have permission to edit this profile');
        }
        
        if (!isset($_FILES['press_kit_files']) || empty($_FILES['press_kit_files']['name'][0])) {
            wp_send_json_error('No files provided');
        }
        
        $uploaded_files = [];
        $files = $_FILES['press_kit_files'];
        
        // Handle multiple file uploads
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                $attachment_id = $this->handle_file_upload($file, $profile_id, 'presskit');
                if ($attachment_id) {
                    $file_url = wp_get_attachment_url($attachment_id);
                    $uploaded_files[] = [
                        'kits' => $file_url
                    ];
                }
            }
        }
        
        if (empty($uploaded_files)) {
            wp_send_json_error('Failed to upload any files');
        }
        
        // Get existing press kit files
        $existing_files = get_field('press_kit_files', $profile_id);
        if (!is_array($existing_files)) {
            $existing_files = [];
        }
        
        // Add new files to existing ones
        $all_files = array_merge($existing_files, $uploaded_files);
        
        // Update ACF field
        update_field('press_kit_files', $all_files, $profile_id);
        
        wp_send_json_success([
            'message' => 'Press kit files saved successfully',
            'uploaded_count' => count($uploaded_files)
        ]);
    }
    
    /**
     * Remove press kit file
     */
    public function remove_press_kit_file() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'remove_press_kit_file')) {
            wp_die('Security check failed');
        }
        
        $profile_id = intval($_POST['profile_id']);
        $file_index = intval($_POST['file_index'] ?? $_POST['file_id']); // Support both parameter names
        
        // Check permissions
        if (!$this->can_edit_profile($profile_id)) {
            wp_send_json_error('You do not have permission to edit this profile');
        }
        
        // Get current press kit files
        $press_kit_files = get_field('press_kit_files', $profile_id);
        if (!is_array($press_kit_files)) {
            wp_send_json_error('No press kit files found');
        }
        
        if (!isset($press_kit_files[$file_index])) {
            wp_send_json_error('File not found');
        }
        
        // Get file URL to find attachment ID
        $file_to_remove = $press_kit_files[$file_index];
        $file_url = '';
        
        if (is_array($file_to_remove) && isset($file_to_remove['kits'])) {
            $file_url = $file_to_remove['kits'];
        } elseif (is_array($file_to_remove) && isset($file_to_remove['url'])) {
            $file_url = $file_to_remove['url'];
        } elseif (is_string($file_to_remove)) {
            $file_url = $file_to_remove;
        }
        
        // Find and delete attachment if it's a WordPress attachment
        if ($file_url) {
            $attachment_id = attachment_url_to_postid($file_url);
            if ($attachment_id) {
                wp_delete_attachment($attachment_id, true);
            }
        }
        
        // Remove file from array by index
        unset($press_kit_files[$file_index]);
        $press_kit_files = array_values($press_kit_files); // Re-index array
        
        // Update ACF field
        update_field('press_kit_files', $press_kit_files, $profile_id);
        
        wp_send_json_success('File removed successfully');
    }
    
    /**
     * Add member video
     */
    public function add_member_video() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'add_member_video')) {
            wp_die('Security check failed');
        }
        
        $profile_id = intval($_POST['profile_id']);
        $video_url = sanitize_url($_POST['video_url']);
        $video_title = sanitize_text_field($_POST['video_title']);
        
        // Check permissions
        if (!$this->can_edit_profile($profile_id)) {
            wp_send_json_error('You do not have permission to edit this profile');
        }
        
        if (empty($video_url)) {
            wp_send_json_error('Video URL is required');
        }
        
        // Fetch video title from URL if empty
        if (empty($video_title)) {
            $video_title = $this->fetch_video_title($video_url);
        }
        
        // Get existing videos
        $videos = get_field('videos', $profile_id);
        if (!is_array($videos)) {
            $videos = [];
        }
        
        // Add new video
        $new_video = [
            'url' => $video_url,
            'title' => $video_title
        ];
        
        $videos[] = $new_video;
        
        // Update ACF field
        update_field('videos', $videos, $profile_id);
        
        wp_send_json_success([
            'message' => 'Video added successfully',
            'video' => $new_video
        ]);
    }
    
    /**
     * Remove member video
     */
    public function remove_member_video() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'remove_member_video')) {
            wp_die('Security check failed');
        }
        
        $profile_id = intval($_POST['profile_id']);
        $video_index = intval($_POST['video_index']);
        
        // Check permissions
        if (!$this->can_edit_profile($profile_id)) {
            wp_send_json_error('You do not have permission to edit this profile');
        }
        
        // Get current videos
        $videos = get_field('videos', $profile_id);
        if (!is_array($videos)) {
            wp_send_json_error('No videos found');
        }
        
        if (!isset($videos[$video_index])) {
            wp_send_json_error('Video not found');
        }
        
        // Remove video from array
        unset($videos[$video_index]);
        $videos = array_values($videos); // Re-index array
        
        // Update ACF field
        update_field('videos', $videos, $profile_id);
        
        wp_send_json_success('Video removed successfully');
    }
    
    /**
     * Handle file upload with proper naming
     */
    private function handle_file_upload($file, $profile_id, $type) {
        // Get member details for naming
        $post = get_post($profile_id);
        if (!$post) {
            return false;
        }
        
        // Try ACF first, then fallback to post_meta
        $basic_info = get_field('basic_info', $profile_id);
        $first_name = $basic_info['first_name'] ?? get_post_meta($profile_id, 'scn_first_name', true);
        $last_name = $basic_info['last_name'] ?? get_post_meta($profile_id, 'scn_last_name', true);
        $credentials = $basic_info['credentials'] ?? get_post_meta($profile_id, 'scn_credentials', true);
        
        // Get file extension
        $file_info = pathinfo($file['name']);
        $extension = strtolower($file_info['extension'] ?? '');
        
        // Generate filename based on type
        if ($type === 'gallery') {
            // Get next sequential number for gallery images
            $existing_images = get_field('gallery_images', $profile_id);
            $next_number = is_array($existing_images) ? count($existing_images) + 1 : 1;
            
            $base_name = $this->generate_gallery_filename($first_name, $last_name, $credentials, $next_number);
            $filename = $base_name . '.' . $extension;
            
            // Set title for attachment to match filename
            $attachment_title = $base_name;
        } elseif ($type === 'presskit') {
            // Use same format as gallery: first-last-credentials-presskit-NNN
            $existing_files = get_field('press_kit_files', $profile_id);
            $count = is_array($existing_files) ? count($existing_files) + 1 : 1;
            
            $first_slug = sanitize_title($first_name);
            $last_slug = sanitize_title($last_name);
            $creds_slug = $credentials ? sanitize_title($credentials) : '';
            
            // Format number with leading zeros (001, 002, etc.)
            $num = str_pad($count, 3, '0', STR_PAD_LEFT);
            
            if ($creds_slug) {
                $base_name = sprintf('%s-%s-%s-presskit-%s', $first_slug, $last_slug, $creds_slug, $num);
            } else {
                $base_name = sprintf('%s-%s-presskit-%s', $first_slug, $last_slug, $num);
            }
            
            $filename = $base_name . '.' . $extension;
            $attachment_title = $base_name;
        } else {
            $base_name = $post->post_name . '-' . $type;
            $filename = $base_name . '.' . $extension;
            $attachment_title = sanitize_file_name($file_info['filename']);
        }
        
        // Move uploaded file to target location
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['path'];
        $target_path = $target_dir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            return false;
        }
        
        // Create WordPress attachment
        $attachment = [
            'post_mime_type' => $file['type'],
            'post_title' => $attachment_title,
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        
        $attachment_id = wp_insert_attachment($attachment, $target_path, $profile_id);
        
        if (!$attachment_id) {
            return false;
        }
        
        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $target_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        // Set alt text for gallery images
        if ($type === 'gallery') {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $attachment_title);
        }
        
        return $attachment_id;
    }
    
    /**
     * Generate gallery filename
     */
    private function generate_gallery_filename($first_name, $last_name, $credentials, $number) {
        $first = sanitize_title($first_name);
        $last = sanitize_title($last_name);
        $creds = $credentials ? sanitize_title($credentials) : '';
        
        // Format number with leading zero (01, 02, etc.)
        $num = str_pad($number, 2, '0', STR_PAD_LEFT);
        
        if ($creds) {
            return sprintf('%s-%s-%s-gallery-photo-%s', $first, $last, $creds, $num);
        } else {
            return sprintf('%s-%s-gallery-photo-%s', $first, $last, $num);
        }
    }
    
    /**
     * Generate gallery alt text
     */
    private function generate_gallery_alt_text($first_name, $last_name, $credentials, $number) {
        // Format number with leading zero (01, 02, etc.)
        $num = str_pad($number, 2, '0', STR_PAD_LEFT);
        
        if ($credentials) {
            return sprintf('%s %s, %s Image Photo %s', $first_name, $last_name, $credentials, $num);
        } else {
            return sprintf('%s %s Image Photo %s', $first_name, $last_name, $num);
        }
    }
    
    
    /**
     * Fetch video title from URL using oEmbed
     */
    private function fetch_video_title($video_url) {
        // Try YouTube oEmbed API first
        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video_url, $matches);
            if (!empty($matches[1])) {
                $video_id = $matches[1];
                $oembed_url = "https://www.youtube.com/oembed?url=" . urlencode("https://www.youtube.com/watch?v={$video_id}") . "&format=json";
                $response = wp_remote_get($oembed_url, ['timeout' => 5]);
                
                if (!is_wp_error($response)) {
                    $data = json_decode(wp_remote_retrieve_body($response), true);
                    if (!empty($data['title'])) {
                        return sanitize_text_field($data['title']);
                    }
                }
            }
            return 'YouTube Video';
        }
        
        // Try Vimeo oEmbed API
        if (strpos($video_url, 'vimeo.com') !== false) {
            $oembed_url = "https://vimeo.com/api/oembed.json?url=" . urlencode($video_url);
            $response = wp_remote_get($oembed_url, ['timeout' => 5]);
            
            if (!is_wp_error($response)) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($data['title'])) {
                    return sanitize_text_field($data['title']);
                }
            }
            return 'Vimeo Video';
        }
        
        // Final fallback
        return 'Video';
    }
    
    /**
     * Check if user can edit profile
     */
    private function can_edit_profile($profile_id) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $current_user_id = get_current_user_id();
        $profile_user_id = get_post_meta($profile_id, 'scn_user_id', true);
        
        // User can edit if they own the profile or have admin capabilities
        return ($current_user_id == $profile_user_id) || 
               current_user_can('edit_members') || 
               current_user_can('edit_others_posts');
    }
}

// Initialize the AJAX handlers
new SCN_Media_Ajax_Handlers();
