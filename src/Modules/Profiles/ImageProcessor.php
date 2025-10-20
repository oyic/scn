<?php

namespace SCN\Membership\Modules\Profiles;

class ImageProcessor {
    public function register() {
        add_action('add_attachment', [$this, 'processProfileImage']);
        add_filter('wp_handle_upload_prefilter', [$this, 'validateImageUpload']);
        add_action('update_post_meta', [$this, 'processProfileImageOnThumbnailSet'], 10, 4);
        // Use acf/save_post to run AFTER ACF saves all fields
        add_action('acf/save_post', [$this, 'processGalleryImagesOnSave'], 20);
    }
    
    /**
     * Process profile image when it's set as featured image
     */
    public function processProfileImageOnThumbnailSet($meta_id, $post_id, $meta_key, $meta_value) {
        // Only process when featured image is being set
        if ($meta_key !== '_thumbnail_id') {
            return;
        }
        
        // Check if this is a member post
        $post = \get_post($post_id);
        if (!$post || $post->post_type !== 'member') {
            return;
        }
        
        // Process the attachment
        $attachment_id = intval($meta_value);
        if ($attachment_id) {
            // Update the attachment's parent to the profile
            wp_update_post([
                'ID' => $attachment_id,
                'post_parent' => $post_id
            ]);
            
            // Process it (rename and set alt text)
            $this->processProfileImageDirect($attachment_id, $post_id);
        }
    }
    
    /**
     * Process profile image directly with post ID
     */
    public function processProfileImageDirect($attachment_id, $post_id) {
        $post = \get_post($post_id);
        if (!$post || $post->post_type !== 'member') {
            return;
        }
        
        // Try ACF first, then fallback to post_meta
        $basic_info = \get_field('basic_info', $post_id);
        $first_name = $basic_info['first_name'] ?? \get_post_meta($post_id, 'scn_first_name', true);
        $last_name = $basic_info['last_name'] ?? \get_post_meta($post_id, 'scn_last_name', true);
        $credentials = $basic_info['credentials'] ?? \get_post_meta($post_id, 'scn_credentials', true);

        if (empty($first_name) || empty($last_name)) {
            return;
        }
        
        // Rename file
        $this->renameAttachmentFile($attachment_id, $first_name, $last_name, $credentials);
        
        // Set alt text
        $this->setAttachmentAltText($attachment_id, $first_name, $last_name, $credentials);
    }
    
    private function renameAttachmentFile($attachment_id, $first_name, $last_name, $credentials) {
        $new_filename = $this->generateSEOFilename($first_name, $last_name, $credentials, $attachment_id);
        if (!$new_filename) {
            return;
        }

        // Get current file info
        $file_path = \get_attached_file($attachment_id);
        if (!$file_path) {
            return;
        }

        $file_info = pathinfo($file_path);
        $upload_dir = \wp_upload_dir();
        $new_file_path = $upload_dir['path'] . '/' . $new_filename;
        
        // Don't rename if already named correctly
        if (basename($file_path) === $new_filename) {
            return;
        }

        // Get metadata before renaming
        $metadata = \wp_get_attachment_metadata($attachment_id);

        // Rename the main file
        if (rename($file_path, $new_file_path)) {
            // Update attachment metadata
            \update_attached_file($attachment_id, $new_file_path);
            
            // Rename all size variations (thumbnail, medium, large, etc.)
            if ($metadata && isset($metadata['sizes']) && is_array($metadata['sizes'])) {
                $old_basename = pathinfo($file_path, PATHINFO_FILENAME);
                $new_basename = pathinfo($new_filename, PATHINFO_FILENAME);
                $extension = pathinfo($new_filename, PATHINFO_EXTENSION);
                $upload_path = dirname($file_path);
                
                foreach ($metadata['sizes'] as $size_name => $size_data) {
                    if (isset($size_data['file'])) {
                        $old_size_file = $upload_path . '/' . $size_data['file'];
                        
                        // Extract dimensions from old filename (e.g., "old-name-150x150.jpg")
                        if (preg_match('/-(\d+x\d+)\.' . preg_quote($extension, '/') . '$/', $size_data['file'], $matches)) {
                            $dimensions = $matches[1];
                            $new_size_filename = $new_basename . '-' . $dimensions . '.' . $extension;
                            $new_size_file = $upload_path . '/' . $new_size_filename;
                            
                            // Rename the size file
                            if (file_exists($old_size_file)) {
                                rename($old_size_file, $new_size_file);
                                $metadata['sizes'][$size_name]['file'] = $new_size_filename;
                            }
                        }
                    }
                }
            }
            
            // Update the attachment post
            \wp_update_post([
                'ID' => $attachment_id,
                'post_title' => $first_name . ' ' . $last_name . ($credentials ? ', ' . $credentials : ''),
                'post_name' => \sanitize_title($new_filename),
            ]);

            // Update attachment metadata with new file path and size filenames
            if ($metadata) {
                $metadata['file'] = str_replace($upload_dir['basedir'] . '/', '', $new_file_path);
                \wp_update_attachment_metadata($attachment_id, $metadata);
            }
        }
    }
    
