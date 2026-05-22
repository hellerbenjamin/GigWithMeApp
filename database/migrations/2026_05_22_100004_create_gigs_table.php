<?php

use App\Enums\GigStatusEnum;
use App\Enums\GigTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gigs', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained('bands')->cascadeOnDelete();
            // Nullable + nullOnDelete: a gig may have a TBD venue, and removing
            // a venue must not delete gig history.
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();

            $table->string('type')->default(GigTypeEnum::Gig->value);
            $table->string('status')->default(GigStatusEnum::Pending->value);
            $table->string('name')->nullable();
            $table->date('date');

            // Gig-day call sheet (all optional).
            $table->time('load_in_time')->nullable();
            $table->time('soundcheck_time')->nullable();
            $table->time('doors_time')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->text('notes')->nullable();
            $table->decimal('fee', 10, 2)->nullable();
            $table->char('currency', 3)->default('USD');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gigs');
    }
};
