<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DepartmentService
{
    public function getAllDepartments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Department::with(['manager'])
            ->withCount('employees')
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->latest()
            ->paginate($perPage);
    }

    public function createDepartment(array $data): Department
    {
        return DB::transaction(function () use ($data) {
            $managerId = $data['manager_id'] ?? null;

            $department = Department::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'manager_id' => $managerId,
                'status' => 'active',
            ]);

            if ($managerId) {
                User::where('department_id', $department->id)
                    ->where('id', '!=', $managerId)
                    ->where('role', 'Employee')
                    ->update([
                        'manager_id' => $managerId,
                    ]);
            }

            return $department->load('manager');
        });
    }

    public function updateDepartment(int $id, array $data): Department
    {
        return DB::transaction(function () use ($id, $data) {
            $department = Department::findOrFail($id);

            $oldManagerId = $department->manager_id;

            $department->update($data);

            if (array_key_exists('manager_id', $data) && $data['manager_id'] != $oldManagerId) {
                $newManagerId = $data['manager_id'];

                User::where('department_id', $department->id)
                    ->where('role', 'Employee')
                    ->where('id', '!=', $newManagerId)
                    ->update([
                        'manager_id' => $newManagerId,
                    ]);
            }

            return $department->load('manager');
        });
    }

    public function changeDepartmentStatus(int $id): Department
    {
        $department = Department::findOrFail($id);
        $newStatus = $department->status === 'active' ? 'inactive' : 'active';
        $department->update(['status' => $newStatus]);

        return $department->load('manager');
    }

    public function getManagersForDropdown(): Collection
    {
        return User::query()
            ->where(function ($query) {
                $query->whereIn('role', ['Manager'])
                    ->orWhereHas('roles', function ($q) {
                        $q->whereIn('name', ['Manager']);
                    });
            })
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }
}
