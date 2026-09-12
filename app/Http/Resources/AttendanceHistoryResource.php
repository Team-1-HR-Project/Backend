<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hours = floor($this->worked_seconds / 3600);
        $minutes = floor(($this->worked_seconds % 3600) / 60);

        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'day_name' => $this->date->format('l'),
            'check_in' => $this->check_in?->format('h:i A'),
            'check_out' => $this->check_out?->format('h:i A'),
            'status' => $this->status,
            'worked_seconds' => $this->worked_seconds,
            'worked_hours_formatted' => sprintf('%dh %dm', $hours, $minutes),
            'is_exception' => $this->is_exception,
        ];
    }
}
