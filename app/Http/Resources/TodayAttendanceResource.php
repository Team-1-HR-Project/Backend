<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodayAttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasCheckedIn = $this['has_checked_in'];
        $hasCheckedOut = $this['has_checked_out'];
        $isInside = $this['is_inside_radius'];

        return [
            'status' => ($hasCheckedIn && ! $hasCheckedOut) ? 'On shift' : 'Off shift',
            'check_in_time' => $this['check_in_time'],
            'check_out_time' => $this['check_out_time'],
            'worked_seconds' => $this['worked_seconds'],
            'distance_meters' => $this['distance_meters'],
            'is_inside_radius' => $isInside,
            'can_check_in' => ! $hasCheckedIn && $isInside,
            'can_check_out' => $hasCheckedIn && ! $hasCheckedOut && $isInside,
        ];
    }
}
