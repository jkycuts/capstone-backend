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
        Schema::table('ghg_emission', function (Blueprint $table) {
            $table->decimal('fuel_tco2', 10, 4)->nullable();
            $table->decimal('electricity_tco2', 10, 4)->nullable();
            $table->decimal('travel_tco2', 10, 4)->nullable();
            $table->decimal('total_tco2', 10, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ghg_emission', function (Blueprint $table) {
            //
        });
    }
};
