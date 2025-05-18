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
        Schema::table('scope3_emission', function (Blueprint $table) {
            $table->dropColumn('gas_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scope3_emission', function (Blueprint $table) {
             $table->enum('gas_type', ['co2', 'ch4', 'n2o'])->nullable();
        });
    }
};
