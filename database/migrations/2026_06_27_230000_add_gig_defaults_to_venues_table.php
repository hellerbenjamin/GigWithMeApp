<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->time('default_load_in_time')->nullable()->after('notes');
            $table->time('default_soundcheck_time')->nullable()->after('default_load_in_time');
            $table->time('default_doors_time')->nullable()->after('default_soundcheck_time');
            $table->time('default_start_time')->nullable()->after('default_doors_time');
            $table->time('default_end_time')->nullable()->after('default_start_time');
            $table->text('default_notes')->nullable()->after('default_end_time');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn([
                'default_load_in_time',
                'default_soundcheck_time',
                'default_doors_time',
                'default_start_time',
                'default_end_time',
                'default_notes',
            ]);
        });
    }
};
