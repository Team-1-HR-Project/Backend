<?php

namespace App\Services\Calendar;

use App\Enums\LeaveStatus;
use App\Models\CompanyEvent;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class CalendarService
{
    /**
     * Get unified calendar events for the authenticated user.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getEvents(User $user, Carbon $from, Carbon $to): Collection
    {
        $events = collect();

        // Add approved leave events.
        $events = $events->merge(
            $this->getApprovedLeaveEvents(
                user: $user,
                from: $from,
                to: $to
            )
        );

        // Add assigned task deadline events.
        $events = $events->merge(
            $this->getTaskDeadlineEvents(
                user: $user,
                from: $from,
                to: $to
            )
        );

        // Add active company holiday events.
        $events = $events->merge(
            $this->getHolidayEvents(
                from: $from,
                to: $to
            )
        );
        // Add active company events.
        $events = $events->merge(
            $this->getCompanyEventEvents(
                user: $user,
                from: $from,
                to: $to
            )
        );

        return $events
            ->sortBy([
                ['date', 'asc'],
                ['type', 'asc'],
                ['reference', 'asc'],
            ])
            ->values();
    }

    /**
     * Get approved leave events for the authenticated user.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function getApprovedLeaveEvents(User $user, Carbon $from, Carbon $to): Collection
    {
        $leaveRequests = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', LeaveStatus::Approved)
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->get([
                'id',
                'start_date',
                'end_date',
            ]);

        return $leaveRequests->flatMap(
            function (LeaveRequest $leaveRequest) use ($from, $to) {
                $start = Carbon::parse($leaveRequest->start_date)
                    ->max($from->copy()->startOfDay());

                $end = Carbon::parse($leaveRequest->end_date)
                    ->min($to->copy()->startOfDay());

                if ($start->gt($end)) {
                    return collect();
                }

                return collect(
                    CarbonPeriod::create($start, $end)
                )->map(
                    fn (Carbon $date) => [
                        'date' => $date->toDateString(),
                        'type' => 'leave',
                        'reference' => $leaveRequest->id,
                    ]
                );
            }
        );
    }

    /**
     * Get deadlines for tasks assigned to the authenticated user.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function getTaskDeadlineEvents(User $user, Carbon $from, Carbon $to): Collection
    {
        return Task::query()
            ->whereHas(
                'assignments',
                fn ($query) => $query->where(
                    'user_id',
                    $user->id
                )
            )
            ->whereBetween('deadline', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ])
            ->get([
                'id',
                'deadline',
            ])
            ->map(
                fn (Task $task) => [
                    'date' => $task->deadline->toDateString(),
                    'type' => 'task_deadline',
                    'reference' => $task->id,
                ]
            );
    }

    /**
     * Get active company holidays overlapping the requested period.
     *
     * Holidays are visible to all authenticated users.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function getHolidayEvents(Carbon $from, Carbon $to): Collection
    {
        $holidays = Holiday::query()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->get([
                'id',
                'start_date',
                'end_date',
            ]);

        return $holidays->flatMap(
            function (Holiday $holiday) use ($from, $to) {
                $start = Carbon::parse($holiday->start_date)
                    ->max($from->copy()->startOfDay());

                $end = Carbon::parse($holiday->end_date)
                    ->min($to->copy()->startOfDay());

                if ($start->gt($end)) {
                    return collect();
                }

                return collect(
                    CarbonPeriod::create($start, $end)
                )->map(
                    fn (Carbon $date) => [
                        'date' => $date->toDateString(),
                        'type' => 'holiday',
                        'reference' => $holiday->id,
                    ]
                );
            }
        );
    }

    private function getCompanyEventEvents(User $user, Carbon $from, Carbon $to): Collection
    {
        if (! $user->can('company_event.view')) {
            return collect();
        }

        $events = CompanyEvent::query()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->get(['id', 'start_date', 'end_date']);

        return $events->flatMap(
            function (CompanyEvent $event) use ($from, $to) {
                $start = Carbon::parse($event->start_date)
                    ->max($from->copy()->startOfDay());

                $end = Carbon::parse($event->end_date)
                    ->min($to->copy()->startOfDay());

                if ($start->gt($end)) {
                    return collect();
                }

                return collect(CarbonPeriod::create($start, $end))
                    ->map(fn (Carbon $date) => [
                        'date' => $date->toDateString(),
                        'type' => 'company_event',
                        'reference' => $event->id,
                    ]);
            }
        );
    }
}
