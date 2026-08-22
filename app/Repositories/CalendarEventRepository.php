<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CalendarEvent;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CalendarEventRepository
{
    /**
     * Get accessible calendar events with filters.
     *
     * @return Collection<int, CalendarEvent>
     */
    public function getEventsForUser(
        User $user,
        ?string $start = null,
        ?string $end = null,
        array $filters = []
    ): Collection {
        $query = CalendarEvent::query()
            ->with([
                'user:id,name,username,branch_id',
                'branch:id,name_ar,name_en,code',
                'lead:id,name,first_name,last_name,company_name,phone,branch_id,lead_status_id,donation_type,donation_type_id,donation_cycle,donation_value,donation_purpose,donation_purpose_id,response_details,contact_date,next_follow_up_at,assigned_user_id',
                'lead.branch:id,name_ar,name_en,code',
                'lead.status:id,name_ar,color,pipeline_stage_id',
                'lead.status.stage:id,name_ar,color',
                'lead.donationTypeRel:id,name_ar,name_en',
                'lead.donationPurposeRel:id,name_ar,name_en',
                'lead.primaryPhone:id,lead_id,phone,is_primary',
            ])
            ->accessibleTo($user);

        if ($start && $end) {
            $query->between($start, $end);
        }

        if (! empty($filters['branch_id']) && $filters['branch_id'] !== 'all') {
            $query->forBranch((int) $filters['branch_id']);
        }

        if (! empty($filters['type']) && $filters['type'] !== 'all') {
            $query->ofType((string) $filters['type']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->withStatus((string) $filters['status']);
        }

        if (! empty($filters['user_id'])) {
            $query->forUser((int) $filters['user_id']);
        }

        if (! empty($filters['lead_id'])) {
            $query->forLead((int) $filters['lead_id']);
        }

        return $query->orderBy('start_time', 'asc')->get();
    }

    /**
     * Get accessible lead followups as scheduled calendar items.
     *
     * @return Collection<int, Lead>
     */
    public function getLeadFollowupsForUser(
        User $user,
        ?string $start = null,
        ?string $end = null,
        array $filters = []
    ): Collection {
        $query = Lead::query()
            ->accessibleTo($user)
            ->whereNotNull('next_follow_up_at');

        if ($start && $end) {
            $query->whereBetween('next_follow_up_at', [$start, $end]);
        }

        if (! empty($filters['branch_id']) && $filters['branch_id'] !== 'all') {
            $query->forBranch((int) $filters['branch_id']);
        }

        if (! empty($filters['user_id'])) {
            $userId = (int) $filters['user_id'];
            $query->where(function ($q) use ($userId): void {
                $q->where('assigned_user_id', $userId)
                    ->orWhere('created_by_user_id', $userId)
                    ->orWhere('responding_user_id', $userId);
            });
        }

        if (! empty($filters['lead_id'])) {
            $query->whereKey((int) $filters['lead_id']);
        }

        return $query->with([
            'branch:id,name_ar,name_en,code',
            'assignedUser:id,name,username',
            'respondingUser:id,name,username',
            'status:id,name_ar,color,pipeline_stage_id',
            'status.stage:id,name_ar,color,position',
            'donationTypeRel:id,name_ar,name_en',
            'donationPurposeRel:id,name_ar,name_en',
            'primaryPhone:id,lead_id,phone,is_primary',
        ])
            ->orderBy('next_follow_up_at', 'asc')
            ->get();
    }

    /**
     * Get accessible lead contact dates as scheduled calendar items.
     *
     * @return Collection<int, Lead>
     */
    public function getLeadContactDatesForUser(
        User $user,
        ?string $start = null,
        ?string $end = null,
        array $filters = []
    ): Collection {
        $query = Lead::query()
            ->accessibleTo($user)
            ->whereNotNull('contact_date');

        if ($start && $end) {
            $query->whereBetween('contact_date', [$start, $end]);
        }

        // Avoid duplicating if contact_date is identical to next_follow_up_at
        $query->where(function ($q): void {
            $q->whereNull('next_follow_up_at')
                ->orWhereColumn('contact_date', '!=', 'next_follow_up_at');
        });

        if (! empty($filters['branch_id']) && $filters['branch_id'] !== 'all') {
            $query->forBranch((int) $filters['branch_id']);
        }

        if (! empty($filters['user_id'])) {
            $userId = (int) $filters['user_id'];
            $query->where(function ($q) use ($userId): void {
                $q->where('assigned_user_id', $userId)
                    ->orWhere('created_by_user_id', $userId)
                    ->orWhere('responding_user_id', $userId);
            });
        }

        if (! empty($filters['lead_id'])) {
            $query->whereKey((int) $filters['lead_id']);
        }

        return $query->with([
            'branch:id,name_ar,name_en,code',
            'assignedUser:id,name,username',
            'respondingUser:id,name,username',
            'status:id,name_ar,color,pipeline_stage_id',
            'status.stage:id,name_ar,color,position',
            'donationTypeRel:id,name_ar,name_en',
            'donationPurposeRel:id,name_ar,name_en',
            'primaryPhone:id,lead_id,phone,is_primary',
        ])
            ->orderBy('contact_date', 'asc')
            ->get();
    }

    /**
     * Get upcoming reminders for user.
     *
     * @return Collection<int, CalendarEvent>
     */
    public function getUpcomingReminders(User $user, int $minutes = 30): Collection
    {
        return CalendarEvent::query()
            ->with([
                'user:id,name,username',
                'branch:id,name_ar,name_en,code',
                'lead:id,name,first_name,last_name,company_name,phone,branch_id',
                'lead.branch:id,name_ar,name_en,code',
                'lead.primaryPhone:id,lead_id,phone,is_primary',
            ])
            ->accessibleTo($user)
            ->upcomingReminders($minutes)
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Get upcoming lead followups for reminders.
     *
     * @return Collection<int, Lead>
     */
    public function getUpcomingLeadFollowupReminders(User $user, int $minutes = 30): Collection
    {
        $now = now();
        $threshold = (clone $now)->addMinutes($minutes);

        return Lead::query()
            ->accessibleTo($user)
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '>=', $now)
            ->where('next_follow_up_at', '<=', $threshold)
            ->with([
                'branch:id,name_ar,name_en,code',
                'assignedUser:id,name,username',
                'primaryPhone:id,lead_id,phone,is_primary',
                'donationPurposeRel:id,name_ar,name_en',
            ])
            ->orderBy('next_follow_up_at', 'asc')
            ->get();
    }

    /**
     * Find event by ID if accessible by user.
     */
    public function findForUser(User $user, int $id): ?CalendarEvent
    {
        return CalendarEvent::query()
            ->with([
                'user:id,name,username',
                'branch:id,name_ar,name_en,code',
                'lead:id,name,first_name,last_name,company_name,phone,branch_id,donation_type,donation_type_id,donation_cycle,donation_value,donation_purpose,donation_purpose_id,response_details',
                'lead.branch:id,name_ar,name_en,code',
                'lead.status:id,name_ar,color,pipeline_stage_id',
                'lead.status.stage:id,name_ar,color',
                'lead.donationTypeRel:id,name_ar,name_en',
                'lead.donationPurposeRel:id,name_ar,name_en',
                'lead.primaryPhone:id,lead_id,phone,is_primary',
            ])
            ->accessibleTo($user)
            ->find($id);
    }

    /**
     * Create a new calendar event.
     */
    public function create(array $data): CalendarEvent
    {
        return CalendarEvent::query()->create([
            'branch_id' => $data['branch_id'] ?? null,
            'user_id' => $data['user_id'],
            'lead_id' => $data['lead_id'] ?? null,
            'title' => trim((string) $data['title']),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'type' => $data['type'] ?? 'meeting',
            'status' => $data['status'] ?? 'scheduled',
            'reminder_minutes_before' => $data['reminder_minutes_before'] ?? 15,
        ]);
    }

    /**
     * Update an existing calendar event.
     */
    public function update(CalendarEvent $event, array $data): CalendarEvent
    {
        $updateData = [];

        if (array_key_exists('branch_id', $data)) {
            $updateData['branch_id'] = $data['branch_id'];
        }
        if (array_key_exists('title', $data)) {
            $updateData['title'] = trim((string) $data['title']);
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'] !== null ? trim((string) $data['description']) : null;
        }
        if (array_key_exists('start_time', $data)) {
            $updateData['start_time'] = $data['start_time'];
        }
        if (array_key_exists('end_time', $data)) {
            $updateData['end_time'] = $data['end_time'];
        }
        if (array_key_exists('type', $data)) {
            $updateData['type'] = $data['type'];
        }
        if (array_key_exists('status', $data)) {
            $updateData['status'] = $data['status'];
        }
        if (array_key_exists('lead_id', $data)) {
            $updateData['lead_id'] = $data['lead_id'];
        }
        if (array_key_exists('user_id', $data)) {
            $updateData['user_id'] = $data['user_id'];
        }
        if (array_key_exists('reminder_minutes_before', $data)) {
            $updateData['reminder_minutes_before'] = $data['reminder_minutes_before'];
        }

        $event->update($updateData);

        return $event->fresh([
            'user:id,name,username',
            'branch:id,name_ar,name_en,code',
            'lead:id,name,first_name,last_name,company_name,phone,branch_id',
            'lead.branch:id,name_ar,name_en,code',
            'lead.primaryPhone:id,lead_id,phone,is_primary',
        ]);
    }

    /**
     * Delete a calendar event.
     */
    public function delete(CalendarEvent $event): bool
    {
        return (bool) $event->delete();
    }
}
