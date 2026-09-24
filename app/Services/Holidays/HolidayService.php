<?php

namespace App\Services\Holidays;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class HolidayService
{
    /**
     * Create a new holiday.
     */
    public function create(array $data): Holiday
    {
        return Holiday::create($data);
    }

    /**
     * Get all holidays.
     */
    public function getAll(): Collection
    {
        return Holiday::query()
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Update an existing holiday.
     */
    public function update(Holiday $holiday, array $data): Holiday
    {
        $holiday->update($data);

        return $holiday->refresh();
    }

    /**
     * Delete an existing holiday.
     */
    public function delete(Holiday $holiday): void
    {
        $holiday->delete();
    }

    /**
     * Get active holidays overlapping the given calendar period.
     */
    public function getForCalendar(Carbon $from, Carbon $to): Collection
    {
        return Holiday::query()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->orderBy('start_date')
            ->get();
    }
}
