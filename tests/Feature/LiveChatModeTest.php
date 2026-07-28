<?php

namespace Tests\Feature;

use App\Models\ChatBotState;
use App\Models\LiveChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for switching between the automated assistant and a
 * human support agent in one live-chat conversation.
 */
class LiveChatModeTest extends TestCase
{
    use RefreshDatabase;

    private const CHAT_KEY = '0123456789abcdef0123456789abcdef';

    public function test_mode_buttons_switch_the_same_guest_conversation_both_ways(): void
    {
        $agent = $this->postJson('/chat-mode', [
            'gk' => self::CHAT_KEY,
            'mode' => 'agent',
        ]);

        $agent->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('mode', 'agent')
            ->assertJsonPath('chatKey', self::CHAT_KEY)
            ->assertJsonPath('message.customer_name', 'System');
        $this->assertFalse(ChatBotState::find(self::CHAT_KEY)->bot_active);

        $assistant = $this->postJson('/chat-mode', [
            'gk' => self::CHAT_KEY,
            'mode' => 'assistant',
        ]);

        $assistant->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('mode', 'assistant')
            ->assertJsonPath('chatKey', self::CHAT_KEY)
            ->assertJsonPath('message.customer_name', 'Luntiang H.A.P.A.G. Assistant');
        $this->assertTrue(ChatBotState::find(self::CHAT_KEY)->bot_active);
    }

    public function test_typed_assistant_request_reactivates_before_contextual_matching(): void
    {
        ChatBotState::create([
            'chat_key' => self::CHAT_KEY,
            'bot_active' => false,
            // This reproduces the former failure: a short mode request was
            // treated as a contextual follow-up instead of a mode change.
            'last_topic' => 'topic:delivery_info',
        ]);

        $response = $this->postJson('/chat-send', [
            'gk' => self::CHAT_KEY,
            'message' => 'talk to assistant',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('mode', 'assistant')
            ->assertJsonCount(1, 'botReplies');

        $this->assertTrue(ChatBotState::find(self::CHAT_KEY)->bot_active);
        $this->assertSame("I'm back! What can I help you with?", LiveChatMessage::latest('id')->value('message'));
    }

    public function test_agent_mode_keeps_customer_messages_available_without_bot_replies(): void
    {
        ChatBotState::create(['chat_key' => self::CHAT_KEY, 'bot_active' => false]);

        $response = $this->postJson('/chat-send', [
            'gk' => self::CHAT_KEY,
            'message' => 'My order has not arrived yet.',
        ]);

        $response->assertOk()
            ->assertJsonPath('mode', 'agent')
            ->assertJsonCount(0, 'botReplies');

        $this->assertDatabaseHas('live_chat_messages', [
            'chat_key' => self::CHAT_KEY,
            'sender' => 'customer',
            'message' => 'My order has not arrived yet.',
        ]);
    }

    public function test_typed_agent_request_switches_before_contextual_matching(): void
    {
        ChatBotState::create([
            'chat_key' => self::CHAT_KEY,
            'bot_active' => true,
            'last_topic' => 'topic:delivery_info',
        ]);

        $response = $this->postJson('/chat-send', [
            'gk' => self::CHAT_KEY,
            'message' => 'talk to agent',
        ]);

        $response->assertOk()
            ->assertJsonPath('mode', 'agent')
            ->assertJsonPath('escalate', true)
            ->assertJsonCount(1, 'botReplies');

        $this->assertFalse(ChatBotState::find(self::CHAT_KEY)->bot_active);
    }

}
