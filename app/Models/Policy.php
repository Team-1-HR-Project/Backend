<?php

namespace App\Models;

use App\Enums\PolicyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Policy extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => PolicyStatus::class,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PolicyVersion::class);
    }

    public function activeVersion(): HasOne
    {
        return $this->hasOne(PolicyVersion::class)
            ->where('status', 'active');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PolicyAudit::class);
    }
}
