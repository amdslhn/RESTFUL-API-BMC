<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('persalinan', function (Blueprint $table) {
            $table->string('id', 25)->primary();
            $table->timestamp('tanggal_jam_rawat')->nullable();
            $table->timestamp('tanggal_jam_mules')->nullable();
            $table->boolean('ketuban_pecah')->default(false);
            $table->string('pasien_no_reg', 25)->nullable();
            $table->enum('status', ['aktif', 'tidak_aktif', 'selesai'])->default('tidak_aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persalinan');
    }
};
