<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SMS consent + invite columns. US A2P 10DLC requires that the person who
     * owns a number opts in themselves, so an admin adding a member no longer
     * makes the number textable. Instead the member accepts an emailed invite
     * (carried by invite_token) and opts in on the acceptance page; we record
     * when they consented and the exact wording they agreed to. sms_opted_out_at
     * is set on STOP. SMS only sends when sms_consent_at is set and
     * sms_opted_out_at is null. See docs/sms-consent-invite-flow.md.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('invite_token', 64)->nullable()->unique()->after('push_token');
            $table->timestamp('sms_consent_at')->nullable()->after('invite_token');
            $table->text('sms_consent_text')->nullable()->after('sms_consent_at');
            $table->timestamp('sms_opted_out_at')->nullable()->after('sms_consent_text');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['invite_token', 'sms_consent_at', 'sms_consent_text', 'sms_opted_out_at']);
        });
    }
};
