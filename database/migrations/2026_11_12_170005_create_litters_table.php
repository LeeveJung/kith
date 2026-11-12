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
        Schema::create('litters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('breeder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('species_id')->constrained('species')->restrictOnDelete();
            $table->string('letter', 8)->nullable();
            $table->foreignId('mother_id')->constrained('animals')->restrictOnDelete();
            $table->foreignId('father_id')->constrained('animals')->restrictOnDelete();
            $table->date('born_on')->nullable();
            $table->string('born_on_precision')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['breeder_id', 'species_id', 'letter']);
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->foreign('litter_id')->references('id')->on('litters')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropForeign(['litter_id']);
        });

        Schema::dropIfExists('litters');
    }
};
