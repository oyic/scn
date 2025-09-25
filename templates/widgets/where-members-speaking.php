<?php
/**
 * Where Our Members Are Speaking Widget Template
 * 
 * @package SCN_Membership
 * @var array $sessions Upcoming sessions data
 * @var string $title Widget title
 * @var bool $show_dates Whether to show event dates
 * @var bool $show_events Whether to show event names
 */

if (!defined('ABSPATH')) {
    exit;
}

$sessions = $template_data['sessions'];
$title = $template_data['title'];
$show_dates = $template_data['show_dates'];
$show_events = $template_data['show_events'];

if (empty($sessions)) {
    return;
}
?>

<div class="scn-members-speaking-widget">
    <?php if ($title): ?>
        <h3 class="widget-title"><?php echo esc_html($title); ?></h3>
    <?php endif; ?>
    
    <div class="members-speaking-grid">
        <?php foreach ($sessions as $session): ?>
            <div class="member-speaking-card">
                <div class="member-photo">
                    <a href="<?php echo esc_url($session->profile_url); ?>" 
                       title="<?php echo esc_attr($session->profile_name); ?>">
                        <img src="<?php echo esc_url($session->headshot); ?>" 
                             alt="<?php echo esc_attr($session->profile_name); ?>" 
                             class="member-headshot">
                    </a>
                </div>
                
                <div class="member-info">
                    <h4 class="member-name">
                        <a href="<?php echo esc_url($session->profile_url); ?>">
                            <?php echo esc_html($session->profile_name); ?>
                        </a>
                    </h4>
                    
                    <?php if ($show_events): ?>
                        <p class="event-name">
                            <a href="<?php echo esc_url($session->event_url); ?>" 
                               title="<?php echo esc_attr($session->event_name); ?>">
                                <?php echo esc_html($session->event_name); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($show_dates): ?>
                        <p class="event-date">
                            <?php echo esc_html(date('M j, Y', strtotime($session->session_datetime))); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>




