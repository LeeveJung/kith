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
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('breeder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('species_id')->constrained('species')->restrictOnDelete();
            $table->foreignId('breed_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('color_id')->nullable()->constrained()->nullOnDelete();
            // The foreign key to litters is added in the litters migration (circular reference).
            $table->unsignedBigInteger('litter_id')->nullable()->index();
            $table->string('name');
            $table->string('call_name')->nullable();
            $table->string('sex')->default('unknown');
            $table->string('origin')->default('bred');
            $table->date('born_on')->nullable();
            $table->string('born_on_precision')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['breeder_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
