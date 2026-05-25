<?php

use App\Enums\GigBookingModeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How each gig gets confirmed (auto vs. poll). See docs/gig-booking-flow.md.
        Schema::table('gigs', static function (Blueprint $table) {
            $table->string('booking_mode')
                ->default(GigBookingModeEnum::Auto->value)
                ->after('status');
        });

        // The band's default, used only to pre-select the create-gig form.
        Schema::table('bands', static function (Blueprint $table) {
            $table->string('default_booking_mode')
                ->default(GigBookingModeEnum::Auto->value)
                ->after('default_currency');
        });
    }

    public function down(): void
    {
        Schema::table('gigs', static function (Blueprint $table) {
            $table->dropColumn('booking_mode');
        });

        Schema::table('bands', static function (Blueprint $table) {
            $table->dropColumn('default_booking_mode');
        });
    }
};
