<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The venue list is always scoped to a band and sorted by name by
        // default; Postgres doesn't auto-index the band_id FK, so back the
        // common listing path with a composite index. (Free-text ILIKE search
        // can't use this — add a pg_trgm GIN index if search gets slow at scale.)
        Schema::table('venues', static function (Blueprint $table) {
            $table->index(['band_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('venues', static function (Blueprint $table) {
            $table->dropIndex(['band_id', 'name']);
        });
    }
};
