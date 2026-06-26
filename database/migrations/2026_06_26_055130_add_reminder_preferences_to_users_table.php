<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Which channels the member wants reminders on. Capability-checked
            // at send time (push only fires if they have an active subscription).
            $table->json('reminder_channels')->default('["email"]')->after('push_token');
            // Days before the gig to send each reminder. Multiple values = multiple
            // reminders. Empty array opts out entirely.
            $table->json('reminder_days')->default('[7, 1]')->after('reminder_channels');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reminder_channels', 'reminder_days']);
        });
    }
};
