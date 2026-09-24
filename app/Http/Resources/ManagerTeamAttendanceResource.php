<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagerTeamAttendanceResource extends JsonResource
{
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
        $avatarFile = $this->relationLoaded('files')
            ? $this->files->sortByDesc('created_at')->first()
            : $this->files()->latest()->first();

        return [
            'user_id' => $this->id,
            'name' => $this->name,
            'job_title' => $this->job_title,
            'avatar_url' => $avatarFile ? route('files.download', $avatarFile->id) : null,
            'attendance_id' => $attendance?->id,
            'check_in' => $attendance?->check_in?->format('h:i A') ?? '—',
            'check_out' => $attendance?->check_out?->format('h:i A') ?? '—',
            'status' => __('attendance.status.'.$shiftStatus),
            'worked_hours_formatted' => $hoursFormatted,
            'worked_seconds' => $attendance?->worked_seconds ?? 0,
            'is_exception' => (bool) ($attendance?->is_exception),
        ];
    }
}
