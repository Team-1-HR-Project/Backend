<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HrDailyAttendanceResource extends JsonResource
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
            'employee_code' => $this->employee_id,
            'job_title' => $this->job_title,
            'department' => $this->department?->name ?? 'N/A',
            'manager_name' => $this->manager?->user?->name ?? 'N/A',
            'attendance_id' => $attendance?->id,
            'check_in' => $attendance?->check_in?->format('h:i A') ?? '—',
            'check_out' => $attendance?->check_out?->format('h:i A') ?? '—',
            'status' => $shiftStatus,
            'worked_hours' => $hoursFormatted,
            'is_exception' => (bool) ($attendance?->is_exception),
        ];
    }
}
