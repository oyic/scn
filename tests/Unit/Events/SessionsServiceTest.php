<?php

namespace SCN\Membership\Tests\Unit\Events;

use SCN\Membership\Modules\Events\SessionsService;
use SCN\Membership\Tests\Unit\TestCase;

class SessionsServiceTest extends TestCase {
    private $sessions_service;

    public function setUp(): void {
        parent::setUp();
        $this->sessions_service = new SessionsService();
    }

    public function testCreateSessionWithValidData() {
        $event_id = $this->createEvent();
        $course_id = $this->createCourse();
        $profile_id = $this->createProfile();

        $data = [
            'event_id' => $event_id,
            'course_id' => $course_id,
            'profile_id' => $profile_id,
            'session_title' => 'Test Session',
            'session_datetime' => '2024-06-15 10:00:00',
            'room' => 'Room A',
            'type_override' => 'Workshop',
        ];

        $result = $this->sessions_service->createSession($data);

        $this->assertNotInstanceOf(\WP_Error::class, $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testCreateSessionWithMissingRequiredFields() {
        $data = [
            'event_id' => 1,
            'session_title' => 'Test Session',
        ];

        $result = $this->sessions_service->createSession($data);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('missing_required_fields', $result->get_error_code());
    }

    public function testCreateSessionWithInvalidEvent() {
        $course_id = $this->createCourse();
        $profile_id = $this->createProfile();

        $data = [
            'event_id' => 99999,
            'course_id' => $course_id,
            'profile_id' => $profile_id,
            'session_title' => 'Test Session',
            'session_datetime' => '2024-06-15 10:00:00',
        ];

        $result = $this->sessions_service->createSession($data);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('invalid_event', $result->get_error_code());
    }

    public function testCreateSessionWithSessionDateBeforeEvent() {
        $event_id = $this->createEvent(['start' => '2024-06-20', 'end' => '2024-06-22']);
        $course_id = $this->createCourse();
        $profile_id = $this->createProfile();

        $data = [
            'event_id' => $event_id,
            'course_id' => $course_id,
            'profile_id' => $profile_id,
            'session_title' => 'Test Session',
            'session_datetime' => '2024-06-15 10:00:00',
        ];

        $result = $this->sessions_service->createSession($data);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('session_before_event', $result->get_error_code());
    }

    public function testCreateSessionWithSessionDateAfterEvent() {
        $event_id = $this->createEvent(['start' => '2024-06-10', 'end' => '2024-06-12']);
        $course_id = $this->createCourse();
        $profile_id = $this->createProfile();

        $data = [
            'event_id' => $event_id,
            'course_id' => $course_id,
            'profile_id' => $profile_id,
            'session_title' => 'Test Session',
            'session_datetime' => '2024-06-15 10:00:00',
        ];

        $result = $this->sessions_service->createSession($data);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('session_after_event', $result->get_error_code());
    }

    public function testGetSessionsByEvent() {
        $event_id = $this->createEvent();
        $course_id = $this->createCourse();
        $profile_id = $this->createProfile();

        $this->createSession($event_id, $course_id, $profile_id);

        $sessions = $this->sessions_service->getSessionsByEvent($event_id);

        $this->assertCount(1, $sessions);
        $this->assertEquals('Test Session', $sessions[0]->session_title);
    }

    public function testGetSessionsByProfile() {
        $event_id = $this->createEvent();
        $course_id = $this->createCourse();
        $profile_id = $this->createProfile();

        $this->createSession($event_id, $course_id, $profile_id);

        $sessions = $this->sessions_service->getSessionsByProfile($profile_id);

        $this->assertCount(1, $sessions);
        $this->assertEquals('Test Session', $sessions[0]->session_title);
    }

    public function testGetUpcomingSessions() {
        $event_id = $this->createEvent();
        $course_id = $this->createCourse();
        $profile_id = $this->createProfile();

        $this->createSession($event_id, $course_id, $profile_id, [
            'session_datetime' => date('Y-m-d H:i:s', strtotime('+1 week'))
        ]);

        $sessions = $this->sessions_service->getUpcomingSessions();

        $this->assertCount(1, $sessions);
        $this->assertEquals('Test Session', $sessions[0]->session_title);
    }

    private function createEvent($dates = ['start' => '2024-06-15', 'end' => '2024-06-17']) {
        $event_id = wp_insert_post([
            'post_title' => 'Test Event',
            'post_type' => 'scn_event',
            'post_status' => 'publish',
        ]);

        update_post_meta($event_id, 'scn_event_dates', $dates);
        update_post_meta($event_id, 'scn_event_year', '2024');

        return $event_id;
    }

    private function createCourse() {
        return wp_insert_post([
            'post_title' => 'Test Course',
            'post_type' => 'scn_course',
            'post_status' => 'publish',
        ]);
    }

    private function createProfile() {
        return wp_insert_post([
            'post_title' => 'Test Profile',
            'post_type' => 'scn_profile',
            'post_status' => 'publish',
        ]);
    }

    private function createSession($event_id, $course_id, $profile_id, $overrides = []) {
        global $wpdb;

        $data = array_merge([
            'event_id' => $event_id,
            'course_id' => $course_id,
            'profile_id' => $profile_id,
            'session_title' => 'Test Session',
            'session_datetime' => '2024-06-15 10:00:00',
            'status' => 'approved',
        ], $overrides);

        $table_name = $wpdb->prefix . 'scn_event_sessions';
        $wpdb->insert($table_name, $data);

        return $wpdb->insert_id;
    }
}




