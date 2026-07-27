<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50); // order_status, ticket_status, chat_reply
            $table->string('title', 150);
            $table->text('message');
            $table->unsignedBigInteger('related_id')->nullable(); // order_id, ticket_id, chat_message_id
            $table->string('related_type', 50)->nullable();
            $table->string('link', 255)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notifications');
    }
};
