<?php
/**
 * Course Archive Template
 */

get_header(); ?>

<div class="scn-courses-archive">
    <header class="scn-courses-header">
        <h1><?php post_type_archive_title(); ?></h1>
        <?php
        $description = get_the_archive_description();
        if ($description) {
            echo '<div class="scn-archive-description">' . $description . '</div>';
        }
        ?>
    </header>
    
    <div class="scn-courses-filters">
        <?php
        $topics = get_terms(['taxonomy' => 'scn_topic', 'hide_empty' => true]);
        if (!empty($topics)) {
            echo '<div class="scn-topic-filters">';
            echo '<h3>' . __('Filter by Topic', 'scn-membership') . '</h3>';
            echo '<div class="scn-topic-filter-list">';
            foreach ($topics as $topic) {
                $current = isset($_GET['topic']) && $_GET['topic'] == $topic->slug;
                $class = $current ? 'scn-topic-filter active' : 'scn-topic-filter';
                echo '<a href="' . add_query_arg('topic', $topic->slug) . '" class="' . $class . '">';
                echo esc_html($topic->name);
                echo '</a>';
            }
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
    
    <div class="scn-courses-grid">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <article class="scn-course-card">
                    <div class="scn-course-image">
                        <a href="<?php the_permalink(); ?>">
                            <?php echo SCN\Membership\Modules\Courses\FrontendTemplates::getCourseImage(get_the_ID(), 'medium'); ?>
                        </a>
                    </div>
                    
                    <div class="scn-course-content">
                        <h2 class="scn-course-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        
                        <?php
                        $subtitle = get_post_meta(get_the_ID(), 'scn_course_subtitle', true);
                        if ($subtitle) {
                            echo '<p class="scn-course-subtitle">' . esc_html($subtitle) . '</p>';
                        }
                        ?>
                        
                        <div class="scn-course-meta">
                            <?php echo SCN\Membership\Modules\Courses\FrontendTemplates::getCourseTopics(get_the_ID()); ?>
                            <?php echo SCN\Membership\Modules\Courses\FrontendTemplates::getCourseFormats(get_the_ID()); ?>
                        </div>
                        
                        <div class="scn-course-badges">
                            <?php echo SCN\Membership\Modules\Courses\FrontendTemplates::getCeBadge(get_the_ID()); ?>
                        </div>
                        
                        <div class="scn-course-excerpt">
                            <?php
                            $description = get_post_meta(get_the_ID(), 'scn_course_description', true);
                            if ($description) {
                                echo '<p>' . wp_trim_words($description, 20) . '</p>';
                            } else {
                                the_excerpt();
                            }
                            ?>
                        </div>
                        
                        <div class="scn-course-author">
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
                                
                                echo '<p class="scn-course-instructor">';
                                echo '<strong>' . __('Instructor:', 'scn-membership') . '</strong> ';
                                echo '<a href="' . get_permalink($profile->ID) . '">';
                                echo esc_html(trim($first_name . ' ' . $last_name));
                                if ($credentials) {
                                    echo ', ' . esc_html($credentials);
                                }
                                echo '</a>';
                                echo '</p>';
                            }
                            ?>
                        </div>
                        
                        <div class="scn-course-actions">
                            <a href="<?php the_permalink(); ?>" class="scn-course-link">
                                <?php _e('View Course Details', 'scn-membership'); ?>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <div class="scn-no-courses">
                <h2><?php _e('No courses found', 'scn-membership'); ?></h2>
                <p><?php _e('No courses match your current filters.', 'scn-membership'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php
    the_posts_pagination([
        'prev_text' => __('Previous', 'scn-membership'),
        'next_text' => __('Next', 'scn-membership'),
    ]);
    ?>
</div>

<?php get_footer(); ?>





