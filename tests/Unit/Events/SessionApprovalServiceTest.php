<?php

namespace SCN\Membership\Tests\Unit\Events;

use SCN\Membership\Modules\Events\SessionApprovalService;
use SCN\Membership\Tests\Unit\TestCase;

class SessionApprovalServiceTest extends TestCase {
    private $approval_service;
    private $sessions_service;

    public function setUp(): void {
        parent::setUp();
        $this->approval_service = new SessionApprovalService();
        $this->sessions_service = new SessionsService();
    }

    public function testApproveSession() {
        $this->setCurrentUser('administrator');
        
        $event_id = $this->createEvent();
        $course_id = $this->createCourse();
        $profile_id = $this->createProfile();
        $session_id = $this->createSession($event_id, $course_id, $profile_id, ['status' => 'pending']);

        $result = $this->approval_service->approveSession($session_id);

        $this->assertNotInstanceOf(\WP_Error::class, $result);
        $this->assertArrayHasKey('message', $result);

        $session = $this->sessions_service->getSession($session_id);
        $this->assertEquals('approved', $session->status);
    }

    public function testApproveSessionWithoutPermissions() {
        $this->setCurrentUser('subscriber');
        
        $event_id = $this->createEvent();
        $course_id = $this->createCourse();
        $profile_id = $this->createProfile();
        $session_id = $this->createSession($event_id, $course_id, $profile_id);

        $result = $this->approval_service->approveSession($session_id);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('insufficient_permissions', $result->get_error_code());
    }

    public function testRejectSession() {
        $this->setCurrentUser('administrator');
        
        $event_id = $this->createEvent();
        $course_id = $this->createCourse();
        $profile_id = $this->createProfile();
        $session_id = $this->createSession($event_id, $course_id, $profile_id, ['status' => 'pending']);

        $result = $this->approval_service->rejectSession($session_id, 'Not suitable');

        $this->assertNotInstanceOf(\WP_Error::class, $result);
        $this->assertArrayHasKey('message', $result);

        $session = $this->sessions_service->getSession($session_id);
        $this->assertEquals('rejected', $session->status);
    }

    public function testGetPendingSessions() {
        $this->setCurrentUser('administrator');
        
        $event_id = $this->createEvent();
        $course_id = $this->createCourse();
        $profile_id = $this->createProfile();
        
        $this->createSession($event_id, $course_id, $profile_id, ['status' => 'pending']);
        $this->createSession($event_id, $course_id, $profile_id, ['status' => 'approved']);

        $sessions = $this->approval_service->getPendingSessions();

        $this->assertCount(1, $sessions);
        $this->assertEquals('pending', $sessions[0]->status);
    }

    public function testBulkApproveSessions() {
        $this->setCurrentUser('administrator');
        
        $event_id = $this->createEvent();
        $course_id = $this->createCourse();
        $profile_id = $this->createProfile();
        
        $session1 = $this->createSession($event_id, $course_id, $profile_id, ['status' => 'pending']);
        $session2 = $this->createSession($event_id, $course_id, $profile_id, ['status' => 'pending']);

        $result = $this->approval_service->bulkApproveSessions([$session1, $session2]);

        $this->assertNotInstanceOf(\WP_Error::class, $result);
        $this->assertEquals(2, $result['approved_count']);

        $session1_data = $this->sessions_service->getSession($session1);
        $session2_data = $this->sessions_service->getSession($session2);
        
        $this->assertEquals('approved', $session1_data->status);
        $this->assertEquals('approved', $session2_data->status);
    }

    public function testGetSessionStats() {
        $this->setCurrentUser('administrator');
        
        $event_id = $this->createEvent();
        $course_id = $this->createCourse();
        $profile_id = $this->createProfile();
        
        $this->createSession($event_id, $course_id, $profile_id, ['status' => 'pending']);
        $this->createSession($event_id, $course_id, $profile_id, ['status' => 'approved']);
        $this->createSession($event_id, $course_id, $profile_id, ['status' => 'rejected']);

        $stats = $this->approval_service->getSessionStats();

        $this->assertNotInstanceOf(\WP_Error::class, $stats);
        $this->assertEquals(3, $stats->total);
        $this->assertEquals(1, $stats->pending);
        $this->assertEquals(1, $stats->approved);
        $this->assertEquals(1, $stats->rejected);
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




