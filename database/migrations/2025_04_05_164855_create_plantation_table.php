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
        Schema::create('plantation', function (Blueprint $table) {
            $table->id();
            $table->float('area_planted');  // in hectares
            $table->integer('seedlings_planted');
            $table->integer('plantation_age');  // date when plantation started
            $table->date('date_recorded');  // date when plantation started
            $table->timestamps();

           
            $table->foreignId('company_id')->constrained()->onDelete('cascade');  // Set up foreign key constraint
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantation');
        
    }
};
