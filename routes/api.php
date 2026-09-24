<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\Api\AIAttentionSignalController;
use App\Http\Controllers\Api\AICareerCoachController;
use App\Http\Controllers\Api\AIEvaluationDraftController;
use App\Http\Controllers\Api\AIPerformanceInsightController;
use App\Http\Controllers\Api\AIPolicyAssistantController;
use App\Http\Controllers\Api\AISkillGapController;
use App\Http\Controllers\Api\AITeamInsightController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\CompanyEventController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeEvaluationController;
use App\Http\Controllers\Api\EmployeePerformanceController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\HrAttendanceController;
use App\Http\Controllers\Api\HrEvaluationSetupController;
use App\Http\Controllers\Api\HrGoalController;
use App\Http\Controllers\Api\HrPerformanceController;
use App\Http\Controllers\Api\LeaveBalanceController;
use App\Http\Controllers\Api\LeaveDecisionHistoryController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\LeaveTypeController;
use App\Http\Controllers\Api\ManagerAttendanceController;
use App\Http\Controllers\Api\ManagerController;
use App\Http\Controllers\Api\ManagerPerformanceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PolicyController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\CompanyLocations\CompanyLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Route::middleware('set.app.language')->prefix('auth')->group(function () {

Route::middleware('set.app.language')->prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/forget-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyForgotPasswordOtp'])->middleware('throttle:5,1');
    Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('/forgot-password/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:5,1');

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::prefix('google')->group(function () {
        Route::get('/redirect', [GoogleAuthController::class, 'redirect']);
        Route::get('/callback', [GoogleAuthController::class, 'callback']);
    });
});

// holiday
Route::prefix('holidays')->middleware(['auth:api', 'set.app.language'])->group(function () {

    Route::get('/', [HolidayController::class, 'index']);

    Route::middleware('role:HR|Owner')->group(function () {
        Route::post('/', [HolidayController::class, 'store']);

        Route::put('/{holiday}', [HolidayController::class, 'update']);

        Route::delete('/{holiday}', [HolidayController::class, 'destroy']);
    });
});

