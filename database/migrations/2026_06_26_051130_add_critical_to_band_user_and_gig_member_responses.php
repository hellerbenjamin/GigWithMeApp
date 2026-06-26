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
        Schema::table('band_user', function (Blueprint $table) {
            $table->boolean('critical')->default(true)->after('role');
        });

        Schema::table('gig_member_responses', function (Blueprint $table) {
            $table->boolean('critical')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('band_user', function (Blueprint $table) {
            $table->dropColumn('critical');
        });

        Schema::table('gig_member_responses', function (Blueprint $table) {
            $table->dropColumn('critical');
        });
    }
};