    private function setAttachmentAltText($attachment_id, $first_name, $last_name, $credentials) {
        // Generate alt text
        if ($credentials) {
            $alt_text = sprintf(
                __('Profile Photo of %s %s, %s', 'scn-membership'),
                $first_name,
                $last_name,
                $credentials
            );
        } else {
            $alt_text = sprintf(
                __('Profile Photo of %s %s', 'scn-membership'),
                $first_name,
                $last_name
            );
        }

        // Allow filtering
        $alt_text = \apply_filters('scn/profile/gallery_alt_text', $alt_text, $attachment_id, \wp_get_post_parent_id($attachment_id));

        // Update alt text
        \update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
    }

    public function processProfileImage($attachment_id) {
        // Only process if this is a profile image
        if (!$this->isProfileImage($attachment_id)) {
            return;
        }

        $this->renameImageForSEO($attachment_id);
        $this->generateAltText($attachment_id);
    }
    
    /**
     * Process gallery image with sequential numbering
     */
    public function processGalleryImage($attachment_id, $post_id, $number) {
        $post = \get_post($post_id);
        if (!$post || $post->post_type !== 'member') {
            return;
        }
        
        // Try ACF first, then fallback to post_meta
        $basic_info = \get_field('basic_info', $post_id);
        $first_name = $basic_info['first_name'] ?? \get_post_meta($post_id, 'scn_first_name', true);
        $last_name = $basic_info['last_name'] ?? \get_post_meta($post_id, 'scn_last_name', true);
        $credentials = $basic_info['credentials'] ?? \get_post_meta($post_id, 'scn_credentials', true);

        if (empty($first_name) || empty($last_name)) {
            return;
        }
        
        // Rename file with sequential number
        $this->renameGalleryFile($attachment_id, $first_name, $last_name, $credentials, $number);
        
        // Set alt text
        $this->setGalleryAltText($attachment_id, $first_name, $last_name, $credentials, $number);
    }
    
