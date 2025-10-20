<?php
/**
 * Auto-rename uploaded files for Member CPT
 * Renames gallery images and press kit files to include member slug
 */

if (!defined('ABSPATH')) {
    exit;
}

class SCN_Member_File_Renamer {
    
    private $current_field = '';
    
    public function __construct() {
        // Hook into WordPress upload filter
        add_filter('wp_handle_upload_prefilter', [$this, 'renameUploadedFile'], 10, 1);
        
        // Track which ACF field is being uploaded
        add_action('acf/upload_files', [$this, 'trackCurrentField'], 1, 2);
        
        // Alternative: Hook into sanitize_file_name
        add_filter('sanitize_file_name', [$this, 'renameSanitizedFile'], 10, 1);
        
        // Hook into featured image uploads (profile photos)
        add_filter('wp_insert_attachment_data', [$this, 'renameProfilePhoto'], 10, 4);
    }
    
    /**
     * Track current ACF field being uploaded
     */
    public function trackCurrentField($field, $post_id) {
        $this->current_field = $field['name'] ?? '';
    }
    
    /**
     * Rename file during upload
     */
    public function renameUploadedFile($file) {
        // Get post ID from various sources
        $post_id = $this->getPostId();
        
        if (!$post_id) {
            return $file;
        }
        
        // Check if it's a member post
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'member') {
            return $file;
        }
        
        // Get member slug
        $member_slug = $post->post_name;
        if (empty($member_slug)) {
            // If no slug yet, use post ID
            $member_slug = 'member-' . $post_id;
        }
        
        // Determine file type and rename
        $file = $this->processFileRename($file, $member_slug, $post_id);
        
