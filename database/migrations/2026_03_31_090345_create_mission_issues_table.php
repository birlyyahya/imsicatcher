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
        Schema::create('mission_issues', function (Blueprint $table) {
            $table->id();
            $table->timestamp('tanggal')->useCurrent();
            $table->string('lokasi');
            $table->string('jenis');
            $table->text('deskripsi');
            $table->string('pelapor');
            $table->text('pihak_terlibat')->nullable();
            $table->string('satker');
            $table->text('tindakan')->nullable();
            $table->string('status')->default('baru');
            $table->string('foto_bukti')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tanggal');
            $table->index('status');
            $table->index('jenis');
            $table->index('satker');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_issues');
    }
};
