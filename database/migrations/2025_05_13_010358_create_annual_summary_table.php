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

            $table->double('total_emission', 12, 3)->default(0); // TCO₂
            $table->double('total_sequestration', 12, 3)->default(0); // TCO₂
            $table->double('carbon_variance', 12, 3)->default(0); // Emissions - Sequestration
            $table->double('percent_country_contribution', 6, 3)->default(0); // %

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unique(['company_id', 'year']); // Prevent duplicate entries per year per company
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
