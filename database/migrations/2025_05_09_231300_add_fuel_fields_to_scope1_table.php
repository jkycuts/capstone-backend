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
            $table->enum('mode', ['monthly', 'quarterly'])->nullable();
            $table->string('month')->nullable();
            $table->string('fuel_type')->nullable();
            $table->double('fuel_liters_used')->nullable();
            $table->double('emission_factor')->nullable();
            $table->double('gwp')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scope1', function (Blueprint $table) {
            $table->dropColumn(['mode', 'month', 'fuel_type', 'fuel_liters_used', 'emission_factor', 'gwp']);
        });
    }
};