    private function renameGalleryFile($attachment_id, $first_name, $last_name, $credentials, $number) {
        error_log('Renaming gallery file for attachment ' . $attachment_id);
        
        $new_filename = $this->generateGalleryFilename($first_name, $last_name, $credentials, $number, $attachment_id);
        if (!$new_filename) {
            error_log('Failed to generate new filename');
            return;
        }
        
        error_log('New filename: ' . $new_filename);

        // Get current file info
        $file_path = \get_attached_file($attachment_id);
        if (!$file_path) {
            error_log('Could not get attached file path');
            return;
        }
        
        error_log('Current file path: ' . $file_path);

        $upload_dir = \wp_upload_dir();
        $new_file_path = $upload_dir['path'] . '/' . $new_filename;
        
        // Don't rename if already named correctly
        if (basename($file_path) === $new_filename) {
            error_log('File already has correct name');
            return;
        }

        error_log('Attempting to rename from: ' . $file_path . ' to: ' . $new_file_path);
        
        // Get metadata before renaming
        $metadata = \wp_get_attachment_metadata($attachment_id);
        
        // Rename the main file
        if (rename($file_path, $new_file_path)) {
            error_log('File renamed successfully');
            
            // Update attachment metadata
            \update_attached_file($attachment_id, $new_file_path);
            
            // Rename all size variations (thumbnail, medium, large, etc.)
            if ($metadata && isset($metadata['sizes']) && is_array($metadata['sizes'])) {
                $old_basename = pathinfo($file_path, PATHINFO_FILENAME);
                $new_basename = pathinfo($new_filename, PATHINFO_FILENAME);
                $extension = pathinfo($new_filename, PATHINFO_EXTENSION);
                $upload_path = dirname($file_path);
                
                foreach ($metadata['sizes'] as $size_name => $size_data) {
                    if (isset($size_data['file'])) {
                        $old_size_file = $upload_path . '/' . $size_data['file'];
                        
                        // Extract dimensions from old filename (e.g., "old-name-150x150.jpg")
                        if (preg_match('/-(\d+x\d+)\.' . preg_quote($extension, '/') . '$/', $size_data['file'], $matches)) {
                            $dimensions = $matches[1];
                            $new_size_filename = $new_basename . '-' . $dimensions . '.' . $extension;
                            $new_size_file = $upload_path . '/' . $new_size_filename;
                            
                            // Rename the size file
                            if (file_exists($old_size_file)) {
                                rename($old_size_file, $new_size_file);
                                $metadata['sizes'][$size_name]['file'] = $new_size_filename;
                                error_log("Renamed size '$size_name': $new_size_filename");
                            }
                        }
                    }
                }
            }
            
            // Generate title
            $num = str_pad($number, 2, '0', STR_PAD_LEFT);
            if ($credentials) {
                $title = sprintf('%s %s, %s Image Photo %s', $first_name, $last_name, $credentials, $num);
            } else {
                $title = sprintf('%s %s Image Photo %s', $first_name, $last_name, $num);
            }
            
            error_log('Setting attachment title to: ' . $title);
            
            // Update the attachment post
            \wp_update_post([
                'ID' => $attachment_id,
                'post_title' => $title,
                'post_name' => \sanitize_title($new_filename),
            ]);

            // Update attachment metadata with new file path and size filenames
            if ($metadata) {
                $metadata['file'] = str_replace($upload_dir['basedir'] . '/', '', $new_file_path);
                \wp_update_attachment_metadata($attachment_id, $metadata);
                error_log('Updated attachment metadata');
            }
        } else {
            error_log('ERROR: Failed to rename file!');
        }
    }
    
    private function generateGalleryFilename($first_name, $last_name, $credentials, $number, $attachment_id) {
        // Normalize names
        $first_name = $this->normalizeForFilename($first_name);
        $last_name = $this->normalizeForFilename($last_name);
        $credentials_slug = $credentials ? $this->normalizeForFilename($credentials) : '';

        // Get file extension
        $file_path = \get_attached_file($attachment_id);
        $file_info = pathinfo($file_path);
        $extension = isset($file_info['extension']) ? strtolower($file_info['extension']) : 'jpg';

        // Format number with leading zero (01, 02, etc.)
        $num = str_pad($number, 2, '0', STR_PAD_LEFT);

        // Build filename with credentials if available
        if ($credentials_slug) {
            $base_name = "{$first_name}-{$last_name}-{$credentials_slug}-gallery-photo-{$num}";
        } else {
            $base_name = "{$first_name}-{$last_name}-gallery-photo-{$num}";
        }

        return "{$base_name}.{$extension}";
    }
    
    private function setGalleryAltText($attachment_id, $first_name, $last_name, $credentials, $number) {
        // Format number with leading zero (01, 02, etc.)
        $num = str_pad($number, 2, '0', STR_PAD_LEFT);
        
        // Generate alt text
        if ($credentials) {
            $alt_text = sprintf('%s %s, %s Image Photo %s', $first_name, $last_name, $credentials, $num);
        } else {
            $alt_text = sprintf('%s %s Image Photo %s', $first_name, $last_name, $num);
        }

        // Update alt text
        \update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
    }
    
    /**
     * Process gallery images when member post is saved via ACF
     * Runs AFTER ACF saves all fields
     */
    public function processGalleryImagesOnSave($post_id) {
        error_log('=== Gallery Images Processing Start for Post ID: ' . $post_id . ' ===');
        
        // Get post type
        $post_type = \get_post_type($post_id);
        error_log('Post Type: ' . $post_type);
        
        // Only process member posts
        if ($post_type !== 'member') {
            error_log('Not a member post, skipping');
            return;
        }
        
        // Skip autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            error_log('Autosave or revision, skipping');
            return;
        }
        
        // Get gallery images from ACF field (should be available now)
        $gallery_images = \get_field('gallery_images', $post_id);
        error_log('Gallery Images: ' . print_r($gallery_images, true));
        
        if (!is_array($gallery_images) || empty($gallery_images)) {
            error_log('No gallery images found');
            return;
        }
        
        // Get member details
        $basic_info = \get_field('basic_info', $post_id);
        error_log('Basic Info: ' . print_r($basic_info, true));
        
