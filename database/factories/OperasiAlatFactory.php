<?php

namespace Database\Factories;

use App\Models\MissionIssue;
use App\Models\OperasiAlat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperasiAlat>
 */
class OperasiAlatFactory extends Factory
{
    protected $model = OperasiAlat::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $waktuMulai = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'jenis_alat' => fake()->randomElement(array_keys(OperasiAlat::JENIS_ALAT)),
            // Default null; gunakan state forOperator() agar operator & satker konsisten.
            'operator_id' => null,
            'satker_id' => null,
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => fake()->optional(0.7)->dateTimeBetween($waktuMulai, 'now'),
            'lokasi' => fake()->optional()->city(),
            'tujuan_operasi' => fake()->sentence(10),
            'hasil' => fake()->optional(0.8)->randomElement(array_keys(OperasiAlat::HASIL)),
            'foto_bukti' => null,
            'mission_issue_id' => null,
            'catatan' => fake()->optional()->sentence(8),
        ];
    }

    /**
     * Kunci log ke operator tertentu sekaligus satker miliknya
     * agar konsisten dengan scopeVisibleTo (satker_id + operator_id).
     */
    public function forOperator(User $user): static
    {
        return $this->state(fn () => [
            'operator_id' => $user->id,
            'satker_id' => $user->satker_id,
        ]);
    }

    /**
     * Tautkan log ke sebuah masalah misi.
     */
    public function forMissionIssue(MissionIssue $issue): static
    {
        return $this->state(fn () => [
            'mission_issue_id' => $issue->id,
        ]);
    }
}
