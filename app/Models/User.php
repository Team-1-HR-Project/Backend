<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable , SoftDeletes;

    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'email_verified_at',
        'role',
        'provider',
        'provider_id',
        'employee_id',
        'locale',
        'fcm_token',
        'job_title',
        'employment_type',
        'start_date',
        'status',
        'department_id',
        'company_location_id',
        'manager_id',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'start_date' => 'date',
        ];
    }

    // ─── JWTSubject ──────────────────────────────────────────────────────────

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        $roleValue = $this->role instanceof \BackedEnum ? $this->role->value : (string) $this->role;

        return ['role' => $roleValue];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /** The manager this user reports to. */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /** All employees directly managed by this user. */
    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /** All leave requests submitted by this user. */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /** Leave balance records for this user. */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function taskActivities(): HasMany
    {
        return $this->hasMany(TaskActivity::class, 'employee_id');
    }

    // ─── Role Helpers ─────────────────────────────────────────────────────────

    public function isOwner(): bool
    {
        return (method_exists($this, 'hasRole') && $this->hasRole('Owner'))
            || $this->role === UserRole::Owner
            || $this->role === 'Owner';
    }

    public function isHR(): bool
    {
        return (method_exists($this, 'hasRole') && $this->hasRole('HR'))
            || $this->role === UserRole::HR
            || $this->role === 'HR';
    }

    public function isManager(): bool
    {
        return (method_exists($this, 'hasRole') && $this->hasRole('Manager'))
            || $this->role === UserRole::Manager
            || $this->role === 'Manager';
    }

    public function isEmployee(): bool
    {
        return (method_exists($this, 'hasRole') && $this->hasRole('Employee'))
            || $this->role === UserRole::Employee
            || $this->role === 'Employee';
    }

    public function isHrOrAbove(): bool
    {
        if ($this->role instanceof UserRole) {
            return $this->role->isHrOrAbove();
        }

        return in_array($this->role, ['Owner', 'HR', UserRole::Owner, UserRole::HR], true);
    }

    public function isManagerOrAbove(): bool
    {
        if ($this->role instanceof UserRole) {
            return $this->role->isManagerOrAbove();
        }

        return in_array($this->role, ['Owner', 'HR', 'Manager', UserRole::Owner, UserRole::HR, UserRole::Manager], true);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id')->latest();
    }

    /**
     * Tasks created by this user.
     */
    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    /**
     * Task assignments created by this user.
     */
    public function taskAssignments()
    {
        return $this->hasMany(TaskAssignment::class, 'user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function managedDepartments()
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    public function companyLocation(): BelongsTo
    {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id');
    }

    public function todayAttendance()
    {
        return $this->hasOne(Attendance::class, 'user_id')->where('date', now()->toDateString());
    }

    public function scopeExcludeOwnerAndSelf($query, ?int $currentUserId = null)
    {
        $currentUserId = $currentUserId ?? auth('api')->id();

        return $query->whereDoesntHave('roles', function ($q) {
            $q->where('name', 'Owner');
        })
            ->when($currentUserId, function ($q) use ($currentUserId) {
                $q->where('id', '!=', $currentUserId);
            });
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class, 'user_id');
    }

    public function evaluationsGiven()
    {
        return $this->hasMany(Evaluation::class, 'evaluator_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class, 'user_id');
    }

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(File::class, 'user_id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function avatarFile(): MorphOne
    {
        return $this->morphOne(File::class, 'fileable')->latestOfMany();
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class, 'created_by');
    }

    public function policyVersions(): HasMany
    {
        return $this->hasMany(PolicyVersion::class, 'created_by');
    }
}
