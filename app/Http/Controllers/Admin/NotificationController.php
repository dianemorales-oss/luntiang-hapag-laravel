<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::orderByDesc('created_at')->get();
        return view('admin.notifications.index', compact('notifications'));
    }

    public function markAll()
    {
        Notification::where('is_read', false)->update(['is_read'=>true]);
        return back();
    }

    public function open($id)
    {
        $notif = Notification::findOrFail($id);
        $notif->is_read = true;
        $notif->save();

        // redirect based on type
        $map = [
            'ticket_new' => route('admin.tickets.show', $notif->related_id),
            'ticket_reply' => route('admin.tickets.show', $notif->related_id),
            'ticket_reopen' => route('admin.tickets.show', $notif->related_id),
            'ticket_closed' => route('admin.tickets.show', $notif->related_id),
            'warranty_new' => route('admin.warranty.index'),
            'return_new' => route('admin.returns.index'),
            'order_new' => route('admin.orders.index'),
        ];
        $url = $map[$notif->type] ?? route('admin.dashboard');
        return redirect()->to($url);
    }
}
