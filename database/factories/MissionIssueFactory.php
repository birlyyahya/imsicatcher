<?php

namespace Database\Factories;

use App\Models\MissionIssue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionIssue>
 */
class MissionIssueFactory extends Factory
{
    protected $model = MissionIssue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => fake()->dateTimeBetween('-30 days', 'now'),
            'lokasi' => fake()->city(),
            'jenis' => fake()->randomElement(['Gangguan Sinyal', 'Kerusakan Alat', 'Pelacakan Posisi', 'Kendala Lapangan']),
            'deskripsi' => fake()->paragraph(),
            'pelapor' => fake()->name(),
            'pihak_terlibat' => fake()->optional()->company(),
            // Default null; gunakan state forCreator() agar satker & pembuat konsisten.
            'satker' => null,
            'tindakan' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['baru', 'proses', 'selesai']),
            'foto_bukti' => null,
            'dibuat_oleh' => null,
        ];
    }

    /**
     * Kunci masalah misi ke pembuat tertentu sekaligus satker (string nama) miliknya
     * agar konsisten dengan scopeVisibleTo (satker + dibuat_oleh).
     */
    public function forCreator(User $user): static
    {
        return $this->state(fn () => [
            'satker' => $user->satker,
            'dibuat_oleh' => $user->id,
        ]);
    }
}
