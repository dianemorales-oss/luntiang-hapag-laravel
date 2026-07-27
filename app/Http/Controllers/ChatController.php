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
        $gk = $request->get('gk') ?? $request->input('gk');
        if (is_string($gk) && preg_match('/^[a-f0-9]{32}$/', $gk)) {
            return $gk;
        }
        // generate new
        return bin2hex(random_bytes(16));
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
                'sender'=>'bot',
                'message'=>$greeting,
            ]);
            $messages = LiveChatMessage::where('chat_key', $chatKey)->orderBy('id')->get();
        }

        return view('chat.live', compact('chatKey','customerName','messages','userId'));
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

        $messageText = trim($request->input('message',''));
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
            'message'=>$messageText,
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
                'sender'=>'bot',
                'message'=>$reply,
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
