<?php

namespace App\Http\Controllers\Api;

use App\Enums\EvaluationStatus;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Requests\UpdateEvaluationRequest;
use App\Http\Resources\EvaluationResource;
use App\Jobs\SendNotificationJob;
use App\Models\Evaluation;
use App\Services\EvaluationService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class EvaluationController extends Controller
{
    public function __construct(private EvaluationService $evaluationService) {}

    public function store(StoreEvaluationRequest $request): JsonResponse
    {
        try {
            $evaluator = $request->user();

            $evaluation = $this->evaluationService->saveDraft($evaluator->id, $request->validated());

            return ResponseHelper::success(
                new EvaluationResource($evaluation),
                __('evaluation.draft_created'),
                201
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                $e->getMessage() ?: __('evaluation.failed_save_draft'),
                400
            );
        }
    }

    public function update(UpdateEvaluationRequest $request, int $id): JsonResponse
    {
        try {
            $evaluation = Evaluation::with(['scores', 'evidence'])->find($id);

            if (! $evaluation) {
                return ResponseHelper::error(null, __('evaluation.not_found'), 404);
            }

            if ($evaluation->status === EvaluationStatus::COMPLETED) {
                return ResponseHelper::error(null, __('evaluation.completed_immutable'), 422);
            }

            $updatedEvaluation = $this->evaluationService->saveDraft(
                $request->user()->id,
                $request->validated(),
                $evaluation
            );

            return ResponseHelper::success(
                new EvaluationResource($updatedEvaluation),
                __('evaluation.draft_updated')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                $e->getMessage() ?: __('evaluation.failed_update'),
                400
            );
        }
    }

    public function complete(Request $request, int $id, NotificationService $notificationService): JsonResponse
    {
        try {
            $evaluation = Evaluation::with(['scores.category', 'user', 'period'])->find($id);

            if (! $evaluation) {
                return ResponseHelper::error(null, __('evaluation.not_found'), 404);
            }

            $completedEvaluation = $this->evaluationService->completeEvaluation($evaluation);

            if ($evaluation->user) {
                SendNotificationJob::dispatch(
                    user: $evaluation->user,
                    type: 'evaluation_closed',
                    titleKey: 'notifications.evaluation_closed_title',
                    bodyKey: 'notifications.evaluation_closed_body',
                    parameters: [
                        'period_name' => $evaluation->period?->name ?? 'the evaluation period',
                    ],
                    metadata: [
                        'screen' => 'evaluation_summary',
                        'evaluation_id' => $completedEvaluation->id,
                        'period_id' => $completedEvaluation->period_id,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]
                );
            }

            return ResponseHelper::success(
                new EvaluationResource($completedEvaluation),
                __('evaluation.completed_success')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                $e->getMessage() ?: __('evaluation.failed_complete'),
                400
            );
        }
    }

    public function managerEvaluations(Request $request): JsonResponse
    {
        try {
            $manager = $request->user();

            if (! $manager) {
                return ResponseHelper::error(null, __('evaluation.user_not_found'), 404);
            }

            $status = $request->query('status');
            $periodId = $request->query('period_id') ? (int) $request->query('period_id') : null;

            $evaluations = $this->evaluationService->getManagerTeamEvaluations($manager, $status, $periodId);

            $paginatedData = EvaluationResource::collection($evaluations)->response()->getData(true);

            return ResponseHelper::success(
                data: [
                    'evaluations' => $paginatedData['data'],
                    'links' => $paginatedData['links'],
                    'meta' => $paginatedData['meta'],
                ],
                message: __('evaluation.team_evaluations')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('evaluation.failed_team'),
                500
            );
        }
    }
}
