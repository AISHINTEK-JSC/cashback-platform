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
        Schema::create('platforms', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('code', 20)->unique('uq_platform_code')->comment('shopee, tiktok, lazada');
            $table->string('name', 50);
            $table->string('logo')->nullable();
            $table->text('api_config')->nullable()->comment('JSON mã hoá: app_id, secret, affiliate_id');
            $table->unsignedSmallInteger('lock_days')->comment('số ngày chờ sàn chốt đơn');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('platform_id');
            $table->string('category_code', 50)->nullable()->comment('null = áp dụng mặc định');
            $table->decimal('user_rate', 5, 2)->comment('% hoa hồng trả cho người mua');
            $table->decimal('f1_rate', 5, 2)->comment('% cho người mời trực tiếp');
            $table->decimal('f2_rate', 5, 2)->comment('% cho tầng 2');
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable()->comment('null = đang áp dụng');
            $table->timestamps();

            $table->index(['platform_id', 'category_code', 'effective_from'], 'idx_rule_lookup');
            $table->foreign('platform_id')->references('id')->on('platforms');
        });

        Schema::create('tracking_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->unsignedSmallInteger('platform_id');
            $table->string('sub_id', 64)->unique('uq_sub_id')->comment('gửi kèm link affiliate');
            $table->text('original_url');
            $table->string('shop_id', 50)->nullable();
            $table->string('product_id', 50)->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_image', 500)->nullable();
            $table->bigInteger('price')->nullable()->comment('VND');
            $table->decimal('estimated_rate', 5, 2)->nullable()->comment('% hoàn dự kiến hiển thị cho user');
            $table->text('affiliate_url')->comment('link do API sàn trả về');
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'idx_link_user');
            $table->foreign('platform_id')->references('id')->on('platforms');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('platform_id');
            $table->string('platform_order_id', 64)->comment('mã đơn trên sàn');
            $table->foreignId('user_id')->nullable()->constrained()->comment('null nếu chưa khớp được sub_id');
            $table->foreignId('tracking_link_id')->nullable()->constrained();
            $table->string('sub_id', 64)->nullable();
            $table->bigInteger('order_amount')->comment('giá trị đơn');
            $table->bigInteger('commission_amount')->comment('hoa hồng sàn trả cho mình');
            $table->bigInteger('cashback_amount')->comment('phần của người mua, chốt tại thời điểm tính');
            $table->foreignId('commission_rule_id')->nullable()->constrained()->comment('snapshot tỷ lệ');
            $table->enum('status', ['pending', 'confirmed', 'approved', 'cancelled', 'paid'])->default('pending');
            $table->string('platform_status', 50)->nullable()->comment('trạng thái gốc từ sàn');
            $table->dateTime('purchased_at');
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->json('raw_payload')->nullable()->comment('dữ liệu gốc để đối soát');
            $table->dateTime('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['platform_id', 'platform_order_id'], 'uq_platform_order');
            $table->index(['user_id', 'status', 'purchased_at'], 'idx_order_user');
            $table->index(['status', 'approved_at'], 'idx_order_status');
            $table->foreign('platform_id')->references('id')->on('platforms');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('item_id', 64);
            $table->string('item_name')->nullable();
            $table->string('category_code', 50)->nullable();
            $table->unsignedInteger('quantity');
            $table->bigInteger('price');
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->bigInteger('commission_amount');
            $table->string('status', 30)->nullable()->comment('item có thể bị hoàn riêng');
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->enum('source', ['sync', 'admin', 'system'])->default('sync');
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('order_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('user_id')->constrained()->comment('người nhận');
            $table->enum('type', ['self', 'f1', 'f2'])->default('self');
            $table->decimal('rate', 5, 2)->comment('% áp dụng');
            $table->bigInteger('amount')->comment('VND');
            $table->enum('status', ['pending', 'available', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->unique(['order_id', 'user_id', 'type'], 'uq_reward');
            $table->index(['user_id', 'status'], 'idx_reward_user');
        });

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('platform_id');
            $table->dateTime('window_from')->comment('khoảng thời gian kéo');
            $table->dateTime('window_to');
            $table->unsignedInteger('fetched')->default(0);
            $table->unsignedInteger('inserted')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->enum('status', ['running', 'success', 'failed'])->default('running');
            $table->text('error')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();

            $table->index(['platform_id', 'started_at'], 'idx_sync');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('order_rewards');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('tracking_links');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('platforms');
    }
};
