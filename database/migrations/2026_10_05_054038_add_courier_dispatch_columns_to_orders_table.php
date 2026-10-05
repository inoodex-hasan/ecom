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
            $table->string('courier_provider', 30)->nullable()->after('fraud_notes');
            $table->string('courier_consignment_id', 100)->nullable()->index()->after('courier_provider');
            $table->string('courier_tracking_code', 100)->nullable()->index()->after('courier_consignment_id');
            $table->string('courier_status', 50)->nullable()->after('courier_tracking_code');
            $table->decimal('courier_cod_amount', 10, 2)->nullable()->after('courier_status');
            $table->timestamp('courier_dispatched_at')->nullable()->after('courier_cod_amount');
            $table->json('courier_payload')->nullable()->after('courier_dispatched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'courier_provider',
                'courier_consignment_id',
                'courier_tracking_code',
                'courier_status',
                'courier_cod_amount',
                'courier_dispatched_at',
                'courier_payload',
            ]);
        });
    }
};
