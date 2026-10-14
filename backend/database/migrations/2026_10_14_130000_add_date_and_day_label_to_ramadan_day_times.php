<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The admin can also set a day's date and its Telugu day name (రోజ్) by hand.
     */
    public function up(): void
    {
        Schema::table('ramadan_day_times', function (Blueprint $table) {
            $table->date('date')->nullable()->after('day');
            $table->string('day_label', 40)->nullable()->after('date');
        });
    }

    public function down(): void
    {
        Schema::table('ramadan_day_times', function (Blueprint $table) {
            $table->dropColumn(['date', 'day_label']);
        });
    }
};