        return $file;
    }
    
    /**
     * Rename file during sanitization
     */
    public function renameSanitizedFile($filename) {
        // Only process if we have context
        if (empty($this->current_field)) {
            return $filename;
        }
        
        $post_id = $this->getPostId();
        if (!$post_id) {
            return $filename;
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'member') {
            return $filename;
        }
        
        $member_slug = $post->post_name;
        if (empty($member_slug)) {
            $member_slug = 'member-' . $post_id;
        }
        
        // Get extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name_without_ext = pathinfo($filename, PATHINFO_FILENAME);
        
        // Determine type and count
        if ($this->current_field === 'gallery_images' || strpos($this->current_field, 'gallery') !== false) {
            $gallery_images = get_field('gallery_images', $post_id);
            $count = is_array($gallery_images) ? count($gallery_images) + 1 : 1;
            $new_name = sprintf('%s-photo-%d', $member_slug, $count);
        } elseif ($this->current_field === 'press_kit_files' || strpos($this->current_field, 'press') !== false) {
            $press_kit_files = get_field('press_kit_files', $post_id);
            $count = is_array($press_kit_files) ? count($press_kit_files) + 1 : 1;
            $new_name = sprintf('%s-presskit-%d', $member_slug, $count);
        } else {
            // Generic rename if field is unknown
            $new_name = sprintf('%s-%s', $member_slug, $name_without_ext);
        }
        
        return $ext ? $new_name . '.' . $ext : $new_name;
    }
    
    /**
     * Rename profile photo (featured image)
     */
    public function renameProfilePhoto($data, $postarr, $unsanitized_postarr, $update) {
        // Only process for member attachments
        if (!isset($postarr['post_parent']) || empty($postarr['post_parent'])) {
            return $data;
        }
        
        $parent_post = get_post($postarr['post_parent']);
        if (!$parent_post || $parent_post->post_type !== 'member') {
            return $data;
        }
        
        // Check if this is being set as featured image
        $is_featured = false;
        if (isset($_POST['action']) && $_POST['action'] === 'set-post-thumbnail') {
            $is_featured = true;
        }
        
        // If featured image or profile image field
        if ($is_featured || (isset($_POST['field']) && strpos($_POST['field'], 'profile') !== false)) {
            // Get member details
            $first_name = get_post_meta($parent_post->ID, 'scn_first_name', true);
            $last_name = get_post_meta($parent_post->ID, 'scn_last_name', true);
            $credentials = get_post_meta($parent_post->ID, 'scn_credentials', true);
            
            if ($first_name && $last_name) {
                $base_name = $this->generateProfilePhotoName($first_name, $last_name, $credentials);
                // This will be used when the attachment is inserted
                update_option('scn_pending_profile_rename_' . $parent_post->ID, $base_name, false);
            }
        }
        
        return $data;
    }
    
    /**
     * Process file rename based on field type
     */
    private function processFileRename($file, $member_slug, $post_id) {
        // Get original extension
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        // Try to determine field from various sources
        $field_name = '';
        
        // Check POST data
        if (isset($_POST['field_key'])) {
            $field_obj = get_field_object($_POST['field_key']);
            $field_name = $field_obj['name'] ?? '';
        } elseif (isset($_POST['field'])) {
            $field_name = sanitize_text_field($_POST['field']);
        } elseif (isset($_REQUEST['acf_field'])) {
            $field_name = sanitize_text_field($_REQUEST['acf_field']);
        } elseif (!empty($this->current_field)) {
            $field_name = $this->current_field;
        }
        
        // Check if this is a profile photo (featured image)
        if (isset($_POST['action']) && $_POST['action'] === 'set-post-thumbnail') {
            return $this->renameAsProfilePhoto($file, $post_id);
        }
        
        // Check referrer for context clues
        $referer = wp_get_referer();
        if (empty($field_name) && $referer) {
            if (strpos($referer, 'gallery') !== false) {
                $field_name = 'gallery_images';
            } elseif (strpos($referer, 'press') !== false || strpos($referer, 'kit') !== false) {
                $field_name = 'press_kit_files';
            } elseif (strpos($referer, 'profile') !== false) {
                return $this->renameAsProfilePhoto($file, $post_id);
            }
        }
        
        // Determine type and count
        if ($field_name === 'gallery_images' || strpos($field_name, 'gallery') !== false) {
            $gallery_images = get_field('gallery_images', $post_id);
            $count = is_array($gallery_images) ? count($gallery_images) + 1 : 1;
            $new_name = sprintf('%s-photo-%d.%s', $member_slug, $count, $ext);
        } elseif ($field_name === 'press_kit_files' || strpos($field_name, 'press') !== false) {
            $press_kit_files = get_field('press_kit_files', $post_id);
            $count = is_array($press_kit_files) ? count($press_kit_files) + 1 : 1;
            $new_name = sprintf('%s-presskit-%d.%s', $member_slug, $count, $ext);
        } else {
            // Keep original name but prefix with member slug
            $new_name = sprintf('%s-%s', $member_slug, $file['name']);
        }
        
        $file['name'] = $new_name;
        
        return $file;
    }
    
    /**
     * Rename file as profile photo
     */
    private function renameAsProfilePhoto($file, $post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return $file;
        }
        
        // Get member details
        $first_name = get_post_meta($post_id, 'scn_first_name', true);
        $last_name = get_post_meta($post_id, 'scn_last_name', true);
        $credentials = get_post_meta($post_id, 'scn_credentials', true);
        
        if (!$first_name || !$last_name) {
            // Fallback to member slug
            $member_slug = $post->post_name;
            $base_name = $member_slug . '-photo';
        } else {
            $base_name = $this->generateProfilePhotoName($first_name, $last_name, $credentials);
        }
        
        // Get extension
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        // Check if file exists and get unique number
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['path'];
        $new_name = $base_name . '.' . $ext;
        $counter = 0;
        
        // Check for existing files
        while (file_exists($target_dir . '/' . $new_name)) {
            $counter++;
            $new_name = sprintf('%s-%02d.%s', $base_name, $counter, $ext);
            
            // Safety limit
            if ($counter > 99) {
                break;
            }
        }
        
        $file['name'] = $new_name;
        
        return $file;
    }
    
    /**
     * Generate profile photo base name
     */
    private function generateProfilePhotoName($first_name, $last_name, $credentials = '') {
        // Sanitize names
        $first = sanitize_title($first_name);
        $last = sanitize_title($last_name);
        $creds = $credentials ? sanitize_title($credentials) : '';
        
        // Build base name
        if ($creds) {
            $base_name = sprintf('%s-%s-%s-photo', $first, $last, $creds);
        } else {
            $base_name = sprintf('%s-%s-photo', $first, $last);
        }
        
        return $base_name;
    }
    
    /**
     * Get post ID from various sources
     */
    private function getPostId() {
        // Try POST data first
        if (isset($_POST['post_id'])) {
            return intval($_POST['post_id']);
        }
        
        if (isset($_POST['post'])) {
            return intval($_POST['post']);
        }
        
        // Try REQUEST
        if (isset($_REQUEST['post_id'])) {
            return intval($_REQUEST['post_id']);
        }
        
        if (isset($_REQUEST['post'])) {
            return intval($_REQUEST['post']);
        }
        
        // Try to get from referrer
        $referer = wp_get_referer();
        if ($referer && preg_match('/post=(\d+)/', $referer, $matches)) {
            return intval($matches[1]);
        }
        
        // Global post
        global $post;
        if ($post && $post->ID) {
            return $post->ID;
        }
        
        return 0;
    }
}

// Initialize
new SCN_Member_File_Renamer();

