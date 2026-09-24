<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagerEmployeeAttendanceDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attendance = $this->resource['attendance'];
        $user = $this->resource['employee'];

        $hoursFormatted = '—';
        if ($attendance?->worked_seconds) {
            $hoursFormatted = number_format($attendance->worked_seconds / 3600, 1).'h';
        }

        $rawStatus = $attendance?->status ?? 'Absent';

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'job_title' => $user->job_title,
                'email' => $user->email,
                'company_name' => $user->companyLocation?->name,
            ],
            'attendance' => [
                'id' => $attendance?->id,
                'date' => $this->resource['date'],
                'check_in' => $attendance?->check_in?->format('h:i A'),
                'check_in_location' => $attendance?->check_in_lat ? [
                    'latitude' => (float) $attendance->check_in_lat,
                    'longitude' => (float) $attendance->check_in_lng,
                ] : null,
                'check_out' => $attendance?->check_out?->format('h:i A'),
                'status' => __('attendance.status.'.$rawStatus),
                'worked_seconds' => $attendance?->worked_seconds ?? 0,
                'worked_hours_formatted' => $hoursFormatted,
                'is_exception' => (bool) ($attendance?->is_exception),
                'exception_reason' => when($attendance?->is_exception, fn () => $attendance?->exception_reason),
            ],
        ];
    }
}
