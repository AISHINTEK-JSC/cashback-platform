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
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('method', 20)->default('bank')->after('user_id')->comment('bank, phone_topup, phone_card');
            $table->string('bank_name', 100)->nullable()->comment('snapshot, chỉ có khi rút về ngân hàng/ví')->change();
            $table->string('account_number', 30)->nullable()->comment('snapshot')->change();
            $table->string('account_name', 100)->nullable()->comment('snapshot')->change();
            $table->string('phone_carrier', 20)->nullable()->after('account_name')->comment('nhà mạng khi nạp điện thoại / thẻ cào');
            $table->string('phone_number', 20)->nullable()->after('phone_carrier')->comment('số được nạp tiền');
            $table->string('card_serial', 50)->nullable()->after('bank_txn_ref');
            $table->text('card_code')->nullable()->after('card_serial')->comment('mã thẻ cào, mã hoá');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['method', 'phone_carrier', 'phone_number', 'card_serial', 'card_code']);
        });
    }
};
