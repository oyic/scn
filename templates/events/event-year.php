<?php
/**
 * Event Year Page Template
 * 
 * @package SCN_Membership
 * @var array $event Event post object
 * @var array $event_data Event metadata
 * @var string $year Event year
 * @var array $sessions Approved sessions for this event
 * @var array $speakers Unique speakers for this event
 */

if (!defined('ABSPATH')) {
    exit;
}

$event = $template_data['event'];
$event_data = $template_data['event_data'];
$year = $template_data['year'];
$sessions = $template_data['sessions'];
$speakers = $template_data['speakers'];
?>

<div class="scn-event-year-page">
    <header class="event-header">
        <h1 class="event-title"><?php echo esc_html($event->post_title); ?></h1>
        
        <div class="event-meta">
            <div class="event-year">
                <span class="meta-label"><?php _e('Year:', 'scn-membership'); ?></span>
                <span class="meta-value"><?php echo esc_html($year); ?></span>
            </div>
            
            <?php if (!empty($event_data['dates']['start'])): ?>
                <div class="event-dates">
                    <span class="meta-label"><?php _e('Dates:', 'scn-membership'); ?></span>
                    <span class="meta-value">
                        <?php echo esc_html(date('F j, Y', strtotime($event_data['dates']['start']))); ?>
                        <?php if (!empty($event_data['dates']['end'])): ?>
                            - <?php echo esc_html(date('F j, Y', strtotime($event_data['dates']['end']))); ?>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($event_data['location']['city'])): ?>
                <div class="event-location">
                    <span class="meta-label"><?php _e('Location:', 'scn-membership'); ?></span>
                    <span class="meta-value">
                        <?php echo esc_html($event_data['location']['city']); ?>
                        <?php if (!empty($event_data['location']['region'])): ?>
                            , <?php echo esc_html($event_data['location']['region']); ?>
                        <?php endif; ?>
                        <?php if (!empty($event_data['location']['country'])): ?>
                            , <?php echo esc_html($event_data['location']['country']); ?>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($event_data['website'])): ?>
                <div class="event-website">
                    <a href="<?php echo esc_url($event_data['website']); ?>" 
                       target="_blank" 
                       rel="noopener" 
                       class="event-website-link">
                        <?php _e('Visit Event Website', 'scn-membership'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($event->post_content): ?>
            <div class="event-description">
                <?php echo wp_kses_post($event->post_content); ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if (!empty($speakers)): ?>
        <section class="event-speakers">
            <h2 class="section-title"><?php _e('Speakers', 'scn-membership'); ?></h2>
            <div class="speakers-grid">
                <?php foreach ($speakers as $speaker): ?>
                    <div class="speaker-card">
                        <div class="speaker-photo">
                            <?php if ($speaker['headshot']): ?>
                                <img src="<?php echo esc_url($speaker['headshot']); ?>" 
                                     alt="<?php echo esc_attr($speaker['name']); ?>" 
                                     class="speaker-headshot">
                            <?php else: ?>
                                <div class="speaker-placeholder">
                                    <span class="placeholder-text"><?php _e('Photo', 'scn-membership'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="speaker-info">
                            <h3 class="speaker-name">
                                <a href="<?php echo esc_url($speaker['url']); ?>">
                                    <?php echo esc_html($speaker['name']); ?>
                                </a>
                            </h3>
                            
                            <?php if ($speaker['bio']): ?>
                                <p class="speaker-bio"><?php echo esc_html($speaker['bio']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($sessions)): ?>
        <section class="event-sessions">
            <h2 class="section-title"><?php _e('Sessions', 'scn-membership'); ?></h2>
            <div class="sessions-list">
                <?php foreach ($sessions as $session): ?>
                    <div class="session-item">
                        <div class="session-header">
                            <h3 class="session-title"><?php echo esc_html($session->session_title); ?></h3>
                            <div class="session-datetime">
                                <?php echo esc_html(date('F j, Y \a\t g:i A', strtotime($session->session_datetime))); ?>
                            </div>
                        </div>
                        
                        <div class="session-meta">
                            <div class="session-speaker">
                                <span class="meta-label"><?php _e('Speaker:', 'scn-membership'); ?></span>
                                <a href="<?php echo esc_url($session->profile_url); ?>" class="speaker-link">
                                    <?php echo esc_html($session->profile_name); ?>
                                </a>
                            </div>
                            
                            <div class="session-course">
                                <span class="meta-label"><?php _e('Course:', 'scn-membership'); ?></span>
                                <span class="course-name"><?php echo esc_html($session->course_title); ?></span>
                            </div>
                            
                            <?php if ($session->room): ?>
                                <div class="session-room">
                                    <span class="meta-label"><?php _e('Room:', 'scn-membership'); ?></span>
                                    <span class="room-name"><?php echo esc_html($session->room); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($session->type_override): ?>
                                <div class="session-type">
                                    <span class="meta-label"><?php _e('Type:', 'scn-membership'); ?></span>
                                    <span class="type-name"><?php echo esc_html($session->type_override); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php else: ?>
        <section class="event-sessions">
            <h2 class="section-title"><?php _e('Sessions', 'scn-membership'); ?></h2>
            <div class="no-sessions">
                <p><?php _e('No sessions have been scheduled for this event yet.', 'scn-membership'); ?></p>
            </div>
        </section>
    <?php endif; ?>
</div>

<style>
.scn-event-year-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.event-header {
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid #e0e0e0;
}

.event-title {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #333;
}

.event-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.meta-label {
    font-weight: bold;
    color: #666;
}

.event-website-link {
    color: #0073aa;
    text-decoration: none;
}

.event-website-link:hover {
    text-decoration: underline;
}

.event-description {
    margin-top: 1.5rem;
    font-size: 1.1rem;
    line-height: 1.6;
}

.section-title {
    font-size: 2rem;
    margin: 3rem 0 2rem 0;
    color: #333;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 0.5rem;
}

.speakers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.speaker-card {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.speaker-photo {
    flex-shrink: 0;
    width: 80px;
    height: 80px;
}

.speaker-headshot {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.speaker-placeholder {
    width: 100%;
    height: 100%;
    background: #f0f0f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.placeholder-text {
    color: #999;
    font-size: 0.8rem;
}

.speaker-name {
    margin: 0 0 0.5rem 0;
    font-size: 1.2rem;
}

.speaker-name a {
    color: #333;
    text-decoration: none;
}

.speaker-name a:hover {
    color: #0073aa;
}

.speaker-bio {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
    line-height: 1.4;
}

.sessions-list {
    display: grid;
    gap: 1.5rem;
}

.session-item {
    padding: 2rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.session-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 1rem;
}

.session-title {
    margin: 0;
    font-size: 1.4rem;
    color: #333;
    flex: 1;
}

.session-datetime {
    color: #666;
    font-weight: bold;
    white-space: nowrap;
}

.session-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.5rem;
}

.session-meta > div {
    display: flex;
    gap: 0.5rem;
}

.speaker-link {
    color: #0073aa;
    text-decoration: none;
}

.speaker-link:hover {
    text-decoration: underline;
}

.no-sessions {
    text-align: center;
    padding: 3rem;
    color: #666;
    font-style: italic;
}

@media (max-width: 768px) {
    .scn-event-year-page {
        padding: 1rem;
    }
    
    .event-title {
        font-size: 2rem;
    }
    
    .speaker-card {
        flex-direction: column;
        text-align: center;
    }
    
    .session-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .session-meta {
        grid-template-columns: 1fr;
    }
}
</style>




