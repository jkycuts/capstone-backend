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
        Schema::create('ghg_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('source_type'); // e.g., "fuel_combustion", "waste"
            
            $table->string('source_detail'); // e.g., "diesel", "short-haul"
            $table->float('amount'); // quantity used
            $table->string('unit'); // L, kWh, etc.
            $table->float('co2_emission');
            $table->float('ch4_emission')->default(0);
            $table->float('n2o_emission')->default(0);
            $table->float('total_emission');
            $table->year('year_recorded');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ghg_inventories');
    }
};
