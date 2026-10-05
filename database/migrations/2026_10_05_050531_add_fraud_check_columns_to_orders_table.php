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
            $table->unsignedTinyInteger('fraud_score')->default(0)->after('notes');
            $table->string('fraud_risk_level', 20)->default('low')->index()->after('fraud_score');
            $table->string('fraud_status', 30)->default('clean')->index()->after('fraud_risk_level');
            $table->json('fraud_flags')->nullable()->after('fraud_status');
            $table->decimal('advance_delivery_charge', 10, 2)->nullable()->after('fraud_flags');
            $table->string('advance_payment_method', 30)->nullable()->after('advance_delivery_charge');
            $table->string('advance_transaction_id', 100)->nullable()->after('advance_payment_method');
            $table->string('advance_payment_status', 30)->default('none')->after('advance_transaction_id');
            $table->timestamp('fraud_checked_at')->nullable()->after('advance_payment_status');
            $table->text('fraud_notes')->nullable()->after('fraud_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'fraud_score',
                'fraud_risk_level',
                'fraud_status',
                'fraud_flags',
                'advance_delivery_charge',
                'advance_payment_method',
                'advance_transaction_id',
                'advance_payment_status',
                'fraud_checked_at',
                'fraud_notes',
            ]);
        });
    }
};
