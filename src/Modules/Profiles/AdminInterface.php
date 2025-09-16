<?php

namespace SCN\Membership\Modules\Profiles;

class AdminInterface {
    public function register() {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_action('wp_ajax_scn_upload_gallery_image', [$this, 'handleGalleryUpload']);
        add_action('wp_ajax_scn_upload_press_kit_file', [$this, 'handlePressKitUpload']);
        add_action('wp_ajax_scn_reorder_gallery', [$this, 'handleGalleryReorder']);
        add_action('wp_ajax_scn_remove_gallery_image', [$this, 'handleRemoveGalleryImage']);
        add_action('wp_ajax_scn_remove_press_kit_file', [$this, 'handleRemovePressKitFile']);
    }

    public function enqueueAdminScripts($hook) {
        global $post_type;

        if ($post_type !== 'scn_profile' || !in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');
        
        wp_enqueue_script(
            'scn-profiles-admin',
            SCN_MEMBERSHIP_URL . 'assets/js/profiles-admin.js',
            ['jquery', 'jquery-ui-sortable', 'media-upload'],
            SCN_MEMBERSHIP_VERSION,
            true
        );

        wp_enqueue_style(
            'scn-profiles-admin',
            SCN_MEMBERSHIP_URL . 'assets/css/profiles-admin.css',
            [],
            SCN_MEMBERSHIP_VERSION
        );

        wp_localize_script('scn-profiles-admin', 'scnProfilesAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scn_profiles_admin'),
            'strings' => [
                'selectImages' => __('Select Images', 'scn-membership'),
                'selectFiles' => __('Select Files', 'scn-membership'),
                'removeImage' => __('Remove Image', 'scn-membership'),
                'removeFile' => __('Remove File', 'scn-membership'),
                'uploading' => __('Uploading...', 'scn-membership'),
                'uploadError' => __('Upload failed. Please try again.', 'scn-membership'),
                'invalidFileType' => __('Invalid file type. Please select a valid image.', 'scn-membership'),
                'fileTooLarge' => __('File is too large. Please select a smaller file.', 'scn-membership'),
            ],
        ]);
    }

    public function handleGalleryUpload() {
        check_ajax_referer('scn_profiles_admin', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $post_id = intval($_POST['post_id']);
        if (!$post_id || get_post_type($post_id) !== 'scn_profile') {
            wp_send_json_error(__('Invalid post ID.', 'scn-membership'));
        }

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploadedfile = $_FILES['file'];
        $upload_overrides = [
            'test_form' => false,
            'action' => 'scn_upload_gallery_image',
        ];

        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $filename = $movefile['file'];
            $wp_filetype = wp_check_filetype(basename($filename), null);
            $wp_upload_dir = wp_upload_dir();

            $attachment = [
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                'post_content' => '',
                'post_status' => 'inherit',
                'post_parent' => $post_id,
            ];

            $attachment_id = wp_insert_attachment($attachment, $filename, $post_id);

            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $filename);
                wp_update_attachment_metadata($attachment_id, $attachment_data);

                // Process the image for SEO naming and alt text
                $image_processor = new ImageProcessor();
                $image_processor->processProfileImage($attachment_id);

                // Add to gallery
                $gallery_images = get_post_meta($post_id, 'scn_gallery_images', true) ?: [];
                $gallery_images[] = $attachment_id;
                update_post_meta($post_id, 'scn_gallery_images', $gallery_images);

                wp_send_json_success([
                    'attachment_id' => $attachment_id,
                    'thumbnail' => wp_get_attachment_image($attachment_id, 'thumbnail'),
                    'url' => wp_get_attachment_url($attachment_id),
                ]);
            }
        }

        wp_send_json_error($movefile['error'] ?? __('Upload failed.', 'scn-membership'));
    }

    public function handlePressKitUpload() {
        check_ajax_referer('scn_profiles_admin', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $post_id = intval($_POST['post_id']);
        if (!$post_id || get_post_type($post_id) !== 'scn_profile') {
            wp_send_json_error(__('Invalid post ID.', 'scn-membership'));
        }

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploadedfile = $_FILES['file'];
        $upload_overrides = [
            'test_form' => false,
            'action' => 'scn_upload_press_kit_file',
        ];

        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $filename = $movefile['file'];
            $wp_filetype = wp_check_filetype(basename($filename), null);

            $attachment = [
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                'post_content' => '',
                'post_status' => 'inherit',
                'post_parent' => $post_id,
            ];

            $attachment_id = wp_insert_attachment($attachment, $filename, $post_id);

            if (!is_wp_error($attachment_id)) {
                // Rename file for SEO
                $first_name = get_post_meta($post_id, 'scn_first_name', true);
                $last_name = get_post_meta($post_id, 'scn_last_name', true);
                
                if ($first_name && $last_name) {
                    $image_processor = new ImageProcessor();
                    $new_filename = $image_processor->generatePressKitFilename($first_name, $last_name, basename($filename));
                    
                    $upload_dir = wp_upload_dir();
                    $new_file_path = $upload_dir['path'] . '/' . $new_filename;
                    
                    if (rename($filename, $new_file_path)) {
                        update_attached_file($attachment_id, $new_file_path);
                        wp_update_post([
                            'ID' => $attachment_id,
                            'post_title' => $new_filename,
                        ]);
                    }
                }

                // Add to press kit files
                $press_kit_files = get_post_meta($post_id, 'scn_press_kit_files', true) ?: [];
                $press_kit_files[] = $attachment_id;
                update_post_meta($post_id, 'scn_press_kit_files', $press_kit_files);

                wp_send_json_success([
                    'attachment_id' => $attachment_id,
                    'filename' => get_the_title($attachment_id),
                    'url' => wp_get_attachment_url($attachment_id),
                ]);
            }
        }

        wp_send_json_error($movefile['error'] ?? __('Upload failed.', 'scn-membership'));
    }

    public function handleGalleryReorder() {
        check_ajax_referer('scn_profiles_admin', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $post_id = intval($_POST['post_id']);
        $image_ids = array_map('intval', $_POST['image_ids']);

        if (!$post_id || get_post_type($post_id) !== 'scn_profile') {
            wp_send_json_error(__('Invalid post ID.', 'scn-membership'));
        }

        update_post_meta($post_id, 'scn_gallery_images', $image_ids);
        wp_send_json_success();
    }

    public function handleRemoveGalleryImage() {
        check_ajax_referer('scn_profiles_admin', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $post_id = intval($_POST['post_id']);
        $image_id = intval($_POST['image_id']);

        if (!$post_id || get_post_type($post_id) !== 'scn_profile') {
            wp_send_json_error(__('Invalid post ID.', 'scn-membership'));
        }

        $gallery_images = get_post_meta($post_id, 'scn_gallery_images', true) ?: [];
        $gallery_images = array_diff($gallery_images, [$image_id]);
        update_post_meta($post_id, 'scn_gallery_images', array_values($gallery_images));

        wp_send_json_success();
    }

    public function handleRemovePressKitFile() {
        check_ajax_referer('scn_profiles_admin', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions.', 'scn-membership'));
        }

        $post_id = intval($_POST['post_id']);
        $file_id = intval($_POST['file_id']);

        if (!$post_id || get_post_type($post_id) !== 'scn_profile') {
            wp_send_json_error(__('Invalid post ID.', 'scn-membership'));
        }

        $press_kit_files = get_post_meta($post_id, 'scn_press_kit_files', true) ?: [];
        $press_kit_files = array_diff($press_kit_files, [$file_id]);
        update_post_meta($post_id, 'scn_press_kit_files', array_values($press_kit_files));

        wp_send_json_success();
    }
}


