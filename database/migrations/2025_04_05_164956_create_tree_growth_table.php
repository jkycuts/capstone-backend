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
        Schema::create('tree_growth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plantation_id')->constrained('plantation')->onDelete('cascade');  // linking to the plantation
            $table->string('species');
            $table->float('dbh'); // diameter at breast height (in cm)
            $table->float('height');
            $table->string('geotag_photos')->nullable();   // to store geotagged photo information
            $table->float('longitude');
            $table->float('latitude');
            $table->timestamps();

            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tree_growth');
    }
};
