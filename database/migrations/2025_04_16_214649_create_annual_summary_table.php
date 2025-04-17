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
        Schema::create('annual_summary', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->year('year');
            
            // Emissions
            $table->decimal('fuel_tco2', 10, 3)->default(0);
            $table->decimal('electricity_tco2', 10, 3)->default(0);
            $table->decimal('travel_tco2', 10, 3)->default(0);
            $table->decimal('total_tco2', 10, 3)->default(0);
        
            // Sequestration
            $table->decimal('carbon_sequestered_tco2', 10, 3)->default(0);
        
            // Derived metrics
            $table->decimal('carbon_neutrality_variance', 10, 3)->default(0);
            $table->decimal('ghg_country_percent', 5, 2)->default(0.00);
        
            $table->timestamps();
        
            $table->unique(['company_id', 'year']);
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_summary');
    }
};
