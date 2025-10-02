<?php
/**
 * Example template for displaying ACF-managed profiles
 * This shows how to use the new ACF profile structure
 */

// Load the ACF Profile Helper
use SCN\Membership\ACF\ACFProfileHelper;

// Get the profile data
$profile_id = get_the_ID();
$profile_data = ACFProfileHelper::getAllProfileData($profile_id);

// Or get individual fields
$basic_info = ACFProfileHelper::getBasicInfo($profile_id);
$full_name = ACFProfileHelper::getFullName($profile_id);
$bio = ACFProfileHelper::getBio($profile_id);
$social_links = ACFProfileHelper::getSocialLinks($profile_id);
$featured_video = ACFProfileHelper::getFeaturedVideo($profile_id);
$gallery_images = ACFProfileHelper::getGalleryImages($profile_id);
$services = ACFProfileHelper::getServices($profile_id);
$badges = ACFProfileHelper::getBadges($profile_id);
$topics = ACFProfileHelper::getTopics($profile_id);
$completion = ACFProfileHelper::getCompletionPercentage($profile_id);
?>

<div class="scn-profile-acf">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-photo">
            <?php if (has_post_thumbnail()): ?>
                <?php the_post_thumbnail('medium', ['class' => 'profile-image']); ?>
            <?php else: ?>
                <div class="no-photo">No Photo</div>
            <?php endif; ?>
        </div>
        
        <div class="profile-info">
            <h1 class="profile-name"><?php echo esc_html($full_name); ?></h1>
            
            <?php if (!empty($basic_info['credentials'])): ?>
                <p class="credentials"><?php echo esc_html($basic_info['credentials']); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($basic_info['location'])): ?>
                <p class="location">📍 <?php echo esc_html($basic_info['location']); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($basic_info['main_url'])): ?>
                <p class="website">
                    <a href="<?php echo esc_url($basic_info['main_url']); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html($basic_info['main_url']); ?>
                    </a>
                </p>
            <?php endif; ?>
            
            <div class="profile-completion">
                <span class="completion-label">Profile Completion:</span>
                <div class="completion-bar">
                    <div class="completion-fill" style="width: <?php echo $completion; ?>%"></div>
                </div>
                <span class="completion-percentage"><?php echo $completion; ?>%</span>
            </div>
        </div>
    </div>

    <!-- Bio Section -->
    <?php if (!empty($bio)): ?>
        <div class="profile-section">
            <h2>About</h2>
            <div class="bio-content">
                <?php echo wp_kses_post($bio); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Social Links -->
    <?php if (!empty($social_links)): ?>
        <div class="profile-section">
            <h2>Connect</h2>
            <div class="social-links">
                <?php foreach ($social_links as $link): ?>
                    <?php if (!empty($link['url'])): ?>
                        <a href="<?php echo esc_url($link['url']); ?>" 
                           target="_blank" 
                           rel="noopener"
                           class="social-link social-<?php echo esc_attr($link['platform']); ?>">
                            <?php echo esc_html(ucfirst($link['platform'])); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Featured Video -->
    <?php if (!empty($featured_video['url'])): ?>
        <div class="profile-section">
            <h2>Featured Video</h2>
            <div class="featured-video">
                <?php
                // Simple video embed (you might want to use a more sophisticated embed method)
                $video_url = $featured_video['url'];
                if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                    // Handle YouTube URLs
                    echo '<div class="video-container">';
                    echo '<iframe src="' . esc_url($video_url) . '" frameborder="0" allowfullscreen></iframe>';
                    echo '</div>';
                } else {
                    // Fallback to link
                    echo '<a href="' . esc_url($video_url) . '" target="_blank" rel="noopener">Watch Video</a>';
                }
                ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Services -->
    <?php if (!empty($services)): ?>
        <div class="profile-section">
            <h2>Services Offered</h2>
            <div class="services-list">
                <?php foreach ($services as $service): ?>
                    <div class="service-item">
                        <h3><?php echo esc_html($service['name']); ?></h3>
                        <?php if (!empty($service['description'])): ?>
                            <p><?php echo esc_html($service['description']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Badges -->
    <?php if (!empty($badges)): ?>
        <div class="profile-section">
            <h2>Recognition & Badges</h2>
            <div class="badges-list">
                <?php foreach ($badges as $badge): ?>
                    <div class="badge-item">
                        <?php if (!empty($badge['image'])): ?>
                            <img src="<?php echo esc_url($badge['image']); ?>" 
                                 alt="<?php echo esc_attr($badge['name']); ?>" 
                                 class="badge-image">
                        <?php endif; ?>
                        <div class="badge-info">
                            <h4><?php echo esc_html($badge['name']); ?></h4>
                            <?php if (!empty($badge['url'])): ?>
                                <a href="<?php echo esc_url($badge['url']); ?>" target="_blank" rel="noopener">Learn More</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Topics/Expertise -->
    <?php if (!empty($topics)): ?>
        <div class="profile-section">
            <h2>Areas of Expertise</h2>
            <div class="topics-list">
                <?php foreach ($topics as $topic): ?>
                    <span class="topic-tag">
                        <?php echo esc_html(is_object($topic) ? $topic->name : $topic); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Photo Gallery -->
    <?php if (!empty($gallery_images)): ?>
        <div class="profile-section">
            <h2>Photo Gallery</h2>
            <div class="gallery-grid">
                <?php foreach ($gallery_images as $image): ?>
                    <div class="gallery-item">
                        <img src="<?php echo esc_url($image['sizes']['medium'] ?? $image['url']); ?>" 
                             alt="<?php echo esc_attr($image['alt'] ?? ''); ?>"
                             class="gallery-image">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Press Kit Files -->
    <?php 
    $press_kit_files = ACFProfileHelper::getPressKitFiles($profile_id);
    if (!empty($press_kit_files)): ?>
        <div class="profile-section">
            <h2>Press Kit & Resources</h2>
            <div class="press-kit-files">
                <?php foreach ($press_kit_files as $file): ?>
                    <div class="press-kit-item">
                        <a href="<?php echo esc_url($file['url']); ?>" 
                           target="_blank" 
                           rel="noopener"
                           class="press-kit-link">
                            📄 <?php echo esc_html($file['title'] ?? basename($file['url'])); ?>
                        </a>
                        <span class="file-size">(<?php echo size_format($file['filesize'] ?? 0); ?>)</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.scn-profile-acf {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.profile-header {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.profile-photo {
    flex-shrink: 0;
}

.profile-image {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
}

.profile-info {
    flex: 1;
}

.profile-name {
    margin: 0 0 10px 0;
    color: #333;
}

.credentials {
    font-style: italic;
    color: #666;
    margin: 5px 0;
}

.location, .website {
    margin: 5px 0;
    color: #666;
}

.profile-completion {
    margin-top: 15px;
}

.completion-bar {
    width: 200px;
    height: 20px;
    background: #e0e0e0;
    border-radius: 10px;
    overflow: hidden;
    display: inline-block;
    margin: 0 10px;
}

.completion-fill {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #8BC34A);
    transition: width 0.3s ease;
}

.profile-section {
    margin-bottom: 40px;
    padding: 20px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}

.social-links {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.social-link {
    padding: 8px 16px;
    background: #0073aa;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.social-link:hover {
    background: #005a87;
}

.services-list, .badges-list {
    display: grid;
    gap: 15px;
}

.service-item, .badge-item {
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.topics-list {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.topic-tag {
    padding: 4px 12px;
    background: #e3f2fd;
    color: #1976d2;
    border-radius: 16px;
    font-size: 14px;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.gallery-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 4px;
}

.press-kit-files {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.press-kit-link {
    color: #0073aa;
    text-decoration: none;
}

.press-kit-link:hover {
    text-decoration: underline;
}

.file-size {
    color: #666;
    font-size: 12px;
}
</style>
