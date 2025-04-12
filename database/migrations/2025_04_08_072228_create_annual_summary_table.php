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
            $table->year('year');
            $table->decimal('annual_carbon_emission', 12, 4)->default(0);
            $table->decimal('annual_carbon_sequestration', 12, 4)->default(0);
            $table->decimal('carbon_neutrality_variance', 12, 4)->default(0);
            $table->decimal('ghg_percentage_national', 6, 4)->default(0);
            
            $table->foreignID('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->timestamps();
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
