<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\EvaluationResource;
use App\Services\EvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class EmployeeEvaluationController extends Controller
{
    public function __construct(private EvaluationService $evaluationService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return ResponseHelper::error(null, __('evaluation.user_not_found'), 404);
            }

            $evaluations = $this->evaluationService->getEmployeeEvaluationsHistory($user, 10);

            $paginatedData = EvaluationResource::collection($evaluations)->response()->getData(true);

            return ResponseHelper::success(
                data: [
                    'evaluations' => $paginatedData['data'],
                    'links' => $paginatedData['links'],
                    'meta' => $paginatedData['meta'],
                ],
                message: __('evaluation.history_retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('evaluation.failed_history'),
                500
            );
        }
    }
}
