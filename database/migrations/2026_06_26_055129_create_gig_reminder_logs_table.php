<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gig_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gig_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('days_before');
            $table->timestamp('sent_at');
            // One reminder per (gig, member, timing slot) — prevents re-sends.
            $table->unique(['gig_id', 'user_id', 'days_before']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gig_reminder_logs');
    }
};
