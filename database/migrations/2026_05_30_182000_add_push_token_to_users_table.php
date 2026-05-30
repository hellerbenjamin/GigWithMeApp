<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A durable, unguessable per-member token — the member's standing identity
     * for push, independent of any one gig. Unlike the per-gig RSVP token it
     * doesn't expire when a poll settles, so it can carry a member into an
     * installed iOS PWA (whose only inbound identity channel is the manifest
     * start_url) without ever asking them to log in.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('push_token', 64)->nullable()->unique()->after('phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('push_token');
        });
    }
};
