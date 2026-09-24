<?php

namespace App\Models;

use App\Enums\PolicyVersionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolicyVersion extends Model
{
    protected $fillable = [
        'policy_id',
        'version',
        'content',
        'status',
        'effective_date',
        'created_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'status' => PolicyVersionStatus::class,
        'effective_date' => 'date',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PolicyAudit::class);
    }
}
