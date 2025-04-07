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
        Schema::create('ghg_emission', function (Blueprint $table) {
            $table->id();

            $table->integer('year');
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);

            // Fuel Consumption - Scope 1
            $table->string('fuel_source');
            $table->enum('fuel_type', ['diesel', 'biodiesel', 'ethanol', 'gasoline'])->nullable();
            $table->decimal('fuel_liters_used', 10, 2)->nullable();

            // Purchased Electricity - Scope 2
            $table->decimal('electricity_kwh', 10, 2)->nullable();

            // Business Travel - Scope 3
            $table->enum('travel_category', ['short', 'medium', 'long', 'unknown'])->nullable();
            $table->integer('travel_distance_miles')->nullable();
            $table->integer('travel_number_of_trips')->nullable();

            $table->foreignId('company_id')->constrained()->onDelete('cascade');  // Set up foreign key constraint

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ghg_emission');
    }
};
