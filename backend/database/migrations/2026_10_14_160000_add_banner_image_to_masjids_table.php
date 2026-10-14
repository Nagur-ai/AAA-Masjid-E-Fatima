<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A photo of the masjid behind its name on the public home page, and which part of it to show.
     */
    public function up(): void
    {
        Schema::table('masjids', function (Blueprint $table) {
            $table->string('banner_image_path')->nullable()->after('ramadan_image_path');
            $table->string('banner_position', 10)->default('center')->after('banner_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('masjids', fn (Blueprint $table) => $table->dropColumn(['banner_image_path', 'banner_position']));
    }
};
