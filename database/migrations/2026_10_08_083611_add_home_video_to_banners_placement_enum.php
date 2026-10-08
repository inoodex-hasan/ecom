<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `banners` MODIFY COLUMN `placement` ENUM('hero_slider', 'home_banner', 'category_banner', 'popup_promo', 'home_video') NOT NULL DEFAULT 'hero_slider'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `banners` MODIFY COLUMN `placement` ENUM('hero_slider', 'home_banner', 'category_banner', 'popup_promo') NOT NULL DEFAULT 'hero_slider'");
        }
    }
};