        $first_name = $basic_info['first_name'] ?? \get_post_meta($post_id, 'scn_first_name', true);
        $last_name = $basic_info['last_name'] ?? \get_post_meta($post_id, 'scn_last_name', true);
        $credentials = $basic_info['credentials'] ?? \get_post_meta($post_id, 'scn_credentials', true);
        
        error_log('Name: ' . $first_name . ' ' . $last_name . ', Credentials: ' . $credentials);
        
        if (empty($first_name) || empty($last_name)) {
            error_log('First name or last name is empty, skipping');
            return;
        }
        
        // Process each gallery image with sequential numbering
        error_log('Processing ' . count($gallery_images) . ' gallery images');
        foreach ($gallery_images as $index => $image) {
            $number = $index + 1;
            
            // ACF gallery fields can return ID or array with 'ID' or 'id'
            if (is_array($image)) {
                $attachment_id = isset($image['ID']) ? $image['ID'] : (isset($image['id']) ? $image['id'] : null);
            } else {
                $attachment_id = $image;
            }
            
            if (!$attachment_id) {
                error_log('Could not extract attachment ID from gallery image at index ' . $index);
                continue;
            }
            
            error_log('Processing image ' . $number . ' (ID: ' . $attachment_id . ')');
            $this->processGalleryImage($attachment_id, $post_id, $number);
        }
        
