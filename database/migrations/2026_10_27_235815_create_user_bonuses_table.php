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
        Schema::create('user_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['first_order', 'onboarding']);
            $table->bigInteger('amount')->comment('VND đã cộng vào số dư khả dụng');
            $table->foreignId('order_id')->nullable()->constrained()->comment('đơn được thưởng (first_order)');
            $table->dateTime('revoked_at')->nullable()->comment('thu hồi khi đơn được thưởng bị huỷ');
            $table->timestamps();

            $table->unique(['user_id', 'type'], 'uq_user_bonus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bonuses');
    }
};
