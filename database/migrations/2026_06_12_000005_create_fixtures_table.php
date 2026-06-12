<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('num')->nullable();   // official match number (1..104)
            $table->string('stage');                           // group|round_of_32|round_of_16|quarter_final|semi_final|third_place|final
            $table->string('round_label');                     // "Matchday 1", "Round of 32", "Final"
            $table->unsignedTinyInteger('matchday')->nullable();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();

            $table->date('match_date');
            $table->string('time_label')->nullable();          // raw "13:00 UTC-6"
            $table->dateTime('kickoff_at')->nullable();        // normalised to UTC

            $table->foreignId('team1_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('team2_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('team1_placeholder')->nullable();   // "2A", "W74", "L101" for knockouts
            $table->string('team2_placeholder')->nullable();

            $table->string('status')->default('scheduled');    // scheduled|live|finished
            $table->unsignedTinyInteger('team1_score')->nullable();
            $table->unsignedTinyInteger('team2_score')->nullable();

            $table->timestamps();

            $table->index(['stage', 'match_date']);
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
