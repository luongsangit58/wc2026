<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('club')->nullable()->after('photo_url');         // club name, e.g. "Real Madrid"
            $table->string('club_nat', 8)->nullable()->after('club');       // club country code, e.g. "ESP"
            $table->unsignedSmallInteger('caps')->nullable()->after('club_nat');       // international appearances
            $table->unsignedSmallInteger('intl_goals')->nullable()->after('caps');     // international (career) goals
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['club', 'club_nat', 'caps', 'intl_goals']);
        });
    }
};
