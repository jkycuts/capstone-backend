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
            $table->date('plantation_age');  // date when plantation started
            $table->json('geotag_photos');   // to store geotagged photo information
            $table->timestamps();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');  // Set up foreign key constraint
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
