<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
            ->with(['user:id,name', 'lead:id,name,company_name,phone'])
            ->accessibleTo($user);

        if ($start && $end) {
            $query->between($start, $end);
        }

        if (! empty($filters['type'])) {
            $query->ofType((string) $filters['type']);
        }

        if (! empty($filters['status'])) {
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
     * Get upcoming reminders for user.
     *
     * @return Collection<int, CalendarEvent>
     */
    public function getUpcomingReminders(User $user, int $minutes = 30): Collection
    {
        return CalendarEvent::query()
            ->with(['user:id,name', 'lead:id,name,company_name,phone'])
            ->accessibleTo($user)
            ->upcomingReminders($minutes)
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Find event by ID if accessible by user.
     */
    public function findForUser(User $user, int $id): ?CalendarEvent
    {
        return CalendarEvent::query()
            ->with(['user:id,name', 'lead:id,name,company_name,phone'])
            ->accessibleTo($user)
            ->find($id);
    }

    /**
     * Create a new calendar event.
     */
    public function create(array $data): CalendarEvent
    {
        return CalendarEvent::query()->create([
            'user_id' => $data['user_id'],
            'lead_id' => $data['lead_id'] ?? null,
            'title' => trim((string) $data['title']),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'type' => $data['type'] ?? 'meeting',
            'status' => $data['status'] ?? 'scheduled',
        ]);
    }

    /**
     * Update an existing calendar event.
     */
    public function update(CalendarEvent $event, array $data): CalendarEvent
    {
        $updateData = [];

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

        $event->update($updateData);

        return $event->fresh(['user:id,name', 'lead:id,name,company_name,phone']);
    }

    /**
     * Delete a calendar event.
     */
    public function delete(CalendarEvent $event): bool
    {
        return (bool) $event->delete();
    }
}
