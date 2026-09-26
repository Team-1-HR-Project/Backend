<?php

namespace App\Http\Controllers\Api;

use App\Enums\GoalStatus;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalProgressRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Http\Resources\GoalResource;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class GoalController extends Controller
{
    public function __construct(private GoalService $goalService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return ResponseHelper::error(null, __('goal.user_not_found'), 404);
            }

            $status = $request->query('status');
            $goals = $this->goalService->getEmployeeGoals($user, $status);
            $paginatedData = GoalResource::collection($goals)->response()->getData(true);

            return ResponseHelper::success(
                data: [
                    'goals' => $paginatedData['data'],
                    'links' => $paginatedData['links'],
                    'meta' => $paginatedData['meta'],
                ],
                message: __('goal.retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('goal.failed_retrieve'),
                500
            );
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return ResponseHelper::error(null, __('goal.user_not_found'), 404);
            }

            $goal = $this->goalService->getEmployeeGoalDetails($user, $id);

            if (! $goal) {
                return ResponseHelper::error(null, __('goal.not_found'), 404);
            }

            return ResponseHelper::success(
                new GoalResource($goal),
                __('goal.details_retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('goal.failed_details'),
                500
            );
        }
    }

    public function store(StoreGoalRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return ResponseHelper::error(null, __('goal.user_not_found'), 404);
            }

            $goal = $this->goalService->createGoal($user, $request->validated());

            return ResponseHelper::success(
                new GoalResource($goal),
                __('goal.created'),
                201
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('goal.failed_create'),
                500
            );
        }
    }

    public function update(UpdateGoalRequest $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return ResponseHelper::error(null, __('goal.user_not_found'), 404);
            }

            $goal = $this->goalService->getEmployeeGoalDetails($user, $id);

            if (! $goal) {
                return ResponseHelper::error(null, __('goal.not_found'), 404);
            }

            if ($goal->status === GoalStatus::COMPLETED || $goal->status === GoalStatus::CANCELLED) {
                return ResponseHelper::error(null, __('goal.cannot_modify_closed'), 422);
            }

            $updatedGoal = $this->goalService->updateGoal($goal, $request->validated());

            return ResponseHelper::success(
                new GoalResource($updatedGoal),
                __('goal.updated')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('goal.failed_update'),
                500
            );
        }
    }

    public function updateProgress(UpdateGoalProgressRequest $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return ResponseHelper::error(null, __('goal.user_not_found'), 404);
            }

            $goal = $this->goalService->getEmployeeGoalDetails($user, $id);

            if (! $goal) {
                return ResponseHelper::error(null, __('goal.not_found'), 404);
            }

            if ($goal->status === GoalStatus::CANCELLED) {
                return ResponseHelper::error(null, __('goal.cannot_update_cancelled'), 422);
            }

            $updatedGoal = $this->goalService->updateProgress(
                $goal,
                (float) $request->validated('current_value'),
                $user->id,
                $request->validated('note')
            );

            return ResponseHelper::success(
                new GoalResource($updatedGoal),
                __('goal.progress_updated')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('goal.failed_progress'),
                500
            );
        }
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return ResponseHelper::error(null, __('goal.user_not_found'), 404);
            }

            $goal = $this->goalService->getEmployeeGoalDetails($user, $id);

            if (! $goal) {
                return ResponseHelper::error(null, __('goal.not_found'), 404);
            }

            if ($goal->status === GoalStatus::COMPLETED) {
                return ResponseHelper::error(null, __('goal.already_completed'), 422);
            }

            $completedGoal = $this->goalService->markAsCompleted($goal);

            return ResponseHelper::success(
                new GoalResource($completedGoal),
                __('goal.completed')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('goal.failed_complete'),
                500
            );
        }
    }
}
