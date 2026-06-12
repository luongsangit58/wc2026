<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // "Mexico"
            $table->string('slug')->unique();             // "mexico"
            $table->string('name_normalised')->nullable();// "Korea Republic"
            $table->char('fifa_code', 3);                 // "MEX"
            $table->string('confederation')->nullable();  // "CONCACAF"
            $table->string('continent')->nullable();      // "North America"
            $table->string('flag_emoji', 16)->nullable(); // 🇲🇽
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
