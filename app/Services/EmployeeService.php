<?php

namespace App\Services;

use App\Mail\EmployeeInvitationMail;
use App\Models\CompanyLocation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function createEmployee(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
            ]);

            $user->assignRole($data['role']);
            if (! empty($data['permissions'])) {
                $user->givePermissionTo($data['permissions']);
            }

            $managerId = $data['manager_id'] ?? null;
            if (empty($managerId) && ! empty($data['department_id']) && $data['role'] === 'Employee') {
                $department = Department::find($data['department_id']);
                $managerId = $department?->manager_id;
            }

            $locationId = $data['company_location_id'] ?? CompanyLocation::where('is_active', true)->value('id');

            $user->employee()->create([
                'employee_id' => $this->generateUniqueEmployeeId(),
                'job_title' => $data['job_title'],
                'employment_type' => $data['employment_type'],
                'start_date' => $data['start_date'],
                'department_id' => $data['department_id'] ?? null,
                'manager_id' => $managerId,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => 'inactive',
                'company_location_id' => $locationId,
            ]);

            Mail::to($user->email)->send(new EmployeeInvitationMail($user));

            return $user->load('employee.department', 'employee.manager');
        });
    }

    private function generateUniqueEmployeeId(): string
    {
        $nextId = (Employee::withTrashed()->max('id') ?? 0) + 1;

        return 'EMP-'.date('Y').'-'.str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    public function getEmployeeById(int $id): Employee
    {
        return Employee::with(['user', 'department', 'manager.user'])->findOrFail($id);
    }

    public function updateHrFields(Employee $employee, array $data): Employee
    {
        $employee->update(array_filter($data, fn ($value) => $value !== null));

        return $employee->load(['user', 'department', 'manager.user']);
    }

    public function updateProfile(User $user, array $data): Employee
    {
        return DB::transaction(function () use ($user, $data) {
            $userData = array_intersect_key($data, array_flip(['name', 'avatar']));
            if (! empty($userData)) {
                $user->update($userData);
                $user->refresh();
            }
            $employeeData = array_intersect_key($data, array_flip(['phone', 'address']));
            $employee = $user->employee;
            if (! $employee) {
                throw new ModelNotFoundException('Employee profile not found for this user.');
            }
            if (! empty($employeeData)) {
                $employee->update($employeeData);
            }

            return $employee->load(['user', 'department', 'manager.user']);
        });
    }

    public function changeAccountStatus(int $employeeId): User
    {
        $employee = Employee::with('user')->findOrFail($employeeId);
        $user = $employee->user;
        if ($user->id === auth('api')->id()) {
            throw ValidationException::withMessages([
                'employee' => 'You cannot change the status of your own account.',
            ]);
        }
        $newStatus = $employee->status === 'active' ? 'inactive' : 'active';
        $employee->update(['status' => $newStatus]);

        return $user;
    }

    public function getAllEmployees(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return User::whereHas('employee')
            ->with(['employee.department', 'employee.manager.user', 'roles'])
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($eq) use ($search) {
                            $eq->where('employee_id', 'like', "%{$search}%")
                                ->orWhere('job_title', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when(! empty($filters['status']), function ($query) use ($filters) {
                $query->whereHas('employee', fn ($q) => $q->where('status', $filters['status']));
            })
            ->when(! empty($filters['department_id']), function ($query) use ($filters) {
                $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
            })
            ->when(! empty($filters['manager_id']), function ($query) use ($filters) {
                $query->whereHas('employee', fn ($q) => $q->where('manager_id', $filters['manager_id']));
            })
            ->when(! empty($filters['employment_type']), function ($query) use ($filters) {
                $query->whereHas('employee', fn ($q) => $q->where('employment_type', $filters['employment_type']));
            })
            ->when(! empty($filters['role']), function ($query) use ($filters) {
                $query->whereHas('roles', function ($q) use ($filters) {
                    $q->where('name', 'like', "%{$filters['role']}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }
}
