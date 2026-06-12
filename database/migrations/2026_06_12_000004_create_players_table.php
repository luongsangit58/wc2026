<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedSmallInteger('number')->nullable();
            $table->string('position', 4)->nullable();    // GK / DF / MF / FW
            $table->string('name');
            $table->date('date_of_birth')->nullable();
            // live tournament stats (0 until matches are played — mirrors source site)
            $table->unsignedInteger('goals')->default(0);
            $table->unsignedInteger('assists')->default(0);
            $table->decimal('rating', 3, 1)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
