<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'user_name',
    'action',
    'description',
    'agent',
    'ip_address',
    'user_agent',
    'metadata',
    'logged_at',
])]
class ActivityLog extends Model
{
    protected $table = 'logs';

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'logged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Batasi log sesuai role pengguna.
     *
     * - Superadmin (Kejagung): seluruh log.
     * - Admin (Kejati): log dari user di satkernya sendiri.
     * - Operator: hanya log miliknya sendiri.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'superadmin' => $query,
            'admin' => $query->whereHas('user', fn (Builder $q) => $q->where('satker', $user->satker)),
            default => $query->where('user_id', $user->id),
        };
    }
}

