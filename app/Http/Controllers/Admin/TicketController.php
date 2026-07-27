<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->get('status', 'all');
        $search = trim($request->get('q', ''));

        $query = Ticket::with('user');

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%$search%")
                  ->orWhere('category', 'like', "%$search%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
                  });
            });
        }

        $tickets = $query->orderByDesc('created_at')->get();

        // Calculate counts
        $statusCounts = [];
        foreach (['open', 'in_progress', 'resolved', 'closed'] as $s) {
            $statusCounts[$s] = Ticket::where('status', $s)->count();
        }
        $totalCount = Ticket::count();

        return view('admin.tickets.index', compact('tickets', 'statusFilter', 'search', 'statusCounts', 'totalCount'));
    }

    public function show($id)
    {
        $ticket = Ticket::with(['user', 'replies'])->findOrFail($id);
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
