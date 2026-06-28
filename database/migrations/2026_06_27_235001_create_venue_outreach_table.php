<?php

use App\Enums\OutreachPriorityEnum;
use App\Enums\OutreachStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_outreach', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default(OutreachStatusEnum::Targeting->value);
            $table->string('priority')->default(OutreachPriorityEnum::Medium->value);
            $table->date('follow_up_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // One row per venue per season.
            $table->unique(['booking_season_id', 'venue_id']);
            $table->index(['booking_season_id', 'status']);
            $table->index('follow_up_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_outreach');
    }
};
