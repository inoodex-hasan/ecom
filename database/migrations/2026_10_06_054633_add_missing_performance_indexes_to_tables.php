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
        Schema::table('orders', function (Blueprint $table) {
            $table->index('advance_payment_status');
            $table->index(['courier_provider', 'courier_status']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index('barcode');
        });

        Schema::table('fraud_blacklists', function (Blueprint $table) {
            $table->unique(['type', 'value', 'list_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fraud_blacklists', function (Blueprint $table) {
            $table->dropUnique(['type', 'value', 'list_type']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex(['barcode']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['courier_provider', 'courier_status']);
            $table->dropIndex(['advance_payment_status']);
        });
    }
};
