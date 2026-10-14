<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sehri/iftar times the admin has set by hand for a day of Ramadan (e.g. from the
     * masjid's printed timetable). Keyed by roza number, so they follow the day if the
     * start date changes. Empty means "use the calculated time".
     */
    public function up(): void
    {
        Schema::create('ramadan_day_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('hijri_year');
            $table->unsignedTinyInteger('day');
            $table->time('sehri')->nullable();
            $table->time('iftar')->nullable();
            $table->timestamps();
            $table->unique(['masjid_id', 'hijri_year', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ramadan_day_times');
    }
};
