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
        Schema::create('tree_growth_simulation', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->float('dbh');
            $table->float('height');
            $table->float('agb');
            $table->float('bgb');
            $table->float('carbon');
            $table->float('co2_sequestration');
            $table->timestamps();

            $table->foreignID('tree_growth_id')->references('id')->on('tree_growth')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tree_growth_simulation');
    }
};
