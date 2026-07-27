<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // admins
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->string('role', 50)->default('Admin');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        // categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        // products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->string('variety', 200)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('unit', 50)->default('per cup');
            $table->string('image', 255)->nullable();
            $table->string('image_2', 255)->nullable();
            $table->string('image_3', 255)->nullable();
            $table->integer('calories')->nullable();
            $table->decimal('protein', 5, 1)->nullable();
            $table->decimal('fiber', 5, 1)->nullable();
            $table->string('vitamin_a', 50)->nullable();
            $table->string('vitamin_c', 50)->nullable();
            $table->text('best_for')->nullable();
            $table->text('storage_instructions')->nullable();
            $table->string('shelf_life', 100)->nullable();
            $table->string('harvest_time', 100)->default('1-3 hours after order');
            $table->integer('plants_available')->default(0);
            $table->boolean('is_best_seller')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        // tickets
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('subject', 150)->default('General Inquiry');
            $table->string('category', 50)->default('General');
            $table->string('priority', 20)->default('Medium'); // enum Low, Medium, High
            $table->string('order_number', 50)->nullable();
            $table->text('issue_description');
            $table->text('attachment_path')->nullable();
            $table->string('status', 20)->default('open'); // open, in_progress, resolved, closed
            $table->text('admin_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('sender_type', 20); // customer, admin
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        // warranty_requests (freshness guarantee)
        Schema::create('warranty_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('product_name', 150);
            $table->string('order_number', 50)->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('quality_issue', 100)->nullable();
            $table->text('defect_description');
            $table->text('proof_of_purchase_path')->nullable();
            $table->text('damage_photo_path')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, denied
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        // return_requests
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('order_number', 50);
            $table->string('product_name', 150)->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('reason_category', 50)->nullable();
            $table->text('reason');
            $table->string('product_condition', 20)->nullable();
            $table->text('proof_of_purchase_path')->nullable();
            $table->text('damage_photo_path')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, denied, completed
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        // notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->integer('related_id');
            $table->string('title', 150);
            $table->text('message');
            $table->string('customer_name', 150)->nullable();
            $table->string('related_link', 255)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        // feedback
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('guest_name', 150)->nullable();
            $table->string('guest_email', 150)->nullable();
            $table->string('subject', 150)->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        // live_chat_messages
        Schema::create('live_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('chat_key', 64);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name', 150);
            $table->string('sender', 20); // customer, admin, bot
            $table->text('message');
            $table->string('image_path', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->index('chat_key');
        });

        Schema::create('chat_bot_state', function (Blueprint $table) {
            $table->string('chat_key', 64)->primary();
            $table->boolean('bot_active')->default(true);
            $table->string('pending_intent', 50)->nullable();
            $table->text('pending_context')->nullable();
            $table->string('last_topic', 100)->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();
        });

        // faqs
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 255);
            $table->text('answer');
            $table->string('category', 50)->default('General');
            $table->timestamps();
        });

        // orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('order_number', 20)->unique();
            $table->string('status', 30)->default('preparing'); // preparing, ready, delivered, completed, cancelled, etc
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('delivery_method', 20)->default('delivery'); // delivery, pickup
            $table->string('payment_method', 50)->default('cod');
            $table->string('promo_code', 50)->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('delivery_city', 100)->nullable();
            $table->string('delivery_province', 100)->nullable();
            $table->string('delivery_zip', 20)->nullable();
            $table->text('delivery_notes')->nullable();
            $table->text('gift_note')->nullable();
            $table->string('preferred_delivery_time', 100)->nullable();
            $table->boolean('is_free_delivery')->default(false);
            $table->string('estimated_harvest_time', 100)->nullable();
            $table->string('customer_name', 200)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->string('cancellation_reason', 100)->nullable();
            $table->text('cancellation_notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name', 200);
            $table->decimal('price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->string('harvest_notes', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('wishlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('review_title', 200)->nullable();
            $table->unsignedTinyInteger('freshness_rating')->nullable();
            $table->unsignedTinyInteger('packaging_rating')->nullable();
            $table->unsignedTinyInteger('delivery_rating')->nullable();
            $table->text('comment')->nullable();
            $table->text('photos')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->integer('helpful_count')->default(0);
            $table->text('admin_reply')->nullable();
            $table->timestamp('admin_replied_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description', 255)->nullable();
            $table->string('discount_type', 20)->default('percentage'); // percentage, fixed
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_order', 10, 2)->default(0);
            $table->integer('max_uses')->nullable();
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_free_delivery')->default(false);
            $table->date('expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('content');
            $table->string('category', 100)->default('General');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('label', 100)->default('Default');
            $table->text('address');
            $table->string('city', 100);
            $table->string('province', 100);
            $table->string('zip', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('claimed_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamp('claimed_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['user_id', 'promotion_id']);
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('quantity')->default(1);
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('claimed_coupons');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('knowledge_base');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('wishlist');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('chat_bot_state');
        Schema::dropIfExists('live_chat_messages');
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('warranty_requests');
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('admins');
    }
};
