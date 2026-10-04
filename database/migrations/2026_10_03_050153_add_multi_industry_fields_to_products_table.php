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
            $table->string('product_type')->default('standard')->after('brand_id');
            $table->string('unit')->default('piece')->after('price');
            $table->decimal('min_order_quantity', 10, 2)->default(1)->after('unit');
            $table->decimal('quantity_step', 10, 2)->default(1)->after('min_order_quantity');
            $table->decimal('unit_coverage_value', 10, 2)->nullable()->after('quantity_step');
            $table->decimal('weight', 10, 3)->nullable()->after('low_stock_threshold');
            $table->string('weight_unit')->default('kg')->after('weight');
            $table->decimal('length', 10, 2)->nullable()->after('weight_unit');
            $table->decimal('width', 10, 2)->nullable()->after('length');
            $table->decimal('height', 10, 2)->nullable()->after('width');
            $table->string('dimension_unit')->default('cm')->after('height');
            $table->json('attributes')->nullable()->after('description');
            $table->boolean('has_variants')->default(false)->after('attributes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'product_type',
                'unit',
                'min_order_quantity',
                'quantity_step',
                'unit_coverage_value',
                'weight',
                'weight_unit',
                'length',
                'width',
                'height',
                'dimension_unit',
                'attributes',
                'has_variants',
            ]);
        });
    }
};
