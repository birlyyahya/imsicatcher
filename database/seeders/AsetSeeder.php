<?php

namespace Database\Seeders;

use App\Models\Aset;
use App\Models\Satker;
use Illuminate\Database\Seeder;

class AsetSeeder extends Seeder
{
    /**
     * Daftar aset standar yang dibuat untuk setiap satker.
     *
     * @var array<int, array{nama_aset: string, kategori: string, status: string}>
     */
    private array $items = [
        ['nama_aset' => 'Server Pusat Data', 'kategori' => 'server', 'status' => 'aktif'],
        ['nama_aset' => 'Laptop Operasi Lapangan', 'kategori' => 'laptop_operasi', 'status' => 'aktif'],
        ['nama_aset' => 'Drone Pemantau Udara', 'kategori' => 'drone', 'status' => 'maintenance'],
        ['nama_aset' => 'Unit Stingray / Interceptor', 'kategori' => 'stingray_interceptor', 'status' => 'aktif'],
        ['nama_aset' => 'HHDF Portable', 'kategori' => 'hhdf', 'status' => 'aktif'],
        ['nama_aset' => 'Kendaraan Operasional', 'kategori' => 'kendaraan', 'status' => 'rusak'],
    ];

    public function run(): void
    {
        // Hapus data aset lama agar tidak duplikat (nomor_seri unik). Tidak menyentuh satker.
        Aset::query()->delete();

        $satkers = Satker::query()->get();

        if ($satkers->isEmpty()) {
            $this->command->warn('AsetSeeder dilewati: belum ada satker.');

            return;
        }

        foreach ($satkers as $satker) {
            foreach ($this->items as $item) {
                Aset::factory()
                    ->forSatker($satker)
                    ->create([
                        'nama_aset' => $item['nama_aset'].' - '.$satker->nama,
                        'kategori' => $item['kategori'],
                        'status' => $item['status'],
                    ]);
            }
        }

        $this->command->info('AsetSeeder selesai: total '.Aset::count().' aset dibuat.');
    }
}