// company events
Route::prefix('company-events')->middleware(['auth:api', 'set.app.language'])->group(function () {

    // All authenticated users can view company events.
    Route::get('/', [CompanyEventController::class, 'index'])->middleware('permission:company_event.view');

    // Only users with manage permission can create, update, or delete events.
    Route::middleware('permission:company_event.manage')->group(function () {
        Route::post('/', [CompanyEventController::class, 'store']);

        Route::put('/{companyEvent}', [CompanyEventController::class, 'update']);

        Route::delete('/{companyEvent}', [CompanyEventController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| calender Routes
|--------------------------------------------------------------------------
*/
Route::prefix('calender')->middleware(['auth:api', 'set.app.language'])->group(function () {
    Route::get('/calendar', [CalendarController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| leave Management Routes
|--------------------------------------------------------------------------
*/

Route::prefix('leaves')->middleware('auth:api', 'set.app.language')->group(function () {

    // ............................................. Leave Types........................................................
    // new leave type
    Route::post('/leave-types', [LeaveTypeController::class, 'store']);
    // get all leave types
    Route::get('/leave-types', [LeaveTypeController::class, 'index']);
    // update leave type
    Route::put('/leave-types/{leaveType}', [LeaveTypeController::class, 'update']);
    // activate leave
    Route::patch('/leave-types/{leaveType}/activate', [LeaveTypeController::class, 'activate']);
    // deactivate
    Route::patch('/leave-types/{leaveType}/deactivate', [LeaveTypeController::class, 'deactivate']);
    // ............................................. Leave balances........................................................
    // Leave Balances
    Route::get('/leave-balances', [LeaveBalanceController::class, 'index']);
    // ..............................................Leave requests.......................................................
    // create leave
    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
    // manager Pending Queue
    Route::get('/leave-requests/manager/pending', [LeaveRequestController::class, 'managerPendingQueue'])->middleware('role:Manager');
    // hr pending Queue
    Route::get('/leave-requests/hr/pending', [LeaveRequestController::class, 'hrPendingQueue'])->middleware('role:HR|Owner');
    // Leave Decision History
    Route::get('/leave-requests/{leaveRequest}/decisions', [LeaveDecisionHistoryController::class, 'index']);
    // approve leave
    Route::patch('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
    // reject leave
    Route::patch('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject']);
    // leave attachments
    Route::post('/leave-requests/{leaveRequest}/attachments', [LeaveRequestController::class, 'storeAttachment']);
    // leave details
    Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show']);
    // leave history
    Route::get('/leave-requests', [LeaveRequestController::class, 'history']);
});

/*
|--------------------------------------------------------------------------
| Task Management Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api', 'check.active', 'set.app.language'])->group(function () {

    Route::prefix('policies')->group(function () {

        Route::post('/', [PolicyController::class, 'store'])->middleware('permission:policy.manage');

        Route::post('/{policy}/versions', [PolicyController::class, 'storeVersion'])->middleware('permission:policy.version_create');

        Route::post('/{policy}/versions/{version}/activate', [PolicyController::class, 'activateVersion'])->middleware('permission:policy.version_activate');

        Route::get('/{policy}/active', [PolicyController::class, 'active'])->middleware('permission:policy.active_view');

        Route::get('/{policy}/audits', [PolicyController::class, 'auditHistory'])->middleware('permission:policy.audit_view');
        Route::get('/', [PolicyController::class, 'index'])->middleware('permission:policy.view');

    });

    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->middleware('permission:'.PermissionEnum::EMPLOYEE_VIEW_ALL->value);
        Route::post('/', [EmployeeController::class, 'store'])->middleware('permission:'.PermissionEnum::EMPLOYEE_CREATE->value);
        Route::patch('/profile', [EmployeeController::class, 'updateProfile'])->middleware('permission:'.PermissionEnum::EMPLOYEE_UPDATE_PROFILE->value);
        Route::get('/{id}', [EmployeeController::class, 'show'])->middleware('permission:'.PermissionEnum::EMPLOYEE_VIEW_PROFILE->value);
        Route::patch('/{id}/hr-fields', [EmployeeController::class, 'updateHrFields'])->middleware('permission:'.PermissionEnum::EMPLOYEE_EDIT_HR_FIELDS->value);
        Route::patch('/{id}/change-account-status', [EmployeeController::class, 'changeAccountStatus'])->middleware('permission:'.PermissionEnum::EMPLOYEE_CHANGE_ACCOUNT_STATUS->value);
    });

    Route::prefix('departments')->group(function () {
        Route::get('/managers-dropdown', [DepartmentController::class,'GetManagersDropdown'])->middleware('permission:'.PermissionEnum::VIEW_MANAGERS_DROPDOWN->value);
        Route::get('/', [DepartmentController::class, 'index'])->middleware('permission:'.PermissionEnum::DEPARTMENT_VIEW->value);
        Route::post('/', [DepartmentController::class, 'store'])->middleware('permission:'.PermissionEnum::DEPARTMENT_CREATE->value);
        Route::patch('/{id}', [DepartmentController::class, 'update'])->middleware('permission:'.PermissionEnum::DEPARTMENT_EDIT->value);
        Route::patch('/{id}/change-status', [DepartmentController::class, 'changeStatus'])->middleware('permission:'.PermissionEnum::DEPARTMENT_CHANGE_STATUS->value);
    });

    Route::prefix('locations/company/location')->group(function () {
        Route::get('/active', [CompanyLocationController::class, 'activeLocation'])->middleware('permission:'.PermissionEnum::LOCATION_VIEW_ACTIVE->value);
        Route::post('/', [CompanyLocationController::class, 'store'])->middleware('permission:'.PermissionEnum::LOCATION_CREATE->value);
        Route::put('/{id}', [CompanyLocationController::class, 'update'])->middleware('permission:'.PermissionEnum::LOCATION_UPDATE->value);
        Route::patch('/{id}/deactivate', [CompanyLocationController::class, 'deactivate'])->middleware('permission:'.PermissionEnum::LOCATION_DEACTIVATE->value);
        Route::patch('/{id}/activate', [CompanyLocationController::class, 'activate'])->middleware('permission:'.PermissionEnum::LOCATION_ACTIVATE->value);
    });

    Route::prefix('tasks')->group(function () {
        Route::post('/', [TaskController::class, 'store'])->middleware('permission:'.PermissionEnum::TASK_CREATE->value);
        Route::put('/{task}', [TaskController::class, 'update'])->middleware('permission:'.PermissionEnum::TASK_UPDATE->value);
        Route::post('/{task}/assign', [TaskController::class, 'assign'])->middleware('permission:'.PermissionEnum::TASK_ASSIGN->value);
        Route::patch('/{task}/progress', [TaskController::class, 'updateProgress'])->middleware('permission:'.PermissionEnum::TASK_UPDATE_PROGRESS->value);
        Route::patch('/{task}/status', [TaskController::class, 'updateStatus'])->middleware('permission:'.PermissionEnum::TASK_UPDATE_STATUS->value);

        Route::post('/{task}/submissions', [SubmissionController::class, 'store'])->middleware('permission:'.PermissionEnum::SUBMISSION_CREATE->value);
        Route::post('/submissions/{submission}/attachments', [SubmissionController::class, 'attachFile'])->middleware('permission:'.PermissionEnum::SUBMISSION_ATTACH_FILE->value);
        Route::get('/submissions/review', [SubmissionController::class, 'reviewQueue'])->middleware('permission:'.PermissionEnum::SUBMISSION_REVIEW_QUEUE->value);
        Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->middleware('permission:'.PermissionEnum::SUBMISSION_VIEW->value);
        Route::patch('/submissions/{submission}/approve', [SubmissionController::class, 'approve'])->middleware('permission:'.PermissionEnum::SUBMISSION_APPROVE->value);
        Route::patch('/submissions/{submission}/reject', [SubmissionController::class, 'reject'])->middleware('permission:'.PermissionEnum::SUBMISSION_REJECT->value);
        Route::patch('/submissions/{submission}/request-changes', [SubmissionController::class, 'requestChanges'])->middleware('permission:'.PermissionEnum::SUBMISSION_REQUEST_CHANGES->value);
        Route::post('/submissions/{submission}/resubmit', [SubmissionController::class, 'resubmit'])->middleware('permission:'.PermissionEnum::SUBMISSION_RESUBMIT->value);
    });

    Route::prefix('attendance')->group(function () {
        Route::get('/today', [AttendanceController::class, 'today'])->middleware('permission:'.PermissionEnum::ATTENDANCE_CHECKIN_CHECKOUT->value);
        Route::post('/check-in', [AttendanceController::class, 'checkIn'])->middleware('permission:'.PermissionEnum::ATTENDANCE_CHECKIN_CHECKOUT->value);
        Route::post('/check-out', [AttendanceController::class, 'checkOut'])->middleware('permission:'.PermissionEnum::ATTENDANCE_CHECKIN_CHECKOUT->value);
        Route::get('/history', [AttendanceController::class, 'history'])->middleware('permission:'.PermissionEnum::ATTENDANCE_VIEW_HISTORY->value);
    });

    Route::prefix('manager')->group(function () {
        Route::get('/employees', [ManagerController::class, 'employees'])->middleware('permission:'.PermissionEnum::MANAGER_VIEW_EMPLOYEES->value);
        Route::get('/attendance/today', [ManagerAttendanceController::class, 'today'])->middleware('permission:'.PermissionEnum::MANAGER_VIEW_ATTENDANCE->value);
        Route::get('/attendance/{employeeId}', [ManagerAttendanceController::class, 'show'])->middleware('permission:'.PermissionEnum::MANAGER_VIEW_ATTENDANCE->value);
        Route::get('/team-goals', [ManagerController::class, 'teamGoals'])->middleware('permission:'.PermissionEnum::MANAGER_VIEW_TEAM_GOALS->value);
        Route::get('/team-performance', [ManagerPerformanceController::class, 'teamDashboard'])->middleware('permission:'.PermissionEnum::MANAGER_PERFORMANCE_TEAM->value);
    });

    Route::prefix('hr')->group(function () {
        Route::get('/attendance/daily', [HrAttendanceController::class, 'daily'])->middleware('permission:'.PermissionEnum::HR_ATTENDANCE_VIEW_DAILY->value);
        Route::get('/attendance/exceptions', [HrAttendanceController::class, 'exceptions'])->middleware('permission:'.PermissionEnum::HR_ATTENDANCE_VIEW_EXCEPTIONS->value);
        Route::get('/attendance/monthly-summary', [HrAttendanceController::class, 'monthlySummary'])->middleware('permission:'.PermissionEnum::HR_ATTENDANCE_VIEW_SUMMARY->value);
        Route::get('/attendance/export', [HrAttendanceController::class, 'export'])->middleware('permission:'.PermissionEnum::HR_ATTENDANCE_EXPORT->value);
        Route::get('/goals', [HrGoalController::class, 'index'])->middleware('permission:'.PermissionEnum::HR_GOALS_OVERVIEW->value);
        Route::get('/company-performance', [HrPerformanceController::class, 'companyDashboard'])->middleware('permission:'.PermissionEnum::HR_PERFORMANCE_COMPANY->value);
    });

    Route::prefix('goals')->group(function () {
        Route::get('/', [GoalController::class, 'index'])->middleware('permission:'.PermissionEnum::GOAL_VIEW_OWN->value);
        Route::post('/', [GoalController::class, 'store'])->middleware('permission:'.PermissionEnum::GOAL_CREATE->value);
        Route::get('/{id}', [GoalController::class, 'show'])->middleware('permission:'.PermissionEnum::GOAL_VIEW_OWN->value);
        Route::put('/{id}', [GoalController::class, 'update'])->middleware('permission:'.PermissionEnum::GOAL_UPDATE->value);
        Route::patch('/{id}/progress', [GoalController::class, 'updateProgress'])->middleware('permission:'.PermissionEnum::GOAL_UPDATE_PROGRESS->value);
        Route::patch('/{id}/complete', [GoalController::class, 'complete'])->middleware('permission:'.PermissionEnum::GOAL_COMPLETE->value);
    });

    Route::prefix('evaluation-periods')->group(function () {
        Route::get('/', [HrEvaluationSetupController::class, 'listPeriods'])->middleware('permission:'.PermissionEnum::EVALUATION_MANAGE_SETUP->value);
        Route::post('/', [HrEvaluationSetupController::class, 'storePeriod'])->middleware('permission:'.PermissionEnum::EVALUATION_MANAGE_SETUP->value);
        Route::patch('/{id}/toggle-status', [HrEvaluationSetupController::class, 'togglePeriodStatus'])->middleware('permission:'.PermissionEnum::EVALUATION_MANAGE_SETUP->value);
    });

    Route::prefix('evaluation-categories')->group(function () {
        Route::get('/', [HrEvaluationSetupController::class, 'listCategories'])->middleware('permission:'.PermissionEnum::EVALUATION_MANAGE_SETUP->value);
        Route::post('/', [HrEvaluationSetupController::class, 'storeCategory'])->middleware('permission:'.PermissionEnum::EVALUATION_MANAGE_SETUP->value);
    });

    Route::prefix('evaluations')->group(function () {
        Route::post('/', [EvaluationController::class, 'store'])->middleware('permission:'.PermissionEnum::EVALUATION_CREATE->value);
        Route::put('/{id}', [EvaluationController::class, 'update'])->middleware('permission:'.PermissionEnum::EVALUATION_UPDATE->value);
        Route::patch('/{id}/complete', [EvaluationController::class, 'complete'])->middleware('permission:'.PermissionEnum::EVALUATION_COMPLETE->value);
        Route::get('/manager', [EvaluationController::class, 'managerEvaluations'])->middleware('permission:'.PermissionEnum::EVALUATION_VIEW_MANAGER->value);
        Route::get('/employee', [EmployeeEvaluationController::class, 'index'])->middleware('permission:'.PermissionEnum::EVALUATION_VIEW_EMPLOYEE->value);
        Route::get('/hr', [HrEvaluationSetupController::class, 'index'])->middleware('permission:'.PermissionEnum::EVALUATION_MANAGE_SETUP->value);
    });

    Route::get('/employee/performance', [EmployeePerformanceController::class, 'dashboard'])->middleware('permission:'.PermissionEnum::EMPLOYEE_PERFORMANCE_DASHBOARD->value);

    Route::prefix('leaves')->group(function () {
        Route::post('/leave-types', [LeaveTypeController::class, 'store'])->middleware('permission:'.PermissionEnum::LEAVE_REQUEST_CREATE->value);
        Route::get('/leave-types', [LeaveTypeController::class, 'index'])->middleware('permission:'.PermissionEnum::LEAVE_BALANCE_VIEW->value);
        Route::put('/leave-types/{leaveType}', [LeaveTypeController::class, 'update'])->middleware('permission:'.PermissionEnum::LEAVE_REQUEST_CREATE->value);
        Route::patch('/leave-types/{leaveType}/activate', [LeaveTypeController::class, 'activate'])->middleware('permission:'.PermissionEnum::LEAVE_APPROVE_HR->value);
        Route::patch('/leave-types/{leaveType}/deactivate', [LeaveTypeController::class, 'deactivate'])->middleware('permission:'.PermissionEnum::LEAVE_APPROVE_HR->value);
        Route::get('/leave-balances', [LeaveBalanceController::class, 'index'])->middleware('permission:'.PermissionEnum::LEAVE_BALANCE_VIEW->value);
        Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->middleware('permission:'.PermissionEnum::LEAVE_REQUEST_CREATE->value);
        Route::patch('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->middleware('permission:'.PermissionEnum::LEAVE_APPROVE_MANAGER->value);
        Route::patch('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->middleware('permission:'.PermissionEnum::LEAVE_REJECT->value);
        Route::get('/leave-requests', [LeaveRequestController::class, 'history'])->middleware('permission:'.PermissionEnum::LEAVE_VIEW_HISTORY->value);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/clear-all', [NotificationController::class, 'clearAll']);
        Route::post('/fcm-token', [NotificationController::class, 'updateFcmToken']);
    });

    Route::prefix('files')->group(function () {
        Route::get('/{file}/download', [FileController::class, 'download'])->name('files.download')->middleware('permission:'.PermissionEnum::FILE_DOWNLOAD->value);
        Route::delete('/{file}', [FileController::class, 'destroy'])->name('files.destroy')->middleware('permission:'.PermissionEnum::FILE_DELETE->value);
    });

    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:'.PermissionEnum::PERMISSION_VIEW_ALL->value);

});

Route::middleware(['auth:api', 'set.app.language'])->post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
});
Route::prefix('ai')->middleware(['auth:api', 'set.app.language'])->group(function () {
    Route::post('/career-coach', AICareerCoachController::class)
        ->middleware('permission:'.PermissionEnum::AI_CAREER_COACH->value);

    Route::post('/performance-insight', AIPerformanceInsightController::class)
        ->middleware('permission:'.PermissionEnum::AI_PERFORMANCE_INSIGHT->value);

    Route::post('/evaluation-draft', AIEvaluationDraftController::class)
        ->middleware('permission:'.PermissionEnum::AI_EVALUATION_DRAFT->value);

    Route::post('/skill-gap', AISkillGapController::class)
        ->middleware('permission:'.PermissionEnum::AI_SKILL_GAP->value);

    Route::post('/attention-signal', AIAttentionSignalController::class)
        ->middleware('permission:'.PermissionEnum::AI_ATTENTION_SIGNAL->value);

    Route::post('/team-insight', AITeamInsightController::class)
        ->middleware('permission:'.PermissionEnum::AI_TEAM_INSIGHT->value);

    Route::post('/policy-assistant', AIPolicyAssistantController::class)
        ->middleware('permission:'.PermissionEnum::AI_POLICY_ASSISTANT->value);
});
