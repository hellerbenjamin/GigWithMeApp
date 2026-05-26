<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remembers the band a user was last working in, so the active band
        // survives a session reset instead of silently re-defaulting to the
        // alphabetically-first band. Resolved by BandSessionService.
        Schema::table('users', static function (Blueprint $table) {
            $table->foreignId('last_active_band_id')
                ->nullable()
                ->after('timezone')
                ->constrained('bands')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_active_band_id');
        });
    }
};
