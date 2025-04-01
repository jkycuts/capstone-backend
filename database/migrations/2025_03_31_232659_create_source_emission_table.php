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
        Schema::create('source_emissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('sources')->onDelete('cascade');
            $table->integer('year'); // Stores year of the data
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']); // Stores quarterly records
            $table->decimal('fuel_consumption', 10, 4);
            $table->decimal('electricity_usage', 10, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_emissions');
    }
};
