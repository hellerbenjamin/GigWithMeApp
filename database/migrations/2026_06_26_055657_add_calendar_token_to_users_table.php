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
            // Durable secret that authorizes the iCal feed URL. Independent from
            // push_token so it can be regenerated without affecting push opt-ins.
            $table->string('calendar_token', 64)->nullable()->unique()->after('reminder_days');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('calendar_token');
        });
    }
};
