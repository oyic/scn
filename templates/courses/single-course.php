<?php
/**
 * Single Course Template
 */

get_header(); ?>

<div class="scn-course-single">
    <div class="scn-course-header">
        <div class="scn-course-image">
            <?php echo SCN\Membership\Modules\Courses\FrontendTemplates::getCourseImage(get_the_ID(), 'large'); ?>
        </div>
        
        <div class="scn-course-meta">
            <h1 class="scn-course-title"><?php the_title(); ?></h1>
            
            <?php
            $subtitle = get_post_meta(get_the_ID(), 'scn_course_subtitle', true);
            if ($subtitle) {
                echo '<p class="scn-course-subtitle">' . esc_html($subtitle) . '</p>';
            }
            ?>
            
            <div class="scn-course-badges">
                <?php echo SCN\Membership\Modules\Courses\FrontendTemplates::getCeBadge(get_the_ID()); ?>
            </div>
            
            <?php echo SCN\Membership\Modules\Courses\FrontendTemplates::getCourseTopics(get_the_ID()); ?>
            <?php echo SCN\Membership\Modules\Courses\FrontendTemplates::getCourseFormats(get_the_ID()); ?>
        </div>
    </div>
    
    <div class="scn-course-content">
        <div class="scn-course-description">
            <?php
            $description = get_post_meta(get_the_ID(), 'scn_course_description', true);
            if ($description) {
                echo '<h2>' . __('Course Description', 'scn-membership') . '</h2>';
                echo '<p>' . esc_html($description) . '</p>';
            }
            ?>
        </div>
        
        <?php echo SCN\Membership\Modules\Courses\FrontendTemplates::getCourseOutcomes(get_the_ID()); ?>
        
        <div class="scn-course-body">
            <h2><?php _e('Course Details', 'scn-membership'); ?></h2>
            <?php the_content(); ?>
        </div>
        
        <?php echo SCN\Membership\Modules\Courses\FrontendTemplates::getOnDemandInfo(get_the_ID()); ?>
        
        <div class="scn-course-author">
            <h3><?php _e('Course Instructor', 'scn-membership'); ?></h3>
            <?php
            $author_id = get_post_field('post_author', get_the_ID());
            $author_profile = get_posts([
                'post_type' => 'scn_profile',
                'author' => $author_id,
                'post_status' => 'publish',
                'posts_per_page' => 1
            ]);
            
            if (!empty($author_profile)) {
                $profile = $author_profile[0];
                $first_name = get_post_meta($profile->ID, 'scn_first_name', true);
                $last_name = get_post_meta($profile->ID, 'scn_last_name', true);
                $credentials = get_post_meta($profile->ID, 'scn_credentials', true);
                $location = get_post_meta($profile->ID, 'scn_location', true);
                
                echo '<div class="scn-course-instructor">';
                echo '<h4><a href="' . get_permalink($profile->ID) . '">';
                echo esc_html(trim($first_name . ' ' . $last_name));
                if ($credentials) {
                    echo ', ' . esc_html($credentials);
                }
                echo '</a></h4>';
                
                if ($location) {
                    echo '<p class="scn-instructor-location">' . esc_html($location) . '</p>';
                }
                
                echo '<a href="' . get_permalink($profile->ID) . '" class="scn-view-profile">';
                echo __('View Full Profile', 'scn-membership') . '</a>';
                echo '</div>';
            } else {
                echo '<p>' . __('Instructor profile not available', 'scn-membership') . '</p>';
            }
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>





