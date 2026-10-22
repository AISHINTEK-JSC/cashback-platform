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
        Schema::create('wallets', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained();
            $table->bigInteger('pending_balance')->default(0)->comment('chờ sàn duyệt');
            $table->bigInteger('available_balance')->default(0)->comment('có thể rút');
            $table->bigInteger('frozen_balance')->default(0)->comment('đang trong yêu cầu rút');
            $table->bigInteger('total_earned')->default(0);
            $table->bigInteger('total_withdrawn')->default(0);
            $table->unsignedInteger('version')->default(0)->comment('optimistic lock');
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            // 'release' (pending -> available) và 'payout' (frozen -> đã chuyển khoản) bổ sung so với schema gốc
            // để mỗi bước di chuyển tiền có (type, bucket) riêng, giữ được khoá chống ghi trùng uq_wtx_idem.
            $table->enum('type', ['cashback', 'referral', 'checkin', 'bonus', 'withdraw', 'withdraw_refund', 'adjust', 'reversal', 'release', 'payout'])->default('cashback');
            $table->enum('bucket', ['pending', 'available', 'frozen'])->default('pending')->comment('cột số dư bị ảnh hưởng');
            $table->bigInteger('amount')->comment('có dấu: + cộng, - trừ');
            $table->bigInteger('balance_after')->comment('số dư bucket sau giao dịch');
            $table->string('reference_type', 50)->nullable()->comment('order_rewards, withdrawals, checkins...');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable()->comment('hiển thị ở Biến động số dư');
            $table->unsignedBigInteger('created_by')->nullable()->comment('admin nếu điều chỉnh tay');
            $table->timestamp('created_at')->nullable();

            $table->unique(['reference_type', 'reference_id', 'type', 'bucket'], 'uq_wtx_idem');
            $table->index(['user_id', 'created_at'], 'idx_wtx_user');
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique('uq_wd_code')->comment('mã hiển thị RT000123');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('bank_account_id')->nullable()->constrained('user_bank_accounts')->nullOnDelete();
            $table->string('bank_name', 100)->comment('snapshot');
            $table->string('account_number', 30)->comment('snapshot');
            $table->string('account_name', 100)->comment('snapshot');
            $table->bigInteger('amount');
            $table->bigInteger('fee')->default(0);
            $table->bigInteger('net_amount')->comment('amount - fee');
            $table->enum('status', ['pending', 'approved', 'processing', 'paid', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable()->comment('admin');
            $table->dateTime('reviewed_at')->nullable();
            $table->string('reject_reason')->nullable();
            $table->string('bank_txn_ref', 100)->nullable()->comment('mã giao dịch chuyển khoản');
            $table->dateTime('paid_at')->nullable();
            $table->string('request_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_wd_status');
        });

        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->date('checkin_date');
            $table->unsignedSmallInteger('streak')->comment('chuỗi ngày liên tiếp');
            $table->bigInteger('reward_amount');
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'checkin_date'], 'uq_checkin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkins');
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
