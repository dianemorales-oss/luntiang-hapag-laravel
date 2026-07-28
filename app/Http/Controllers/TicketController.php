<?php
namespace App\Http\Controllers;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Helpers\FormHelper;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function create(Request $request)
    {
        $categories = ['Order Issue','Product Defect','Delivery Issue','Payment Issue','Account Issue','Website / Technical Issue','Other'];
        $priorities = ['Low','Medium','High'];
        $formData = $request->session()->get('pending_ticket', [
            'subject'=>'','category'=>'','priority'=>'Medium','order_number'=>'','issue_description'=>''
        ]);
        $userId = $request->session()->get('user_id');
        $tickets = Ticket::where('user_id', $userId)->orderByDesc('created_at')->take(5)->get();
        return view('tickets.create', compact('categories','priorities','formData','tickets'));
    }

    public function store(Request $request)
    {
        $categories = ['Order Issue','Product Defect','Delivery Issue','Payment Issue','Account Issue','Website / Technical Issue','Other'];
        $priorities = ['Low','Medium','High'];

        if ($request->has('confirm_submit')) {
            $submitted = $request->session()->get('pending_ticket');
            if (!$submitted) return redirect()->route('tickets.create')->with('error','Session expired');

            $ticket = Ticket::create([
                'user_id' => $request->session()->get('user_id'),
                'subject' => $submitted['subject'],
                'category' => $submitted['category'],
                'priority' => $submitted['priority'],
                'order_number' => $submitted['order_number'] ?: null,
                'issue_description' => $submitted['issue_description'],
                'attachment_path' => $submitted['attachment_path'] ?? null,
                'status' => 'open',
            ]);

            $user = \App\Models\User::find($request->session()->get('user_id'));
            $customerName = $user->first_name . ' ' . $user->last_name;
            NotificationHelper::create('ticket_new', $ticket->id, 'New Support Ticket', $ticket->subject . "\nSubmitted by: " . $customerName, $customerName);

            $request->session()->forget('pending_ticket');
            return redirect()->route('profile.index', ['section'=>'support'])->with('success','Ticket submitted')->with('ticket_id',$ticket->id);
        }

        $subject = trim($request->input('subject',''));
        $category = trim($request->input('category',''));
        $priority = trim($request->input('priority',''));
        $order_number = trim($request->input('order_number',''));
        $issue_description = trim($request->input('issue_description',''));

        $formData = compact('subject','category','priority','order_number','issue_description');

        if (empty($subject) || empty($issue_description) || empty($category) || empty($priority)) {
            return back()->with('error','Please fill in the subject, category, priority, and description.')->withInput();
        }
        if (!in_array($category, $categories, true)) return back()->with('error','Invalid category')->withInput();
        if (!in_array($priority, $priorities, true)) return back()->with('error','Invalid priority')->withInput();
        if (mb_strlen($issue_description) > 1000) return back()->with('error','Description too long')->withInput();
        if ($order_number !== '' && !FormHelper::isValidOrderNumber($order_number)) {
            return back()->with('error', FormHelper::ORDER_NUMBER_HELP_TEXT)->withInput();
        }

        // file upload
        $uploadResult = FormHelper::handleUpload($request->file('attachment'), ['jpg','jpeg','png','pdf'], storage_path('app/public/tickets'), 'uploads/tickets', false);
        if (!$uploadResult['ok']) return back()->with('error',$uploadResult['error'])->withInput();

        // Copy to public uploads for compatibility with original path
        $publicDir = public_path('uploads/tickets');
        if (!is_dir($publicDir)) mkdir($publicDir,0755,true);
        foreach ($uploadResult['paths'] as $path) {
            $src = storage_path('app/public/' . basename(dirname($path)) . '/' . basename($path));
            // Already moved to storage, also copy to public
            $storagePath = storage_path('app/public/tickets/' . basename($path));
            $publicPath = $publicDir . '/' . basename($path);
            if (file_exists($storagePath) && !file_exists($publicPath)) @copy($storagePath, $publicPath);
        }

        $request->session()->put('pending_ticket', [
            'subject'=>$subject,
            'category'=>$category,
            'priority'=>$priority,
            'order_number'=>$order_number,
            'issue_description'=>$issue_description,
            'attachment_path'=>FormHelper::encodeAttachmentPaths($uploadResult['paths']),
            'attachment_names'=>$uploadResult['names']
        ]);

        return view('tickets.confirm', ['submittedData'=>$request->session()->get('pending_ticket'), 'categories'=>$categories, 'priorities'=>$priorities]);
    }

    public function show(Request $request, $id)
    {
        $userId = $request->session()->get('user_id');
        $ticket = Ticket::where('id',$id)->where('user_id',$userId)->with('replies')->firstOrFail();
        $user = \App\Models\User::find($userId);
        return view('tickets.show', compact('ticket','user'));
    }

    public function reply(Request $request, $id)
    {
        $userId = $request->session()->get('user_id');
        $ticket = Ticket::where('id',$id)->where('user_id',$userId)->firstOrFail();
        $message = trim($request->input('message'));
        if (empty($message)) return back()->with('error','Message required');
        if (mb_strlen($message) > 2000) return back()->with('error','Reply must not exceed 2,000 characters.');
        if ($ticket->status === 'closed') return back()->with('error','Ticket closed');

        TicketReply::create([
            'ticket_id'=>$ticket->id,
            'sender_type'=>'customer',
            'message'=>$message,
        ]);
        $ticket->status = 'open';
        $ticket->save();

        $user = \App\Models\User::find($userId);
        NotificationHelper::create('ticket_reply', $ticket->id, 'Ticket Reply', $message, $user->first_name . ' ' . $user->last_name);

        return back()->with('success','Reply sent');
    }

    public function close(Request $request, $id)
    {
        $userId = $request->session()->get('user_id');
        $ticket = Ticket::where('id',$id)->where('user_id',$userId)->firstOrFail();
        if ($ticket->status !== 'resolved') return back()->with('error','Only resolved tickets can be closed from this confirmation.');
        $ticket->status = 'closed';
        $ticket->save();
        $user = \App\Models\User::find($userId);
        NotificationHelper::create('ticket_closed', $ticket->id, 'Ticket Closed', "Ticket #{$ticket->id} closed by customer", $user->first_name);
        return redirect()->route('profile.index')->with('success','Ticket closed');
    }

    public function reopen(Request $request, $id)
    {
        $userId = $request->session()->get('user_id');
        $ticket = Ticket::where('id',$id)->where('user_id',$userId)->firstOrFail();
        if ($ticket->status !== 'resolved') return back()->with('error','This ticket can no longer be reopened from the resolution confirmation.');
        $ticket->status = 'open';
        $ticket->save();
        $user = \App\Models\User::find($userId);
        NotificationHelper::create('ticket_reopen', $ticket->id, 'Ticket Reopened', "Ticket #{$ticket->id} reopened", $user->first_name);
        return back()->with('success','Ticket reopened');
    }
}
