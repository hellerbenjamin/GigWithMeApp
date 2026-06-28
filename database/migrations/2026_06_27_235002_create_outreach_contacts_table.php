<?php

use App\Enums\OutreachContactMethodEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outreach_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_outreach_id')->constrained('venue_outreach')->cascadeOnDelete();
            $table->date('occurred_on');
            $table->string('method')->default(OutreachContactMethodEnum::Email->value);
            $table->text('summary');
            $table->text('response')->nullable();
            $table->timestamps();

            $table->index(['venue_outreach_id', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_contacts');
    }
};