        error_log('=== Gallery Images Processing Complete ===');
    }

    public function validateImageUpload($file) {
        // Check if this is being uploaded to a profile
        if (!$this->isProfileUploadContext()) {
            return $file;
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            $file['error'] = __('Only JPEG, PNG, and GIF images are allowed for profiles.', 'scn-membership');
            return $file;
        }

        // Check file size (10MB max for profile images)
        $max_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_size) {
            $file['error'] = __('Profile images must be smaller than 10MB.', 'scn-membership');
            return $file;
        }

        return $file;
    }

    private function isProfileImage($attachment_id) {
        // Check if this image is attached to a profile post
        $post = \get_post($attachment_id);
        if (!$post || $post->post_parent == 0) {
            return false;
        }

        $parent_post = \get_post($post->post_parent);
        return $parent_post && $parent_post->post_type === 'member';
    }

    private function isProfileUploadContext() {
        // Check if we're in the member edit context
        return isset($_POST['post_type']) && $_POST['post_type'] === 'member';
    }

    private function renameImageForSEO($attachment_id) {
        $post = \get_post($attachment_id);
        if (!$post) {
            return;
        }

        $parent_post = \get_post($post->post_parent);
        if (!$parent_post || $parent_post->post_type !== 'member') {
            return;
        }

        // Try ACF first, then fallback to post_meta
        $basic_info = \get_field('basic_info', $parent_post->ID);
        $first_name = $basic_info['first_name'] ?? \get_post_meta($parent_post->ID, 'scn_first_name', true);
        $last_name = $basic_info['last_name'] ?? \get_post_meta($parent_post->ID, 'scn_last_name', true);
        $credentials = $basic_info['credentials'] ?? \get_post_meta($parent_post->ID, 'scn_credentials', true);

        if (empty($first_name) || empty($last_name)) {
            return;
        }

        // Generate SEO-friendly filename
        $new_filename = $this->generateSEOFilename($first_name, $last_name, $credentials, $attachment_id);
        if (!$new_filename) {
            return;
        }

        // Get current file info
        $file_path = \get_attached_file($attachment_id);
        if (!$file_path) {
            return;
        }

        $file_info = pathinfo($file_path);
        $upload_dir = \wp_upload_dir();
        $new_file_path = $upload_dir['path'] . '/' . $new_filename;

        // Rename the file
        if (rename($file_path, $new_file_path)) {
            // Update attachment metadata
            \update_attached_file($attachment_id, $new_file_path);
            
            // Update the attachment post
            \wp_update_post([
                'ID' => $attachment_id,
                'post_title' => $new_filename,
                'post_name' => \sanitize_title($new_filename),
            ]);

            // Update attachment metadata
            $metadata = \wp_get_attachment_metadata($attachment_id);
            if ($metadata) {
                $metadata['file'] = str_replace($upload_dir['basedir'], '', $new_file_path);
                \wp_update_attachment_metadata($attachment_id, $metadata);
            }
        }
    }

    private function generateSEOFilename($first_name, $last_name, $credentials, $attachment_id) {
        // Normalize names
        $first_name = $this->normalizeForFilename($first_name);
        $last_name = $this->normalizeForFilename($last_name);
        $credentials_slug = $credentials ? $this->normalizeForFilename($credentials) : '';

        // Get file extension
        $file_path = \get_attached_file($attachment_id);
        $file_info = pathinfo($file_path);
        $extension = isset($file_info['extension']) ? strtolower($file_info['extension']) : 'jpg';

        // Build filename with credentials if available
        if ($credentials_slug) {
            $base_name = "{$first_name}-{$last_name}-{$credentials_slug}-profile-photo";
        } else {
            $base_name = "{$first_name}-{$last_name}-profile-photo";
        }

        return "{$base_name}.{$extension}";
    }

    private function normalizeForFilename($name) {
        // Convert to lowercase
        $name = strtolower($name);
        
        // Remove diacritics
        $name = \remove_accents($name);
        
        // Replace spaces and special characters with hyphens
        $name = preg_replace('/[^a-z0-9]+/', '-', $name);
        
        // Remove leading/trailing hyphens
        $name = trim($name, '-');
        
        return $name;
    }

    private function generateAltText($attachment_id) {
        $post = \get_post($attachment_id);
        if (!$post) {
            return;
        }

        $parent_post = \get_post($post->post_parent);
        if (!$parent_post || $parent_post->post_type !== 'member') {
            return;
        }

        // Try ACF first, then fallback to post_meta
        $basic_info = \get_field('basic_info', $parent_post->ID);
        $first_name = $basic_info['first_name'] ?? \get_post_meta($parent_post->ID, 'scn_first_name', true);
        $last_name = $basic_info['last_name'] ?? \get_post_meta($parent_post->ID, 'scn_last_name', true);
        $credentials = $basic_info['credentials'] ?? \get_post_meta($parent_post->ID, 'scn_credentials', true);

        if (empty($first_name) || empty($last_name)) {
            return;
        }

        // Generate alt text
        if ($credentials) {
            $alt_text = sprintf(
                __('Profile Photo of %s %s, %s', 'scn-membership'),
                $first_name,
                $last_name,
                $credentials
            );
        } else {
            $alt_text = sprintf(
                __('Profile Photo of %s %s', 'scn-membership'),
                $first_name,
                $last_name
            );
        }

        // Allow filtering
        $alt_text = \apply_filters('scn/profile/gallery_alt_text', $alt_text, $attachment_id, $parent_post->ID);

        // Update alt text
        \update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
    }

    public function generatePressKitFilename($first_name, $last_name, $credentials = '', $original_filename = '', $post_id = null) {
        $first_slug = sanitize_title($first_name);
        $last_slug = sanitize_title($last_name);
        $creds_slug = $credentials ? sanitize_title($credentials) : '';
        
        $file_info = pathinfo($original_filename);
        $extension = strtolower($file_info['extension'] ?? 'pdf');
        
        // Get increment for press kit files
        $parent_post_id = $post_id ?: \get_the_ID();
        $press_kit_files = \get_field('press_kit_files', $parent_post_id) ?: [];
        $count = is_array($press_kit_files) ? count($press_kit_files) + 1 : 1;
        
        // Format number with leading zeros (001, 002, etc.)
        $num = str_pad($count, 3, '0', STR_PAD_LEFT);
        
        if ($creds_slug) {
            return sprintf('%s-%s-%s-presskit-%s.%s', $first_slug, $last_slug, $creds_slug, $num, $extension);
        } else {
            return sprintf('%s-%s-presskit-%s.%s', $first_slug, $last_slug, $num, $extension);
        }
    }

    public function validatePressKitFile($file) {
        // Check if this is being uploaded to a profile
        if (!$this->isProfileUploadContext()) {
            return $file;
        }

        // Check if this is a press kit upload
        if (!isset($_POST['scn_press_kit_upload'])) {
            return $file;
        }

        // Validate file type
        $allowed_types = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        
        if (!in_array($file['type'], $allowed_types)) {
            $file['error'] = __('Only PDF, DOC, and DOCX files are allowed for press kits.', 'scn-membership');
            return $file;
        }

        // Check file size (20MB max for press kit files)
        $max_size = 20 * 1024 * 1024; // 20MB
        if ($file['size'] > $max_size) {
            $file['error'] = __('Press kit files must be smaller than 20MB.', 'scn-membership');
            return $file;
        }

        return $file;
    }
}


