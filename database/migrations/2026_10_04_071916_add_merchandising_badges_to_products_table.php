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
        Schema::table('products', function (Blueprint $table) {
            $table->string('badge_label', 50)->nullable()->after('is_featured');
            $table->boolean('is_hot')->default(false)->after('badge_label');
            $table->boolean('is_trending')->default(false)->after('is_hot');
            $table->boolean('is_new_arrival')->default(false)->after('is_trending');

            $table->index('is_hot');
            $table->index('is_trending');
            $table->index('is_new_arrival');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_hot']);
            $table->dropIndex(['is_trending']);
            $table->dropIndex(['is_new_arrival']);
            $table->dropColumn(['badge_label', 'is_hot', 'is_trending', 'is_new_arrival']);
        });
    }
};
