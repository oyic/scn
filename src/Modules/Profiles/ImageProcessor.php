<?php

namespace SCN\Membership\Modules\Profiles;

class ImageProcessor {
    public function register() {
        add_action('add_attachment', [$this, 'processProfileImage']);
        add_filter('wp_handle_upload_prefilter', [$this, 'validateImageUpload']);
    }

    public function processProfileImage($attachment_id) {
        // Only process if this is a profile image
        if (!$this->isProfileImage($attachment_id)) {
            return;
        }

        $this->renameImageForSEO($attachment_id);
        $this->generateAltText($attachment_id);
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
        $post = get_post($attachment_id);
        if (!$post || $post->post_parent == 0) {
            return false;
        }

        $parent_post = get_post($post->post_parent);
        return $parent_post && $parent_post->post_type === 'scn_profile';
    }

    private function isProfileUploadContext() {
        // Check if we're in the profile edit context
        return isset($_POST['post_type']) && $_POST['post_type'] === 'scn_profile';
    }

    private function renameImageForSEO($attachment_id) {
        $post = get_post($attachment_id);
        if (!$post) {
            return;
        }

        $parent_post = get_post($post->post_parent);
        if (!$parent_post || $parent_post->post_type !== 'scn_profile') {
            return;
        }

        $first_name = get_post_meta($parent_post->ID, 'scn_first_name', true);
        $last_name = get_post_meta($parent_post->ID, 'scn_last_name', true);

        if (empty($first_name) || empty($last_name)) {
            return;
        }

        // Generate SEO-friendly filename
        $new_filename = $this->generateSEOFilename($first_name, $last_name, $attachment_id);
        if (!$new_filename) {
            return;
        }

        // Get current file info
        $file_path = get_attached_file($attachment_id);
        if (!$file_path) {
            return;
        }

        $file_info = pathinfo($file_path);
        $upload_dir = wp_upload_dir();
        $new_file_path = $upload_dir['path'] . '/' . $new_filename;

        // Rename the file
        if (rename($file_path, $new_file_path)) {
            // Update attachment metadata
            update_attached_file($attachment_id, $new_file_path);
            
            // Update the attachment post
            wp_update_post([
                'ID' => $attachment_id,
                'post_title' => $new_filename,
                'post_name' => sanitize_title($new_filename),
            ]);

            // Update attachment metadata
            $metadata = wp_get_attachment_metadata($attachment_id);
            if ($metadata) {
                $metadata['file'] = str_replace($upload_dir['basedir'], '', $new_file_path);
                wp_update_attachment_metadata($attachment_id, $metadata);
            }
        }
    }

    private function generateSEOFilename($first_name, $last_name, $attachment_id) {
        // Normalize names
        $first_name = $this->normalizeForFilename($first_name);
        $last_name = $this->normalizeForFilename($last_name);

        // Get current gallery images to determine increment
        $parent_post = get_post(wp_get_post_parent_id($attachment_id));
        $gallery_images = get_post_meta($parent_post->ID, 'scn_gallery_images', true) ?: [];
        
        // Count existing images with same name pattern
        $increment = 1;
        foreach ($gallery_images as $existing_id) {
            if ($existing_id == $attachment_id) {
                continue;
            }
            $existing_post = get_post($existing_id);
            if ($existing_post && strpos($existing_post->post_title, "{$first_name}-{$last_name}-photo") === 0) {
                $increment++;
            }
        }

        return "{$first_name}-{$last_name}-photo{$increment}.jpg";
    }

    private function normalizeForFilename($name) {
        // Convert to lowercase
        $name = strtolower($name);
        
        // Remove diacritics
        $name = remove_accents($name);
        
        // Replace spaces and special characters with hyphens
        $name = preg_replace('/[^a-z0-9]+/', '-', $name);
        
        // Remove leading/trailing hyphens
        $name = trim($name, '-');
        
        return $name;
    }

    private function generateAltText($attachment_id) {
        $post = get_post($attachment_id);
        if (!$post) {
            return;
        }

        $parent_post = get_post($post->post_parent);
        if (!$parent_post || $parent_post->post_type !== 'scn_profile') {
            return;
        }

        $first_name = get_post_meta($parent_post->ID, 'scn_first_name', true);
        $last_name = get_post_meta($parent_post->ID, 'scn_last_name', true);

        if (empty($first_name) || empty($last_name)) {
            return;
        }

        // Generate alt text
        $alt_text = sprintf(
            __('Photo of %s %s, member of Speaking Consulting Network', 'scn-membership'),
            $first_name,
            $last_name
        );

        // Allow filtering
        $alt_text = apply_filters('scn/profile/gallery_alt_text', $alt_text, $attachment_id, $parent_post->ID);

        // Update alt text
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
    }

    public function generatePressKitFilename($first_name, $last_name, $original_filename) {
        $first_name = $this->normalizeForFilename($first_name);
        $last_name = $this->normalizeForFilename($last_name);
        
        $file_info = pathinfo($original_filename);
        $extension = strtolower($file_info['extension']);
        
        // Get increment for press kit files
        $parent_post_id = get_the_ID();
        $press_kit_files = get_post_meta($parent_post_id, 'scn_press_kit_files', true) ?: [];
        
        $increment = 1;
        foreach ($press_kit_files as $existing_id) {
            $existing_post = get_post($existing_id);
            if ($existing_post && strpos($existing_post->post_title, "{$first_name}-{$last_name}-presskit") === 0) {
                $increment++;
            }
        }

        return "{$first_name}-{$last_name}-presskit{$increment}.{$extension}";
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


