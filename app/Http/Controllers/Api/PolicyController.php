<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policies\IndexPolicyRequest;
use App\Http\Requests\Policies\StorePolicyRequest;
use App\Http\Requests\Policies\StorePolicyVersionRequest;
use App\Models\Policy;
use App\Models\PolicyVersion;
use App\Services\Policies\PolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    public function __construct(
        private PolicyService $policyService
    ) {}

    public function store(StorePolicyRequest $request): JsonResponse
    {
        $policy = $this->policyService->create(
            user: $request->user(),
            policyData: $request->only([
                'title',
                'description',
            ]),
            content: $request->validated('content'),
        );

        return ResponseHelper::success(
            data: $policy,
            message: __('messages.policy_created_successfully')
        );
    }

    public function storeVersion(StorePolicyVersionRequest $request, Policy $policy): JsonResponse
    {
        $version = $this->policyService->createVersion(
            user: $request->user(),
            policy: $policy,
            content: $request->validated('content'),
        );

        return ResponseHelper::success(
            data: $version,
            message: __('messages.policy_version_created_successfully')
        );
    }

    public function activateVersion(Request $request, Policy $policy, PolicyVersion $version): JsonResponse
    {
        $version = $this->policyService->activateVersion(
            user: $request->user(),
            policy: $policy,
            version: $version,
        );

        return ResponseHelper::success(
            data: $version,
            message: __('messages.policy_version_activated_successfully')
        );
    }

    public function active(Policy $policy): JsonResponse
    {
        $version = $this->policyService->getActiveVersion($policy);

        if (! $version) {
            return ResponseHelper::error(
                message: __('messages.no_active_policy_version_found')
            );
        }

        return ResponseHelper::success(
            data: $version,
            message: __('messages.active_policy_retrieved_successfully')
        );
    }

    // policy list for HR
    public function index(IndexPolicyRequest $request): JsonResponse
    {
        $policies = $this->policyService->list(
            $request->validated()
        );

        return ResponseHelper::success(
            data: $policies,
            message: __('messages.policies_retrieved_successfully')
        );
    }

    // audit History
    public function auditHistory(Policy $policy): JsonResponse
    {
        $audits = $this->policyService->getAuditHistory($policy);

        return ResponseHelper::success(
            data: $audits,
            message: __('messages.policy_audit_history_retrieved_successfully')
        );
    }
}
