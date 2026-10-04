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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->json('photos')->nullable();
            $table->string('reviewer_name');
            $table->string('reviewer_email')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'spam'])->default('pending');
            $table->boolean('is_verified_purchase')->default(false);
            $table->text('merchant_reply')->nullable();
            $table->timestamp('merchant_replied_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['status', 'rating']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
