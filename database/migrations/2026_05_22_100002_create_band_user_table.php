<?php

use App\Enums\BandUserRoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('band_user', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('band_id')->constrained('bands')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default(BandUserRoleEnum::Member->value);
            $table->timestamps();

            $table->unique(['band_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('band_user');
    }
};
