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
        Schema::table('partograf', function (Blueprint $table) {
            $table->foreign('persalinan_id', 'fk_partograf_persalinan')
                  ->references('id')
                  ->on('persalinan')
                  ->onUpdate('cascade')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partograf', function (Blueprint $table) {
            $table->dropForeign('fk_partograf_persalinan');
        });
    }
};
