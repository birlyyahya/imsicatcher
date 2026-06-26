<?php

namespace App\Models;

use Database\Factories\AsetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nama_aset',
    'kategori',
    'nomor_seri',
    'status',
    'satker_id',
    'tanggal_pengadaan',
    'catatan',
])]
class Aset extends Model
{
    /** @use HasFactory<AsetFactory> */
    use HasFactory;

    /**
     * Label terbaca untuk enum kategori.
     *
     * @var array<string, string>
     */
    public const KATEGORI = [
        'server' => 'Server',
        'laptop_operasi' => 'Laptop Operasi',
        'drone' => 'Drone',
        'stingray_interceptor' => 'Stingray / Interceptor',
        'hhdf' => 'HHDF',
        'kendaraan' => 'Kendaraan',
    ];

    /**
     * Label terbaca untuk enum status.
     *
     * @var array<string, string>
     */
    public const STATUS = [
        'aktif' => 'Aktif',
        'maintenance' => 'Maintenance',
        'rusak' => 'Rusak',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengadaan' => 'date',
        ];
    }

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class);
    }

    public function kategoriLabel(): string
    {
        return self::KATEGORI[$this->kategori] ?? $this->kategori;
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    /**
     * Limit the query to the records the given user is allowed to see.
     *
     * - Superadmin (Kejagung): seluruh satker.
     * - Admin/Operator: hanya aset di satker miliknya.
     *
     * Meskipun ini master data, scoping per satker tetap diterapkan agar tidak
     * terjadi kebocoran data lintas satker (lihat pola OperasiAlat::scopeVisibleTo).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'superadmin' => $query,
            default => $query->where('satker_id', $user->satker_id),
        };
    }

    /**
     * Whether the given user may view/manage this specific aset.
     */
    public function isManageableBy(User $user): bool
    {
        return match ($user->role) {
            'superadmin' => true,
            default => (int) $this->satker_id === (int) $user->satker_id,
        };
    }
}
