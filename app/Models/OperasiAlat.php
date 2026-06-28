<?php

namespace App\Models;

use Database\Factories\OperasiAlatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'jenis_alat',
    'operator_id',
    'satker_id',
    'waktu_mulai',
    'waktu_selesai',
    'lokasi',
    'latitude',
    'longitude',
    'lokasi_keterangan',
    'tujuan_operasi',
    'hasil',
    'foto_bukti',
    'mission_issue_id',
    'catatan',
])]
class OperasiAlat extends Model
{
    /** @use HasFactory<OperasiAlatFactory> */
    use HasFactory;

    /**
     * Nama tabel tidak mengikuti pluralisasi default Laravel (operasi_alats).
     */
    protected $table = 'operasi_alat';

    /**
     * Label terbaca untuk enum jenis alat.
     *
     * @var array<string, string>
     */
    public const JENIS_ALAT = [
        'stingray_interceptor' => 'Stingray / Interceptor',
        'hhdf' => 'HHDF',
        'drone' => 'Drone',
        'laptop_operasi' => 'Laptop Operasi',
    ];

    /**
     * Label terbaca untuk enum hasil operasi.
     *
     * @var array<string, string>
     */
    public const HASIL = [
        'berhasil' => 'Berhasil',
        'gagal' => 'Gagal',
        'sebagian' => 'Sebagian',
    ];

    /**
     * Titik default peta saat koordinat belum ditandai (Manokwari, Papua Barat).
     */
    public const DEFAULT_LAT = -0.8615;
    public const DEFAULT_LNG = 134.0620;

    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class, 'satker_id');
    }

    public function missionIssue(): BelongsTo
    {
        return $this->belongsTo(MissionIssue::class);
    }

    public function jenisAlatLabel(): string
    {
        return self::JENIS_ALAT[$this->jenis_alat] ?? $this->jenis_alat;
    }

    public function hasilLabel(): ?string
    {
        return $this->hasil ? (self::HASIL[$this->hasil] ?? $this->hasil) : null;
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
            'admin' => $query->where('satker_id', $user->satker_id),
            default => $query->where('satker_id', $user->satker_id)
                ->where('operator_id', $user->id),
        };
    }

    /**
     * Whether the given user may view/manage this specific log.
     */
    public function isManageableBy(User $user): bool
    {
        return match ($user->role) {
            'superadmin' => true,
            'admin' => (int) $this->satker_id === (int) $user->satker_id,
            default => (int) $this->satker_id === (int) $user->satker_id
                && (int) $this->operator_id === (int) $user->id,
        };
    }
}
