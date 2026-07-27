<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('live_chat_messages')) {
            DB::statement('ALTER TABLE live_chat_messages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            DB::statement('ALTER TABLE live_chat_messages MODIFY sender VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
            DB::statement('ALTER TABLE live_chat_messages MODIFY customer_name VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
            DB::statement('ALTER TABLE live_chat_messages MODIFY message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
        }

        if (Schema::hasTable('chat_bot_state')) {
            DB::statement('ALTER TABLE chat_bot_state CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
    }

    public function down(): void
    {
        // Do not convert chat tables back to a smaller charset.
    }
};
