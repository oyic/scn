<?php

namespace SCN\Membership\Modules\Courses;

class AdminInterface {
    public function register() {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_action('admin_init', [$this, 'addImageUploaderScript']);
    }

    public function enqueueAdminScripts($hook) {
        global $post_type;
        
        if ($post_type === 'scn_course' && in_array($hook, ['post.php', 'post-new.php'])) {
            wp_enqueue_media();
            wp_enqueue_script('scn-courses-admin', plugin_dir_url(__FILE__) . '../../../assets/js/courses-admin.js', ['jquery'], '1.0.0', true);
            wp_enqueue_style('scn-courses-admin', plugin_dir_url(__FILE__) . '../../../assets/css/courses-admin.css', [], '1.0.0');
            
            wp_localize_script('scn-courses-admin', 'scnCoursesAdmin', [
                'selectImageTitle' => __('Select Course Image', 'scn-membership'),
                'useImageText' => __('Use this image', 'scn-membership'),
                'outcomePlaceholder' => __('Learning outcome', 'scn-membership'),
                'removeText' => __('Remove', 'scn-membership'),
                'ceHoursError' => __('CE hours must be at least 0.5 when CE credits are enabled', 'scn-membership'),
                'outcomesError' => __('At least one learning outcome is required', 'scn-membership'),
                'linkError' => __('On-demand link must be a valid URL', 'scn-membership'),
            ]);
        }
    }

    public function addImageUploaderScript() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            let courseImageUploader;
            
            $('#scn_course_upload_image').click(function(e) {
                e.preventDefault();
                
                if (courseImageUploader) {
                    courseImageUploader.open();
                    return;
                }
                
                courseImageUploader = wp.media({
                    title: '<?php _e('Select Course Image', 'scn-membership'); ?>',
                    button: {
                        text: '<?php _e('Use this image', 'scn-membership'); ?>'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                courseImageUploader.on('select', function() {
                    const attachment = courseImageUploader.state().get('selection').first().toJSON();
                    $('#scn_course_image_id').val(attachment.id);
                    $('#scn_course_image_preview').html('<img src="' + attachment.sizes.medium.url + '" alt="' + attachment.alt + '" />');
                    $('#scn_course_remove_image').show();
                });
                
                courseImageUploader.open();
            });
            
            $('#scn_course_remove_image').click(function(e) {
                e.preventDefault();
                $('#scn_course_image_id').val('');
                $('#scn_course_image_preview').empty();
                $(this).hide();
            });
        });
        </script>
        <?php
    }
}
