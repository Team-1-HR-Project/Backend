<?php

namespace App\Services;

use App\Mail\EmployeeInvitationMail;
use App\Models\CompanyLocation;
use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function __construct(protected FileService $fileService) {}

    public function createEmployee(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $role = $data['role'];
            $departmentId = $role === 'HR' ? null : ($data['department_id'] ?? null);
            $managerId = null;
            if ($departmentId) {
                $department = Department::find($departmentId);
                if ($role === 'Employee') {
                    $managerId = $department?->manager_id;
                }
            }

            $locationId = CompanyLocation::latest('id')->value('id');

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'employee_id' => $this->generateUniqueEmployeeId(),
                'job_title' => $data['job_title'],
                'employment_type' => $data['employment_type'],
                'start_date' => $data['start_date'],
                'status' => 'inactive',
                'department_id' => $departmentId,
                'company_location_id' => $locationId,
                'manager_id' => $managerId,
                'address' => $data['address'] ?? null,
            ]);

            $user->assignRole($data['role']);
            if (! empty($data['permissions'])) {
                $user->givePermissionTo($data['permissions']);
            }
            if ($role === 'Manager' && $departmentId) {
                Department::where('id', $departmentId)->update([
                    'manager_id' => $user->id,
                ]);

                User::where('department_id', $departmentId)
                    ->where('id', '!=', $user->id)
                    ->where('role', 'Employee')
                    ->update([
                        'manager_id' => $user->id,
                    ]);
            }
            $user = $user->fresh();

            Mail::to($user->email)->send(new EmployeeInvitationMail($user));

            return $user->load(['department', 'companyLocation', 'manager']);
        });
    }

    private function generateUniqueEmployeeId(): string
    {
        do {
            $code = 'EMP-'.date('Y').'-'.mt_rand(10000, 99999);
        } while (User::withTrashed()->where('employee_id', $code)->exists());

        return $code;
    }

    public function getEmployeeById(int $id): User
    {
        $user = User::with(['department', 'companyLocation', 'manager', 'files', 'roles'])->findOrFail($id);

        if (empty($user->department_id) && ! empty($user->manager_id)) {
            $managedDepartmentId = Department::where('manager_id', $user->manager_id)->value('id');

            if ($managedDepartmentId) {
                $user->update(['department_id' => $managedDepartmentId]);
                $user->load('department');
            }
        }

        return $user;
    }

    public function updateHrFields(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $filteredData = array_filter($data, fn ($value) => $value !== null);

            if (array_key_exists('department_id', $filteredData)) {
                $newDepartmentId = $filteredData['department_id'];
                $roleValue = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

                if ($newDepartmentId) {
                    $department = Department::find($newDepartmentId);

                    if ($roleValue === 'Employee') {
                        $filteredData['manager_id'] = $department?->manager_id;
                    }

                    if ($roleValue === 'Manager') {
                        $department?->update(['manager_id' => $user->id]);

                        User::where('department_id', $newDepartmentId)
                            ->where('id', '!=', $user->id)
                            ->where('role', 'Employee')
                            ->update(['manager_id' => $user->id]);
                        
                        $filteredData['manager_id'] = null; 
                    }
                } else {
                    $filteredData['manager_id'] = null;
                }
            }

            if (! empty($filteredData)) {
                $user->update($filteredData);
            }

            return $user->fresh()->load(['department', 'companyLocation', 'manager', 'files']);
        });
    }

    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
                $this->fileService->updateAvatar($data['avatar'], $user);
                unset($data['avatar']);
            }

            $updateData = array_intersect_key($data, array_flip(['name', 'phone', 'locale', 'address']));

            if (! empty($updateData)) {
                $user->update(array_filter($updateData, fn ($val) => $val !== null));
            }

            return $user->fresh()->load(['department', 'companyLocation', 'manager', 'files']);
        });
    }

    public function changeAccountStatus(int $userId): User
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth('api')->id()) {
            throw ValidationException::withMessages([
                'employee' => [__('employees.cannot_change_own_status')],
            ]);
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        return $user->fresh()->load(['department', 'companyLocation', 'manager']);
    }

    public function getAllEmployees(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->with(['department', 'companyLocation', 'manager', 'roles', 'files'])
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('job_title', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['department_id']), fn ($q) => $q->where('department_id', $filters['department_id']))
            ->when(! empty($filters['manager_id']), fn ($q) => $q->where('manager_id', $filters['manager_id']))
            ->when(! empty($filters['employment_type']), fn ($q) => $q->where('employment_type', $filters['employment_type']))
            ->when(! empty($filters['role']), function ($query) use ($filters) {
                $role = $filters['role'];
                $query->where(function ($q) use ($role) {
                    $q->where('role', $role)
                        ->orWhereHas('roles', fn ($r) => $r->where('name', $role));
                });
            })
            ->latest()
            ->paginate($perPage);
    }
}
