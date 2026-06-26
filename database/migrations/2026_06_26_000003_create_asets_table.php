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
        Schema::create('asets', function (Blueprint $table) {
            $table->id();
            $table->string('nama_aset');
            $table->enum('kategori', ['server', 'laptop_operasi', 'drone', 'stingray_interceptor', 'hhdf', 'kendaraan']);
            $table->string('nomor_seri')->nullable()->unique();
            $table->enum('status', ['aktif', 'maintenance', 'rusak'])->default('aktif');
            $table->foreignId('satker_id')->nullable()->constrained('satkers')->nullOnDelete();
            $table->date('tanggal_pengadaan')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('kategori');
            $table->index('status');
            // Catatan: index untuk satker_id sudah dibuat otomatis oleh
            // constrained() (foreign key), jadi tidak diulang di sini.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asets');
    }
};
