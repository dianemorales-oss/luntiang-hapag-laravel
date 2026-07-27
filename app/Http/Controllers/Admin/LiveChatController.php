<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\LiveChatMessage;
use Illuminate\Http\Request;

class LiveChatController extends Controller
{
    public function index()
    {
        // group by chat_key
        $conversations = LiveChatMessage::orderByDesc('created_at')->get()->groupBy('chat_key')->map(function($msgs){
            $first = $msgs->last();
            $last = $msgs->first();
            return [
                'chat_key'=>$first->chat_key,
                'customer_name'=>$first->customer_name,
                'user_id'=>$first->user_id,
                'last_message'=>$last->message,
                'last_at'=>$last->created_at,
                'count'=>$msgs->count(),
            ];
        })->values();

        return view('admin.live-chat.index', compact('conversations'));
    }

    public function show(Request $request, $chatKey)
    {
        $messages = LiveChatMessage::where('chat_key',$chatKey)->orderBy('id')->get();
        return view('admin.live-chat.show', compact('messages','chatKey'));
    }

    public function send(Request $request, $chatKey)
    {
        $message = trim($request->input('message'));
        if (empty($message)) return back()->with('error','Empty');

        LiveChatMessage::create([
            'chat_key'=>$chatKey,
            'user_id'=>null,
            'customer_name'=>'Admin',
            'sender'=>'admin',
            'message'=>$message,
        ]);

        // set bot inactive
        \App\Models\ChatBotState::updateOrCreate(['chat_key'=>$chatKey], ['bot_active'=>false]);

        return back();
    }

    public function poll(Request $request, $chatKey)
    {
        $lastId = (int)$request->get('last_id',0);
        $messages = LiveChatMessage::where('chat_key',$chatKey)->where('id','>',$lastId)->orderBy('id')->get();
        return response()->json(['messages'=>$messages]);
    }

    public function deleteConversation($chatKey)
    {
        LiveChatMessage::where('chat_key',$chatKey)->delete();
        \App\Models\ChatBotState::where('chat_key',$chatKey)->delete();
        return redirect()->route('admin.live-chat.index')->with('success','Deleted');
    }
}
