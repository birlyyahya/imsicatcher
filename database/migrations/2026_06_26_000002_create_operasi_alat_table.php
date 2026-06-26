<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('operasi_alat', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis_alat', ['stingray_interceptor', 'hhdf', 'drone', 'laptop_operasi']);
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('satker_id')->nullable()->constrained('satkers')->nullOnDelete();
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai')->nullable();
            $table->string('lokasi')->nullable();
            $table->text('tujuan_operasi');
            $table->enum('hasil', ['berhasil', 'gagal', 'sebagian'])->nullable();
            $table->string('foto_bukti')->nullable();
            $table->foreignId('mission_issue_id')->nullable()->constrained('mission_issues')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('jenis_alat');
            $table->index('waktu_mulai');
            $table->index('hasil');
            // Catatan: index untuk satker_id, operator_id & mission_issue_id sudah
            // dibuat otomatis oleh constrained() (foreign key), jadi tidak diulang
            // di sini untuk menghindari duplikasi index.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operasi_alat');
    }
};
