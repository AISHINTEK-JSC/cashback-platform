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
        Schema::create('user_levels', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name', 50)->comment('Đồng, Bạc, Vàng...');
            $table->unsignedInteger('min_approved_orders')->comment('số đơn duyệt tối thiểu để lên hạng');
            $table->decimal('bonus_rate', 5, 2)->comment('% cộng thêm trên phần của user');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 191)->unique('uq_users_email');
            $table->string('phone', 20)->nullable()->unique('uq_users_phone');
            $table->string('password')->nullable()->comment('null nếu đăng nhập Google/Facebook');
            $table->string('avatar')->nullable();
            $table->string('referral_code', 20)->unique('uq_users_ref')->comment('mã mời của user');
            $table->foreignId('referred_by')->nullable()->comment('người mời trực tiếp (F1 của họ)')
                ->constrained('users');
            $table->unsignedSmallInteger('level_id')->nullable();
            $table->enum('role', ['member', 'admin', 'staff'])->default('member');
            $table->enum('status', ['active', 'locked', 'pending'])->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->text('two_factor_secret')->nullable()->comment('mã hoá');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('level_id')->references('id')->on('user_levels');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20)->comment('google, facebook');
            $table->string('provider_id', 191);
            $table->timestamps();

            $table->unique(['provider', 'provider_id'], 'uq_social');
        });

        Schema::create('user_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('bank_code', 20)->comment('VCB, TCB, MOMO...');
            $table->string('bank_name', 100);
            $table->string('account_number', 30);
            $table->string('account_name', 100)->comment('viết hoa không dấu');
            $table->boolean('is_default')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['bank_code', 'account_number'], 'idx_bank_acc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bank_accounts');
        Schema::dropIfExists('social_accounts');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_levels');
    }
};
