<?php

namespace App\Services\Policies;

use App\Enums\PolicyStatus;
use App\Enums\PolicyVersionStatus;
use App\Models\Policy;
use App\Models\PolicyAudit;
use App\Models\PolicyVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PolicyService
{
    public function create(User $user, array $policyData, string $content): Policy
    {
        return DB::transaction(function () use ($user, $policyData, $content) {

            $policy = Policy::create([
                'title' => $policyData['title'],
                'description' => $policyData['description'] ?? null,
                'status' => PolicyStatus::Draft,
                'created_by' => $user->id,
            ]);

            PolicyVersion::create([
                'policy_id' => $policy->id,
                'version' => 1,
                'content' => $content,
                'status' => PolicyVersionStatus::Draft,
                'effective_date' => null,
                'created_by' => $user->id,
            ]);

            return $policy->load('versions');
        });
    }

    public function createVersion(User $user, Policy $policy, string $content): PolicyVersion
    {
        return DB::transaction(function () use ($user, $policy, $content) {

            $nextVersion = ((int) $policy->versions()->max('version')) + 1;

            return PolicyVersion::create([
                'policy_id' => $policy->id,
                'version' => $nextVersion,
                'content' => $content,
                'status' => PolicyVersionStatus::Draft,
                'effective_date' => null,
                'created_by' => $user->id,
            ]);
        });
    }

    public function activateVersion(
        User $user,
        Policy $policy,
        PolicyVersion $version
    ): PolicyVersion {
        return DB::transaction(function () use ($user, $policy, $version) {

            if ($version->policy_id !== $policy->id) {
                throw new \InvalidArgumentException(
                    'The policy version does not belong to this policy.'
                );
            }

            $oldStatus = $version->status->value;

            $policy->versions()
                ->where('id', '!=', $version->id)
                ->update([
                    'status' => PolicyVersionStatus::Archived,
                ]);

            $version->update([
                'status' => PolicyVersionStatus::Active,
                'effective_date' => now()->toDateString(),
            ]);

            $policy->update([
                'status' => PolicyStatus::Active,
            ]);

            PolicyAudit::create([
                'policy_id' => $policy->id,
                'policy_version_id' => $version->id,
                'performed_by' => $user->id,
                'action' => 'version_activated',
                'old_status' => $oldStatus,
                'new_status' => PolicyVersionStatus::Active->value,
                'description' => "Policy version {$version->version} was activated.",
            ]);

            return $version->refresh();
        });
    }

    public function getActiveVersion(Policy $policy): ?PolicyVersion
    {
        return $policy->versions()
            ->where('status', PolicyVersionStatus::Active)
            ->first();
    }

    // policy list for HR
    public function list(array $filters = [])
    {
        return Policy::query()
            ->with([
                'versions' => function ($query) use ($filters) {
                    if (! empty($filters['version_status'])) {
                        $query->where(
                            'status',
                            $filters['version_status']
                        );
                    }

                    $query->orderByDesc('version');
                },
            ])
            ->when(
                ! empty($filters['status']),
                fn ($query) => $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->latest()
            ->get();
    }

    // audit history
    public function getAuditHistory(Policy $policy)
    {
        return $policy->audits()
            ->with([
                'policyVersion:id,policy_id,version,content,status,effective_date',
                'performer:id,name',
            ])
            ->latest()
            ->get();
    }
}
