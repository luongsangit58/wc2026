<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // stadium name, e.g. "Estadio Azteca"
            $table->string('city');                 // "Mexico City"
            $table->string('slug')->unique();       // derived from city
            $table->char('country_code', 2);        // us / ca / mx
            $table->unsignedInteger('capacity')->nullable();
            $table->string('timezone')->nullable(); // "UTC-6"
            $table->string('coords')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
