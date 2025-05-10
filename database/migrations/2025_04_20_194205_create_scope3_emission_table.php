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
        Schema::create('scope3_emission', function (Blueprint $table) {
            $table->id();
            $table->enum('mode', ['monthly', 'quarterly'])->nullable();
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4'])->nullable();
            $table->string('month')->nullable();
            $table->year('year');
            $table->enum('travel_type', ['short', 'medium', 'long'])->nullable();
            $table->integer('travel_distance_miles')->nullable();
            $table->double('emission_factor')->nullable();
            $table->double('gwp')->nullable();
            $table->decimal('emission_tco2e', 12, 4);
            $table->timestamps();
        
            $table->foreignID('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scope3_emission');
    }
};
