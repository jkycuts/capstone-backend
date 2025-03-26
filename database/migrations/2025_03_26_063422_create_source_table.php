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

                $table->id();
                $table->string('name'); // e.g., Mining Equipment, Vehicles
                $table->string('type'); // e.g., Fuel-based, Electricity-based
                $table->string('fuel_type')->nullable(); // Gasoline, Diesel (optional)
                $table->decimal('fuel_consumption', 10, 2)->default(0); // Fuel used (liters)
                $table->decimal('electricity_usage', 10, 2)->default(0); // Electricity used (kWh)
                $table->foreignId('companyID')->constrained('company')->onDelete('cascade'); // Connect to Company
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
