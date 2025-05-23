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
        Schema::create('capaian_pembelajarans', function (Blueprint $table) {
            $table->string('kode_cp', 20)->primary();
            $table->string('capaian');
            $table->string('kode_matpel', 20);
            $table->foreign('kode_matpel')->references('kode_matpel')->on('matpels');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capaian_pembelajarans');
    }
};
