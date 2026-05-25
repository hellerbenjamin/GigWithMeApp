<?php

use App\Enums\GigResponseStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-member poll replies for poll-mode gigs. See docs/gig-booking-flow.md.
        Schema::create('gig_member_responses', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('gig_id')->constrained('gigs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('status')->default(GigResponseStatusEnum::Pending->value);
            $table->timestamp('responded_at')->nullable();
            // Which transport recorded the reply (web / sms / rcs …) — analytics.
            $table->string('channel')->nullable();
            $table->text('note')->nullable();
            // Unguessable per-response token: powers the magic link and, later,
            // the RCS button payload.
            $table->string('token', 64)->unique();

            $table->timestamps();

            // One reply row per member per gig.
            $table->unique(['gig_id', 'user_id']);
        });

        // When the poll first reaches "everyone replied" — used to confirm or to
        // notify admins exactly once, and to show the poll as closed.
        Schema::table('gigs', static function (Blueprint $table) {
            $table->timestamp('poll_closed_at')->nullable()->after('booking_mode');
        });
    }

    public function down(): void
    {
        Schema::table('gigs', static function (Blueprint $table) {
            $table->dropColumn('poll_closed_at');
        });

        Schema::dropIfExists('gig_member_responses');
    }
};
