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
        Schema::create('source', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID
            $table->string('name'); // Name of the source
            $table->foreignId('companyID')->constrained('company')->onDelete('cascade'); // Foreign key

            // Emission-related fields
            $table->string('fuel_type')->nullable(); // Gasoline/Diesel
            $table->decimal('fuel_consumption', 10, 2)->default(0.00); // Liters
            $table->decimal('electricity_usage', 10, 2)->default(0.00); // kWh

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source');
    }
};
