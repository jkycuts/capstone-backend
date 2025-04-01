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
        Schema::table('source_emissions', function (Blueprint $table) {
            $table->decimal('co2_emission', 15, 2)->nullable();
            $table->decimal('n2o_emission', 15, 2)->nullable();
            $table->decimal('electricity_emission', 15, 2)->nullable();
            $table->decimal('total_emission', 15, 2)->nullable();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
