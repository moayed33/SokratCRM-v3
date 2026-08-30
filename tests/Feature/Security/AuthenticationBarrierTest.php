<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Tests\TestCase;

class AuthenticationBarrierTest extends TestCase
{
    public function test_guests_are_redirected_to_login_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_guests_cannot_access_leads_endpoints(): void
    {
        $this->get('/leads')->assertRedirect(route('login'));
        $this->get('/leads/create')->assertRedirect(route('login'));
        $this->get('/leads/1')->assertRedirect(route('login'));
        $this->get('/leads/1/edit')->assertRedirect(route('login'));
        $this->post('/leads', [])->assertRedirect(route('login'));
        $this->patch('/leads/1', [])->assertRedirect(route('login'));
        $this->delete('/leads/1')->assertRedirect(route('login'));
        $this->get('/leads/kanban')->assertRedirect(route('login'));
        $this->get('/leads/import')->assertRedirect(route('login'));
        $this->get('/leads/export')->assertRedirect(route('login'));
        $this->post('/leads/export-selected', [])->assertRedirect(route('login'));
        $this->get('/leads/1/followups')->assertRedirect(route('login'));
        $this->post('/leads/1/followups', [])->assertRedirect(route('login'));
    }

    public function test_guests_cannot_access_tasks_and_calendar_endpoints(): void
    {
        $this->get('/tasks/daily')->assertRedirect(route('login'));
        $this->post('/tasks/leads/1/reschedule', [])->assertRedirect(route('login'));
        $this->get('/calendar')->assertRedirect(route('login'));
        $this->get('/calendar/events')->assertRedirect(route('login'));
        $this->get('/calendar/events/1')->assertRedirect(route('login'));
        $this->post('/calendar/events', [])->assertRedirect(route('login'));
        $this->patch('/calendar/events/1', [])->assertRedirect(route('login'));
        $this->delete('/calendar/events/1')->assertRedirect(route('login'));
    }

    public function test_guests_cannot_access_campaigns_and_reports_endpoints(): void
    {
        $this->get('/campaigns')->assertRedirect(route('login'));
        $this->get('/campaigns/create')->assertRedirect(route('login'));
        $this->get('/campaigns/1')->assertRedirect(route('login'));
        $this->get('/campaigns/1/edit')->assertRedirect(route('login'));
        $this->post('/campaigns', [])->assertRedirect(route('login'));
        $this->patch('/campaigns/1', [])->assertRedirect(route('login'));
        $this->delete('/campaigns/1')->assertRedirect(route('login'));
        $this->get('/campaigns/reports')->assertRedirect(route('login'));
        $this->get('/reports/employees')->assertRedirect(route('login'));
        $this->get('/reports/voip')->assertRedirect(route('login'));
    }

    public function test_guests_cannot_access_collections_and_donations_endpoints(): void
    {
        $this->get('/collections')->assertRedirect(route('login'));
        $this->get('/collections/1')->assertRedirect(route('login'));
        $this->get('/collections/1/receipt')->assertRedirect(route('login'));
        $this->patch('/collections/1/assign', [])->assertRedirect(route('login'));
        $this->post('/collections/1/complete', [])->assertRedirect(route('login'));
        $this->patch('/collections/1/cancel', [])->assertRedirect(route('login'));
        $this->get('/collections/methods/settings')->assertRedirect(route('login'));
        $this->get('/leads/1/donations/1/receipt')->assertRedirect(route('login'));
    }

    public function test_guests_cannot_access_voip_and_notifications_endpoints(): void
    {
        $this->get('/voip/live')->assertRedirect(route('login'));
        $this->get('/voip/live/data')->assertRedirect(route('login'));
        $this->get('/leads/1/calls')->assertRedirect(route('login'));
        $this->get('/my/calls')->assertRedirect(route('login'));
        $this->get('/notifications')->assertRedirect(route('login'));
        $this->get('/notifications/unread-count')->assertRedirect(route('login'));
        $this->get('/notifications/stream')->assertRedirect(route('login'));
        $this->get('/my/notifications')->assertRedirect(route('login'));
    }

    public function test_guests_cannot_access_settings_endpoints(): void
    {
        $this->get('/settings')->assertRedirect(route('login'));
        $this->get('/settings/branches')->assertRedirect(route('login'));
        $this->get('/settings/users')->assertRedirect(route('login'));
        $this->get('/settings/groups')->assertRedirect(route('login'));
        $this->get('/settings/permissions')->assertRedirect(route('login'));
        $this->get('/settings/stages')->assertRedirect(route('login'));
        $this->get('/settings/stages/1/fields')->assertRedirect(route('login'));
        $this->get('/settings/lead-fields')->assertRedirect(route('login'));
        $this->get('/settings/option-sets')->assertRedirect(route('login'));
        $this->get('/settings/notifications')->assertRedirect(route('login'));
        $this->get('/settings/voip')->assertRedirect(route('login'));
    }
}
