<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom `satker` (string nama) tetap dipertahankan agar kompatibel dgn
            // MissionIssue & manajemen user; `satker_id` ditambahkan untuk relasi FK.
            $table->foreignId('satker_id')
                ->nullable()
                ->after('satker')
                ->constrained('satkers')
                ->nullOnDelete();
        });

        // Backfill: cocokkan users.satker (nama) ke satkers.id. Driver-agnostic.
        foreach (DB::table('satkers')->get() as $satker) {
            DB::table('users')
                ->where('satker', $satker->nama)
                ->update(['satker_id' => $satker->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('satker_id');
        });
    }
};
