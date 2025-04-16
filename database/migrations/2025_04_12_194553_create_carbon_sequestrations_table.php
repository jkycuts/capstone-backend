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
        Schema::create('carbon_sequestrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('plantation_id')->nullable()->constrained('plantation')->nullOnDelete();

            $table->float('dbh'); // cm
            $table->float('height'); // m
            $table->float('agb'); // Above Ground Biomass
            $table->float('bgb'); // Below Ground Biomass
            $table->float('carbon_content'); // biomass × 0.47
            $table->float('co2_sequestered'); // carbon × 3.67
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
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
        Schema::dropIfExists('carbon_sequestrations');
    }
};
