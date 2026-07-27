<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('live_chat_messages')) {
            Schema::create('live_chat_messages', function (Blueprint $table) {
                $table->id();
                $table->string('chat_key', 64)->index();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('customer_name', 150);
                $table->string('sender', 20); // customer, admin, bot
                $table->text('message')->nullable();
                $table->string('image_path', 500)->nullable();
                $table->timestamps();
            });
        } else {
            if (!Schema::hasColumn('live_chat_messages', 'image_path')) {
                Schema::table('live_chat_messages', function (Blueprint $table) {
                    $table->string('image_path', 500)->nullable()->after('message');
                });
            }

            if (!Schema::hasColumn('live_chat_messages', 'created_at')) {
                Schema::table('live_chat_messages', function (Blueprint $table) {
                    $table->timestamp('created_at')->nullable();
                });
            }

            if (!Schema::hasColumn('live_chat_messages', 'updated_at')) {
                Schema::table('live_chat_messages', function (Blueprint $table) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                });
            }

            // Older copies of the original PHP project created this as
            // ENUM('customer','admin'), which rejects the chatbot's `bot` sender.
            // Convert to VARCHAR so customer/admin/bot all work.
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE live_chat_messages MODIFY sender VARCHAR(20) NOT NULL");
            }
        }

        if (!Schema::hasTable('chat_bot_state')) {
            Schema::create('chat_bot_state', function (Blueprint $table) {
                $table->string('chat_key', 64)->primary();
                $table->boolean('bot_active')->default(true);
                $table->string('pending_intent', 50)->nullable();
                $table->text('pending_context')->nullable();
                $table->string('last_topic', 100)->nullable();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        } else {
            if (!Schema::hasColumn('chat_bot_state', 'bot_active')) {
                Schema::table('chat_bot_state', function (Blueprint $table) {
                    $table->boolean('bot_active')->default(true)->after('chat_key');
                });
            }

            if (!Schema::hasColumn('chat_bot_state', 'pending_intent')) {
                Schema::table('chat_bot_state', function (Blueprint $table) {
                    $table->string('pending_intent', 50)->nullable()->after('bot_active');
                });
            }

            if (!Schema::hasColumn('chat_bot_state', 'pending_context')) {
                Schema::table('chat_bot_state', function (Blueprint $table) {
                    $table->text('pending_context')->nullable()->after('pending_intent');
                });
            }

            if (!Schema::hasColumn('chat_bot_state', 'last_topic')) {
                Schema::table('chat_bot_state', function (Blueprint $table) {
                    $table->string('last_topic', 100)->nullable()->after('pending_context');
                });
            }

            if (!Schema::hasColumn('chat_bot_state', 'created_at')) {
                Schema::table('chat_bot_state', function (Blueprint $table) {
                    $table->timestamp('created_at')->nullable()->useCurrent();
                });
            }

            if (!Schema::hasColumn('chat_bot_state', 'updated_at')) {
                Schema::table('chat_bot_state', function (Blueprint $table) {
                    $table->timestamp('updated_at')->nullable()->useCurrent();
                });
            }
        }
    }

    public function down(): void
    {
        // Compatibility migration only. Do not drop chat tables or columns on rollback.
    }
};
