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
        Schema::create('persalinan', function (Blueprint $table) {
            $table->string('id', 25)->primary();
            $table->timestamp('tanggal_jam_rawat')->nullable();
            $table->timestamp('tanggal_jam_mules')->nullable();
            $table->boolean('ketuban_pecah')->nullable()->default(false);
            $table->decimal('pasien_no_reg')->nullable();
            $table->string('partograf_id', 25)->nullable();
            $table->string('status', 20)->default('tidak_aktif');
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
