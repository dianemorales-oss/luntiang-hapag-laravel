<?php
namespace App\Http\Controllers;
use App\Models\LiveChatMessage;
use App\Models\ChatBotState;
use App\Services\ChatbotEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    private function getChatKey(Request $request)
    {
        if ($request->session()->has('user_id')) {
            return 'user-' . $request->session()->get('user_id');
        }
        $gk = $request->get('gk') ?? $request->input('gk') ?? $request->session()->get('guest_chat_key');
        if (is_string($gk) && preg_match('/^[a-f0-9]{32}$/', $gk)) {
            $request->session()->put('guest_chat_key', $gk);
            return $gk;
        }
        $newGk = bin2hex(random_bytes(16));
        $request->session()->put('guest_chat_key', $newGk);
        return $newGk;
    }

    private function cleanMessageForDatabase(?string $message): string
    {
        $message = (string) $message;

        // Some existing MySQL tables were created with utf8/utf8mb3 instead of
        // utf8mb4, which rejects 4-byte emoji characters and causes SQL 1366.
        // Strip those characters before saving chat messages so Live Chat works
        // even before the charset migration is run locally.
        $clean = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $message);

        return $clean === null ? $message : trim($clean);
    }

    private function getCustomerName(Request $request, $chatKey)
    {
        if ($request->session()->has('user_id')) {
            return trim($request->session()->get('first_name') . ' ' . ($request->session()->get('last_name') ?? ''));
        }
        return 'Guest ' . strtoupper(substr($chatKey,0,4));
    }

    public function index(Request $request)
    {
        $chatKey = $this->getChatKey($request);
        $customerName = $this->getCustomerName($request, $chatKey);
        $userId = $request->session()->get('user_id');

        $messages = LiveChatMessage::where('chat_key', $chatKey)->orderBy('id')->get();

        // If no messages, insert bot greeting
        if ($messages->isEmpty()) {
            $greeting = ChatbotEngine::greeting();
            LiveChatMessage::create([
                'chat_key'=>$chatKey,
                'user_id'=>$userId,
                'customer_name'=>'Luntiang H.A.P.A.G. Assistant',
                // Store assistant messages as "admin" for compatibility with older
                // live_chat_messages tables that only allow customer/admin sender values.
                'sender'=>'admin',
                'message'=>$this->cleanMessageForDatabase($greeting),
            ]);
            $messages = LiveChatMessage::where('chat_key', $chatKey)->orderBy('id')->get();
        }

        // Load suggested questions from knowledge base
        $kbPath = app_path('Services/chatbot-knowledge.php');
        $kb = file_exists($kbPath) ? require $kbPath : ['quick_actions'=>[]];
        $suggestedQuestions = $kb['quick_actions']['primary'] ?? [
            ['label'=>'What products do you have?', 'message'=>'What products do you have?'],
            ['label'=>'How does delivery work?', 'message'=>'How does delivery work?'],
            ['label'=>'How fresh is the lettuce?', 'message'=>'How fresh is the lettuce?'],
            ['label'=>'What is a support ticket?', 'message'=>'What is a support ticket?'],
        ];
        $moreQuestions = $kb['quick_actions']['more'] ?? [];

        // Check current bot state for mode
        $botState = ChatBotState::where('chat_key', $chatKey)->first();
        $isAgentMode = $botState ? !$botState->bot_active : false;

        return view('chat.live', compact('chatKey','customerName','messages','userId','suggestedQuestions','moreQuestions','isAgentMode'));
    }

    public function switchMode(Request $request)
    {
        $chatKey = $request->input('gk') ?? $request->get('gk') ?? $this->getChatKey($request);
        if ($request->session()->has('user_id')) {
            $chatKey = 'user-' . $request->session()->get('user_id');
        }
        $mode = strtolower((string) $request->input('mode', 'assistant'));
        if (!in_array($mode, ['assistant', 'agent'], true)) {
            return response()->json(['ok' => false, 'error' => 'Invalid chat mode.'], 422);
        }

        $userId = $request->session()->get('user_id');

        if ($mode === 'agent') {
            ChatBotState::updateOrCreate(
                ['chat_key' => $chatKey],
                ['bot_active' => false, 'pending_intent' => null, 'pending_context' => null]
            );
            LiveChatMessage::create([
                'chat_key' => $chatKey,
                'user_id' => $userId,
                'customer_name' => 'System',
                'sender' => 'admin',
                'message' => 'You are now connected to a live support agent. The AI assistant has been paused. An agent will be with you shortly. You can switch back to the assistant at any time using the mode controls.',
            ]);
        } else {
            ChatBotState::updateOrCreate(
                ['chat_key' => $chatKey],
                ['bot_active' => true, 'pending_intent' => null, 'pending_context' => null]
            );
            LiveChatMessage::create([
                'chat_key' => $chatKey,
                'user_id' => $userId,
                'customer_name' => 'Luntiang H.A.P.A.G. Assistant',
                'sender' => 'admin',
                'message' => ChatbotEngine::greeting() . "\n\nI'm back! Assistant mode restored with full conversation context maintained. How can I help?",
            ]);
        }

        return response()->json(['ok'=>true, 'mode'=>$mode, 'chatKey'=>$chatKey]);
    }

    public function send(Request $request)
    {
        $chatKey = $request->input('gk') ?? $request->get('gk') ?? $this->getChatKey($request);
        if ($request->session()->has('user_id')) {
            $chatKey = 'user-' . $request->session()->get('user_id');
        } else {
            // validate
            if (!preg_match('/^[a-f0-9]{32}$/', $chatKey)) {
                $chatKey = bin2hex(random_bytes(16));
            }
        }

        $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $messageText = trim($request->input('message',''));
        $messageTextForDb = $this->cleanMessageForDatabase($messageText);
        $image = $request->file('image');

        if (empty($messageText) && !$image) {
            return response()->json(['ok'=>false, 'error'=>'Empty message']);
        }

        $customerName = $this->getCustomerName($request, $chatKey);
        $userId = $request->session()->get('user_id');

        $imagePath = null;
        if ($image) {
            $dest = storage_path('app/public/chat');
            if (!is_dir($dest)) mkdir($dest,0755,true);
            $name = bin2hex(random_bytes(8)) . '.' . $image->getClientOriginalExtension();
            $image->move($dest, $name);
            $imagePath = 'uploads/chat/' . $name;
            // copy to public
            $pub = public_path('uploads/chat');
            if (!is_dir($pub)) mkdir($pub,0755,true);
            @copy($dest.'/'.$name, $pub.'/'.$name);
        }

        // Save customer message
        $msg = LiveChatMessage::create([
            'chat_key'=>$chatKey,
            'user_id'=>$userId,
            'customer_name'=>$customerName,
            'sender'=>'customer',
            'message'=>$messageTextForDb,
            'image_path'=>$imagePath,
        ]);

        // Bot response
        $isLoggedIn = $request->session()->has('user_id');
        $engine = new ChatbotEngine($chatKey, $isLoggedIn);
        $result = $engine->respond($messageText);

        $botReplies = [];
        foreach ($result['replies'] ?? [] as $reply) {
            $botMsg = LiveChatMessage::create([
                'chat_key'=>$chatKey,
                'user_id'=>null,
                'customer_name'=>'Luntiang H.A.P.A.G. Assistant',
                // Store assistant messages as "admin" for compatibility with older
                // live_chat_messages tables that only allow customer/admin sender values.
                'sender'=>'admin',
                'message'=>$this->cleanMessageForDatabase($reply),
            ]);
            $botReplies[] = $botMsg;
        }

        return response()->json([
            'ok'=>true,
            'chatKey'=>$chatKey,
            'customerMessage'=>$msg,
            'botReplies'=>$botReplies,
            'escalate'=>$result['escalate'] ?? false,
        ]);
    }

    public function poll(Request $request)
    {
        $chatKey = $request->get('gk') ?? $request->input('gk') ?? $this->getChatKey($request);
        if ($request->session()->has('user_id')) {
            $chatKey = 'user-' . $request->session()->get('user_id');
        }
        $lastId = (int)($request->get('last_id') ?? $request->input('last_id',0));

        $messages = LiveChatMessage::where('chat_key',$chatKey)->where('id','>',$lastId)->orderBy('id')->get();

        return response()->json([
            'messages'=>$messages,
            'chatKey'=>$chatKey,
        ]);
    }
}
