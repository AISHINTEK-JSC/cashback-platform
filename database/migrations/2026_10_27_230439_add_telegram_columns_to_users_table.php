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
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('telegram_chat_id')->nullable()->unique('uq_users_telegram')->comment('chat riêng với bot, null = chưa liên kết');
            $table->string('telegram_username', 64)->nullable();
            $table->dateTime('telegram_linked_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('uq_users_telegram');
            $table->dropColumn(['telegram_chat_id', 'telegram_username', 'telegram_linked_at']);
        });
    }
};
