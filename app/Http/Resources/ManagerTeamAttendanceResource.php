<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagerTeamAttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attendance = $this->todayAttendance;

        $hasCheckedIn = (bool) ($attendance?->check_in);
        $hasCheckedOut = (bool) ($attendance?->check_out);

        $shiftStatus = 'Absent';
        if ($hasCheckedIn && ! $hasCheckedOut) {
            $shiftStatus = 'On shift';
        } elseif ($attendance?->status) {
            $shiftStatus = $attendance->status;
        }

        $hoursFormatted = '—';
        if ($attendance?->worked_seconds) {
            $hoursFormatted = number_format($attendance->worked_seconds / 3600, 1).'h';
        }

        return [
            'employee_id' => $this->id,
            'name' => $this->user?->name,
            'job_title' => $this->job_title,
            'avatar' => $this->user?->avatar,
            'attendance_id' => $attendance?->id,
            'check_in' => $attendance?->check_in?->format('H:i') ?? '—',
            'check_out' => $attendance?->check_out?->format('H:i') ?? '—',
            'status' => $shiftStatus,
            'worked_hours_formatted' => $hoursFormatted,
            'worked_seconds' => $attendance?->worked_seconds ?? 0,
            'is_exception' => (bool) ($attendance?->is_exception),
        ];

    }
}
