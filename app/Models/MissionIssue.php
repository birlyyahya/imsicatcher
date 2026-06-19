<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tanggal',
    'lokasi',
    'jenis',
    'deskripsi',
    'pelapor',
    'pihak_terlibat',
    'satker',
    'tindakan',
    'status',
    'foto_bukti',
    'dibuat_oleh',
])]
class MissionIssue extends Model
{
    protected function casts(): array
    {
        return [
            'tanggal' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Limit the query to the records the given user is allowed to see.
     *
     * - Superadmin (Kejagung): seluruh satker.
     * - Admin (Kejati): hanya satker miliknya (termasuk data operatornya).
     * - Operator: hanya data miliknya sendiri di satkernya.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'superadmin' => $query,
            'admin' => $query->where('satker', $user->satker),
            default => $query->where('satker', $user->satker)
                ->where('dibuat_oleh', $user->id),
        };
    }

    /**
     * Whether the given user may view/manage this specific issue.
     */
    public function isManageableBy(User $user): bool
    {
        return match ($user->role) {
            'superadmin' => true,
            'admin' => $this->satker === $user->satker,
            default => $this->satker === $user->satker
                && (int) $this->dibuat_oleh === (int) $user->id,
        };
    }
}

