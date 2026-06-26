<?php

namespace Database\Seeders;

use App\Models\MissionIssue;
use App\Models\OperasiAlat;
use App\Models\User;
use Illuminate\Database\Seeder;

class MissionIssueSeeder extends Seeder
{
    /**
     * Skenario bernarasi: tiap masalah misi punya log operasi alat yang relevan,
     * sehingga keterkaitan masalah misi <-> log operasi alat terlihat jelas.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $scenarios = [
        [
            'jenis' => 'Gangguan Sinyal',
            'lokasi' => 'Pelabuhan Peti Kemas',
            'deskripsi' => 'Sinyal komunikasi target terputus saat pemantauan di area pelabuhan sehingga pelacakan sempat terhenti.',
            'tindakan' => 'Mengerahkan interceptor untuk akuisisi ulang sinyal target.',
            'status' => 'selesai',
            'logs' => [
                ['jenis_alat' => 'stingray_interceptor', 'tujuan_operasi' => 'Akuisisi dan penguncian ulang sinyal target di area pelabuhan.', 'hasil' => 'berhasil'],
            ],
        ],
        [
            'jenis' => 'Kerusakan Alat',
            'lokasi' => 'Perbukitan Sektor 4',
            'deskripsi' => 'Drone kehilangan kendali dan mendarat darurat saat pemantauan udara pergerakan target.',
            'tindakan' => 'Evakuasi unit drone dan penjadwalan maintenance.',
            'status' => 'proses',
            'logs' => [
                ['jenis_alat' => 'drone', 'tujuan_operasi' => 'Pemantauan udara pergerakan target di perbukitan sektor 4.', 'hasil' => 'gagal'],
            ],
        ],
        [
            'jenis' => 'Pelacakan Posisi',
            'lokasi' => 'Permukiman Padat Blok C',
            'deskripsi' => 'Posisi pasti target sulit ditentukan di permukiman padat penduduk akibat banyaknya sumber sinyal.',
            'tindakan' => 'Menggunakan HHDF untuk mempersempit area, lalu konfirmasi perangkat dengan interceptor.',
            'status' => 'proses',
            'logs' => [
                ['jenis_alat' => 'hhdf', 'tujuan_operasi' => 'Direction finding untuk mempersempit posisi target.', 'hasil' => 'sebagian'],
                ['jenis_alat' => 'stingray_interceptor', 'tujuan_operasi' => 'Konfirmasi identitas perangkat target.', 'hasil' => 'berhasil'],
            ],
        ],
        [
            'jenis' => 'Akuisisi Barang Bukti',
            'lokasi' => 'Kantor Tersangka',
            'deskripsi' => 'Diperlukan pengamanan dan akuisisi data dari perangkat elektronik yang disita di lokasi.',
            'tindakan' => 'Akuisisi forensik menggunakan laptop operasi.',
            'status' => 'selesai',
            'logs' => [
                ['jenis_alat' => 'laptop_operasi', 'tujuan_operasi' => 'Akuisisi forensik data dari perangkat yang disita.', 'hasil' => 'berhasil'],
            ],
        ],
        [
            'jenis' => 'Kendala Cuaca',
            'lokasi' => 'Pesisir Utara',
            'deskripsi' => 'Operasi pemantauan terganggu hujan deras dan angin kencang sehingga ditunda.',
            'tindakan' => 'Operasi ditunda; seluruh alat diamankan.',
            'status' => 'baru',
            'logs' => [],
        ],
    ];

    public function run(): void
    {
        // Hapus data lama masalah misi & log operasi alat (TIDAK menyentuh user/satker).
        // Log dihapus lebih dulu karena mereferensikan mission_issues.
        OperasiAlat::query()->delete();
        MissionIssue::query()->delete();

        // Pembuat/operator = user dengan satker (operator & admin di satker nyata).
        $creators = User::query()
            ->whereIn('role', ['operator', 'admin'])
            ->whereNotNull('satker_id')
            ->get();

        if ($creators->isEmpty()) {
            $this->command->warn('MissionIssueSeeder dilewati: tidak ada operator/admin dengan satker_id.');

            return;
        }

        $scenarioIndex = 0;

        foreach ($creators as $creator) {
            // Tiap creator dapat 2 skenario (dirotasi) agar setiap satker terisi.
            for ($i = 0; $i < 2; $i++) {
                $scenario = $this->scenarios[$scenarioIndex % count($this->scenarios)];
                $scenarioIndex++;

                $issue = MissionIssue::factory()
                    ->forCreator($creator)
                    ->create([
                        'jenis' => $scenario['jenis'],
                        'lokasi' => $scenario['lokasi'],
                        'deskripsi' => $scenario['deskripsi'],
                        'tindakan' => $scenario['tindakan'],
                        'status' => $scenario['status'],
                    ]);

                foreach ($scenario['logs'] as $log) {
                    OperasiAlat::factory()
                        ->forOperator($creator)
                        ->forMissionIssue($issue)
                        ->create([
                            'jenis_alat' => $log['jenis_alat'],
                            'tujuan_operasi' => $log['tujuan_operasi'],
                            'hasil' => $log['hasil'],
                            'lokasi' => $scenario['lokasi'],
                        ]);
                }
            }

            // Beberapa log mandiri (tanpa keterkaitan masalah misi) untuk variasi.
            OperasiAlat::factory()
                ->count(2)
                ->forOperator($creator)
                ->create();
        }

        $this->command->info(sprintf(
            'MissionIssueSeeder selesai: %d masalah misi, %d log operasi alat (%d tertaut).',
            MissionIssue::count(),
            OperasiAlat::count(),
            OperasiAlat::whereNotNull('mission_issue_id')->count(),
        ));
    }
}
