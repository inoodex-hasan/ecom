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
        Schema::create('fraud_blacklists', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index(); // 'phone', 'email', 'ip', 'address'
            $table->string('value')->index();
            $table->string('list_type', 20)->default('blacklist')->index(); // 'blacklist', 'whitelist'
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fraud_blacklists');
    }
};
