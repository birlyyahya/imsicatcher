<?php

namespace Database\Factories;

use App\Models\Aset;
use App\Models\Satker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aset>
 */
class AsetFactory extends Factory
{
    protected $model = Aset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_aset' => 'Aset '.fake()->unique()->numerify('####'),
            'kategori' => fake()->randomElement(array_keys(Aset::KATEGORI)),
            'nomor_seri' => strtoupper(fake()->unique()->bothify('SN-####-????')),
            'status' => fake()->randomElement(array_keys(Aset::STATUS)),
            'satker_id' => Satker::query()->inRandomOrder()->value('id'),
            'tanggal_pengadaan' => fake()->dateTimeBetween('-3 years', '-1 month'),
            'catatan' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Tautkan aset ke satker tertentu.
     */
    public function forSatker(Satker $satker): static
    {
        return $this->state(fn () => [
            'satker_id' => $satker->id,
        ]);
    }
}
