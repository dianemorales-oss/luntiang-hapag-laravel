<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\LiveChatMessage;
use App\Models\ChatBotState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LiveChatController extends Controller
{
    private function cleanMessageForDatabase(?string $message): string
    {
        $message = (string) $message;
        $clean = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $message);

        return $clean === null ? $message : trim($clean);
    }

    public function index(Request $request)
    {
        // Conversation list: one row per chat_key, showing the latest message.
        $conversations = LiveChatMessage::select('chat_key', DB::raw('MAX(created_at) as last_message_at'))
            ->groupBy('chat_key')
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function($c) {
                // Find first customer message for customer_name
                $firstCust = LiveChatMessage::where('chat_key', $c->chat_key)
                    ->where('sender', 'customer')
                    ->orderBy('created_at')
                    ->first();
                
                $c->customer_name = $firstCust ? $firstCust->customer_name : 'Guest ' . strtoupper(substr($c->chat_key, 0, 4));
                
                // Find last message
                $lastMsg = LiveChatMessage::where('chat_key', $c->chat_key)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                $c->last_message = $lastMsg ? $lastMsg->message : '';
                $c->customer_message_count = LiveChatMessage::where('chat_key', $c->chat_key)->where('sender', 'customer')->count();
                return $c;
            });

        $activeChatKey = $request->get('chat') ?? ($conversations->first()->chat_key ?? '');

        $activeMessages = [];
        if ($activeChatKey !== '') {
            $activeMessages = LiveChatMessage::where('chat_key', $activeChatKey)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();
        }
        $activeLastId = !empty($activeMessages) ? (int)$activeMessages->last()->id : 0;

        $activeCustomerName = 'Customer';
        foreach ($activeMessages as $m) {
            if ($m->sender === 'customer') {
                $activeCustomerName = $m->customer_name;
                break;
            }
        }

        return view('admin.live-chat.index', compact(
            'conversations', 'activeChatKey', 'activeMessages', 'activeLastId', 'activeCustomerName'
        ));
    }

    public function show(Request $request, $chatKey)
    {
        return redirect()->route('admin.live-chat.index', ['chat' => $chatKey]);
    }

    public function send(Request $request, $chatKey = null)
    {
        // Support both route parameter and request input
        $activeChatKey = $chatKey ?? $request->input('chat_key');
        $text = trim($request->input('message', ''));
        $image = $request->file('chat_image');

        if ($activeChatKey === '') {
            return response()->json(['success' => false, 'error' => 'Missing conversation.']);
        }

        if (empty($text) && !$image) {
            return response()->json(['success' => false, 'error' => 'Please write a message or attach an image.']);
        }

        $imagePath = null;
        if ($image) {
            $dest = public_path('uploads/chat');
            if (!is_dir($dest)) mkdir($dest, 0755, true);
            $name = bin2hex(random_bytes(8)) . '.' . $image->getClientOriginalExtension();
            $image->move($dest, $name);
            $imagePath = 'uploads/chat/' . $name;
        }

        $msg = LiveChatMessage::create([
            'chat_key' => $activeChatKey,
            'user_id' => null,
            'customer_name' => 'Luntiang H.A.P.A.G. Support',
            'sender' => 'admin',
            'message' => $this->cleanMessageForDatabase($text),
            'image_path' => $imagePath,
        ]);

        // Notify customer about agent reply (real-time notification)
        try {
            $customerMsg = LiveChatMessage::where('chat_key', $activeChatKey)->where('sender', 'customer')->orderBy('id')->first();
            if ($customerMsg && $customerMsg->user_id) {
                \App\Helpers\CustomerNotificationHelper::chatAgentReply($customerMsg->user_id, $msg->id, 'Support Agent');
            }
        } catch (\Exception $e) {}

        // A human agent has now joined this conversation — stop the bot from replying
        ChatBotState::updateOrCreate(
            ['chat_key' => $activeChatKey],
            ['bot_active' => false, 'pending_intent' => null, 'pending_context' => null]
        );

        if ($request->expectsJson() || $request->header('Content-Type') === 'application/json') {
            return response()->json([
                'success' => true,
                'chatKey' => $activeChatKey,
                'message' => $msg,
            ]);
        }

        return redirect()->route('admin.live-chat.index', ['chat' => $activeChatKey]);
    }

    public function poll(Request $request, $chatKey = null)
    {
        $action = $request->get('action', 'messages');
        
        if ($action === 'conversations') {
            $conversations = LiveChatMessage::select('chat_key', DB::raw('MAX(created_at) as last_message_at'))
                ->groupBy('chat_key')
                ->orderByDesc('last_message_at')
                ->get()
                ->map(function($c) {
                    $firstCust = LiveChatMessage::where('chat_key', $c->chat_key)
                        ->where('sender', 'customer')
                        ->orderBy('created_at')
                        ->first();
                    
                    $c->customer_name = $firstCust ? $firstCust->customer_name : 'Guest ' . strtoupper(substr($c->chat_key, 0, 4));
                    
                    $lastMsg = LiveChatMessage::where('chat_key', $c->chat_key)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    $c->last_message = $lastMsg ? $lastMsg->message : '';
                    return $c;
                });
            return response()->json(['success' => true, 'conversations' => $conversations]);
        }

        $activeChatKey = $chatKey ?? $request->get('chat_key');
        $lastId = (int)$request->get('after_id', 0);

        $messages = LiveChatMessage::where('chat_key', $activeChatKey)
            ->where('id', '>', $lastId)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json(['success' => true, 'messages' => $messages]);
    }

    public function deleteConversation(Request $request, string $chatKey)
    {
        if ($chatKey === '' || mb_strlen($chatKey) > 64) {
            return response()->json(['success' => false, 'error' => 'Invalid conversation.'], 422);
        }

        try {
            DB::transaction(function () use ($chatKey) {
                $deleted = LiveChatMessage::where('chat_key', $chatKey)->delete();
                if ($deleted === 0) {
                    throw new \RuntimeException('This conversation was already deleted or could not be found.');
                }
                ChatBotState::where('chat_key', $chatKey)->delete();
            });
            return response()->json(['success' => true, 'message' => 'Conversation deleted successfully.']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'error' => 'The conversation could not be deleted. Please try again.'], 500);
        }
    }

}
