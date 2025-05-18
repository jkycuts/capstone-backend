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
        Schema::table('scope1', function (Blueprint $table) {
            $table->double('co2_emission_factor')->nullable();
            $table->double('ch4_emission_factor')->nullable();
            $table->double('n2o_emission_factor')->nullable();

            $table->double('co2_gwp')->nullable();
            $table->double('ch4_gwp')->nullable();
            $table->double('n2o_gwp')->nullable();

            $table->double('emission_co2e')->nullable();
            $table->double('emission_ch4e')->nullable();
            $table->double('emission_n2oe')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scope1', function (Blueprint $table) {
            //
        });
    }
};
