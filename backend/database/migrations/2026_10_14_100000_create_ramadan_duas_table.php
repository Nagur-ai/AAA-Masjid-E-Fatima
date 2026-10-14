<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Duas shown with the Ramadan calendar, managed by the masjid admin.
        Schema::create('ramadan_duas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16)->default('other'); // sehri, iftar or other (sets the colour)
            $table->string('title', 120);
            $table->text('body');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['masjid_id', 'sort_order']);
        });

        // The masjid's printed timetable, shown at the top of the Ramadan pages.
        Schema::table('masjids', function (Blueprint $table) {
            $table->string('ramadan_image_path')->nullable()->after('bank_details');
        });

        // Existing masjids get the default duas.
        $now = now();
        foreach (DB::table('masjids')->pluck('id') as $masjidId) {
            foreach (config('ramadan.default_duas') as $i => $dua) {
                DB::table('ramadan_duas')->insert([
                    ...$dua, 'masjid_id' => $masjidId, 'sort_order' => $i, 'is_active' => true,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('masjids', fn (Blueprint $table) => $table->dropColumn('ramadan_image_path'));
        Schema::dropIfExists('ramadan_duas');
    }
};
