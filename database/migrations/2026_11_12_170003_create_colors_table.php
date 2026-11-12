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
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('species_id')->constrained('species')->cascadeOnDelete();
            // null = global entry, otherwise a custom entry of that breeder
            $table->foreignId('breeder_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('hex', 7);
            $table->boolean('is_dark')->default(false);
            $table->timestamps();

            $table->unique(['breeder_id', 'species_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};
