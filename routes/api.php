<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\HrAttendanceController;
use App\Http\Controllers\Api\ManagerAttendanceController;
use App\Http\Controllers\Api\ManagerController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\V1\Calendar\LeaveCalendarController;
use App\Http\Controllers\Api\V1\HR\HRLeaveQueueController;
use App\Http\Controllers\Api\V1\LeaveBalance\LeaveBalanceController;
use App\Http\Controllers\Api\V1\LeaveDecisionHistory\LeaveDecisionHistoryController;
use App\Http\Controllers\Api\V1\LeaveRequest\LeaveApprovalController;
use App\Http\Controllers\Api\V1\LeaveRequest\LeaveRequestController;
use App\Http\Controllers\Api\V1\LeaveType\LeaveTypeController;
use App\Http\Controllers\Api\V1\Manager\ManagerLeaveQueueController;
use App\Http\Controllers\Auth\AuthController as V1AuthController;
use App\Http\Controllers\CompanyLocations\CompanyLocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // Route::post('/login', [AuthController::class,'login'])
    //     ->middleware('throttle:5,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/forget-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyForgotPasswordOtp'])->middleware('throttle:5,1');
    Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('/forgot-password/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:5,1');
});

// Company Location

// Create company location
Route::post('company/location', [CompanyLocationController::class, 'store']);

// Update company location
Route::put('company/location/{id}', [CompanyLocationController::class, 'update']);

// Deactivate company location
Route::patch('company/location/{id}/deactivate', [CompanyLocationController::class, 'deactivate']);

// Activate company location
Route::patch('company/location/{id}/activate', [CompanyLocationController::class, 'activate']);
// Get active company location
Route::get('company/location/active', [CompanyLocationController::class, 'activeLocation']);

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
// ─── Team Auth & Account Routes ──────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [ApiAuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/forget-password', [ApiAuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/forgot-password/verify-otp', [ApiAuthController::class, 'verifyForgotPasswordOtp'])->middleware('throttle:5,1');
    Route::post('/forgot-password/reset', [ApiAuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('/forgot-password/resend-otp', [ApiAuthController::class, 'resendOtp'])->middleware('throttle:5,1');
    Route::post('/login', [ApiAuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [ApiAuthController::class, 'logout']);
    });

    Route::prefix('google')->group(function () {
        Route::get('/redirect', [GoogleAuthController::class, 'redirect']);
        Route::get('/callback', [GoogleAuthController::class, 'callback']);
    });
});

// ─── Company Locations ───────────────────────────────────────────────────────
Route::post('company/location', [CompanyLocationController::class, 'store']);
Route::put('company/location/{id}', [CompanyLocationController::class, 'update']);
Route::patch('company/location/{id}/deactivate', [CompanyLocationController::class, 'deactivate']);
Route::patch('company/location/{id}/activate', [CompanyLocationController::class, 'activate']);
Route::get('company/location/active', [CompanyLocationController::class, 'activeLocation']);

// ─── Protected Management Routes (Employees, Departments, Managers) ──────────
Route::middleware(['auth:api', 'check.active'])->group(function () {
    Route::middleware(['role:Owner|HR'])->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index']);
    });
    Route::post('/employees', [EmployeeController::class, 'store'])->middleware('permission:create employee');
    Route::patch('/employees/profile', [EmployeeController::class, 'updateProfile']);
    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    Route::patch('/employees/{id}/hr-fields', [EmployeeController::class, 'updateHrFields'])->middleware('permission:edit hr fields');
    Route::patch('/employees/{id}/change-account-status', [EmployeeController::class, 'changeAccountStatus'])->middleware('permission:employee.change-account-status');
    Route::get('/employees', [EmployeeController::class, 'index'])->middleware('permission:employee.view-all');

    Route::get('/departments', [DepartmentController::class, 'index'])->middleware('permission:department.view');
    Route::post('/departments', [DepartmentController::class, 'store'])->middleware('permission:department.create');
    Route::patch('/departments/{id}', [DepartmentController::class, 'update'])->middleware('permission:department.edit');
    Route::patch('/departments/{id}/change-status', [DepartmentController::class, 'changeStatus'])->middleware('permission:department.change-status');

    Route::get('/managers/employees', [ManagerController::class, 'employees'])->middleware('permission:manager.view-employees');

    Route::prefix('attendance')->group(function () {
        Route::get('/today', [AttendanceController::class, 'today']);
        Route::post('/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/history', [AttendanceController::class, 'history']);
    });
    Route::middleware(['role:Manager|Owner|HR'])->prefix('manager/attendance')->group(function () {
        Route::get('/today', [ManagerAttendanceController::class, 'today']);
        Route::get('/{employeeId}', [ManagerAttendanceController::class, 'show']);
    });
    Route::middleware(['role:HR|Owner'])->prefix('hr/attendance')->group(function () {
        Route::get('/daily', [HrAttendanceController::class, 'daily']);
        Route::get('/exceptions', [HrAttendanceController::class, 'exceptions']);
        Route::get('/monthly-summary', [HrAttendanceController::class, 'monthlySummary']);
        Route::get('/export', [HrAttendanceController::class, 'export']);
    });
});

// ─── V1 Leave Management API ─────────────────────────────────────────────────
Route::prefix('v1')->group(function (): void {
    // Authentication
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [V1AuthController::class, 'login'])->name('auth.login');

        Route::middleware('jwt.auth')->group(function (): void {
            Route::post('logout', [V1AuthController::class, 'logout'])->name('auth.logout');
            Route::post('refresh', [V1AuthController::class, 'refresh'])->name('auth.refresh');
            Route::get('me', [V1AuthController::class, 'me'])->name('auth.me');
        });
    });

    // Protected Leave Management Routes
    Route::middleware('jwt.auth')->group(function (): void {
        // Leave Types (Read: All authenticated, Write: HR/Owner via Policy)
        Route::apiResource('leave-types', LeaveTypeController::class);

        // Leave Balances
        Route::get('leave-balances', [LeaveBalanceController::class, 'index'])->name('leave-balances.index');

        // Leave Requests (Employee own & actions)
        Route::get('leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
        Route::post('leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
        Route::get('leave-requests/{leave_request}', [LeaveRequestController::class, 'show'])->name('leave-requests.show');
        Route::post('leave-requests/{leave_request}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');

        // Approval Workflow (Protected by Gate/Policies inside controller)
        Route::prefix('leave-requests/{leave_request}')->group(function (): void {
            Route::post('approve-manager', [LeaveApprovalController::class, 'approveByManager'])->name('leave-requests.approve-manager');
            Route::post('approve-hr', [LeaveApprovalController::class, 'approveByHR'])->name('leave-requests.approve-hr');
            Route::post('reject', [LeaveApprovalController::class, 'reject'])->name('leave-requests.reject');
            Route::get('history', [LeaveDecisionHistoryController::class, 'index'])->name('leave-requests.history');
        });

        // Manager Queue (Direct reports only)
        Route::middleware('role:Manager,Owner,HR')->prefix('manager')->group(function (): void {
            Route::get('leave-requests', [ManagerLeaveQueueController::class, 'index'])->name('manager.leave-requests.index');
        });

        // HR Queue (Full visibility with multi-parameter filtering)
        Route::middleware('role:HR,Owner')->prefix('hr')->group(function (): void {
            Route::get('leave-requests', [HRLeaveQueueController::class, 'index'])->name('hr.leave-requests.index');
        });

        // Leave Calendar (Approved leaves view)
        Route::get('calendar/leaves', [LeaveCalendarController::class, 'index'])->name('calendar.leaves');
    });
});
