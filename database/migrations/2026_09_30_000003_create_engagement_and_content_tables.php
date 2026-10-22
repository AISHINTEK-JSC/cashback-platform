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
        Schema::create('saved_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->unsignedSmallInteger('platform_id');
            $table->string('product_id', 50);
            $table->string('name');
            $table->string('image', 500)->nullable();
            $table->bigInteger('price')->nullable();
            $table->text('url');
            $table->timestamps();

            $table->unique(['user_id', 'platform_id', 'product_id'], 'uq_saved');
            $table->foreign('platform_id')->references('id')->on('platforms');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 191);
            $table->string('notifiable_type', 191);
            $table->unsignedBigInteger('notifiable_id');
            $table->json('data')->comment('tiêu đề, nội dung, link');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'idx_notif');
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['missing_order', 'wrong_amount', 'withdraw', 'other'])->default('missing_order');
            $table->unsignedSmallInteger('platform_id')->nullable();
            $table->string('platform_order_id', 64)->nullable()->comment('mã đơn user khai');
            $table->string('subject');
            $table->enum('status', ['open', 'answered', 'closed'])->default('open');
            $table->unsignedBigInteger('assigned_to')->nullable()->comment('admin');
            $table->timestamps();

            $table->index('status', 'idx_ticket_status');
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->comment('người gửi');
            $table->text('message');
            $table->json('attachments')->nullable()->comment('đường dẫn S3');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('platform_id');
            $table->string('code', 50);
            $table->string('title');
            $table->enum('discount_type', ['percent', 'fixed', 'freeship'])->default('percent');
            $table->bigInteger('discount_value');
            $table->bigInteger('min_order')->default(0);
            $table->bigInteger('max_discount')->nullable();
            $table->string('url', 500)->nullable()->comment('link lấy mã');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'expires_at'], 'idx_voucher_active');
            $table->foreign('platform_id')->references('id')->on('platforms');
        });

        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique('uq_pc_slug');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('post_categories')->nullOnDelete();
            $table->foreignId('author_id')->constrained('users');
            $table->string('title');
            $table->string('slug')->unique('uq_post_slug');
            $table->string('excerpt', 500)->nullable();
            $table->longText('content');
            $table->string('thumbnail')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at'], 'idx_post_pub');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 50)->comment('login, bank.update, withdraw.create...');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_hash', 64)->nullable()->comment('fingerprint để phát hiện multi-account');
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at'], 'idx_al_user');
            $table->index('device_hash', 'idx_al_device');
        });

        Schema::create('fraud_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('reason', ['shared_bank', 'shared_device', 'self_referral', 'high_cancel_rate', 'other'])->default('shared_bank');
            $table->unsignedTinyInteger('score')->comment('0-100');
            $table->json('detail')->nullable()->comment('user liên quan, IP...');
            $table->enum('status', ['open', 'ignored', 'confirmed'])->default('open');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'user_id'], 'idx_ff');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('fraud_flags');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_categories');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('saved_products');
    }
};
