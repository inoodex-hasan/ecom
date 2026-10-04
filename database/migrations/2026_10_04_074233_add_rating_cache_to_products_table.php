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
            $table->decimal('rating_avg', 3, 2)->default(0.00)->after('is_featured');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');

            $table->index(['rating_avg', 'rating_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['rating_avg', 'rating_count']);
            $table->dropColumn(['rating_avg', 'rating_count']);
        });
    }
};
