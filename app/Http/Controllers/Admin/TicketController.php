<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with('user')->orderByDesc('created_at')->get();
        return view('admin.tickets.index', compact('tickets'));
    }

    public function show($id)
    {
        $ticket = Ticket::with(['user','replies'])->findOrFail($id);
        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $message = trim($request->input('message'));
        if (empty($message)) return back()->with('error','Message required');

        TicketReply::create([
            'ticket_id'=>$ticket->id,
            'sender_type'=>'admin',
            'message'=>$message,
        ]);
        $ticket->status = $request->input('status', 'in_progress');
        $ticket->replied_at = now();
        $ticket->save();

        return back()->with('success','Replied');
    }

    public function updateStatus(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $status = $request->input('status');
        $ticket->status = $status;
        $ticket->save();
        return back()->with('success','Status updated');
    }
}
