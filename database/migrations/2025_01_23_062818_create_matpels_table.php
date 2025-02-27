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
        Schema::create('matpels', function (Blueprint $table) {
            $table->string('kode_matpel', 20)->primary();
            $table->string('matpel');
            $table->enum('kelompok', ['adaptif', 'normatif', 'kejuruan', 'pilihan']);
            $table->string('matpels_kode')->nullable();
            $table->foreign('matpels_kode')->references('kode_matpel')->on('matpels');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matpels');
    }
};
