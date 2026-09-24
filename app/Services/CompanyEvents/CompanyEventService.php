<?php

namespace App\Services\CompanyEvents;

use App\Models\CompanyEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class CompanyEventService
{
    public function create(User $user, array $data): CompanyEvent
    {
        $data['created_by'] = $user->id;

        return CompanyEvent::create($data);
    }

    public function getAll(): Collection
    {
        return CompanyEvent::query()
            ->with('creator:id,name')
            ->orderBy('start_date')
            ->get();
    }

    public function update(CompanyEvent $companyEvent, array $data): CompanyEvent
    {
        $companyEvent->update($data);

        return $companyEvent->refresh();
    }

    public function delete(CompanyEvent $companyEvent): void
    {
        $companyEvent->delete();
    }

    public function getForCalendar(Carbon $from, Carbon $to): Collection
    {
        return CompanyEvent::query()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->orderBy('start_date')
            ->get();
    }
}
