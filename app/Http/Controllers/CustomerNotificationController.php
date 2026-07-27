<?php

namespace App\Http\Controllers;

use App\Models\CustomerNotification;
use Illuminate\Http\Request;

class CustomerNotificationController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->session()->has('user_id')) {
            return redirect()->route('login');
        }
        $userId = $request->session()->get('user_id');
        $notifications = CustomerNotification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $unreadCount = CustomerNotification::where('user_id', $userId)->where('is_read', false)->count();

        return view('notifications.index', compact('notifications','unreadCount'));
    }

    public function apiList(Request $request)
    {
        if (!$request->session()->has('user_id')) {
            return response()->json(['notifications'=>[],'unread'=>0]);
        }
        $userId = $request->session()->get('user_id');
        $notifications = CustomerNotification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $unread = CustomerNotification::where('user_id', $userId)->where('is_read', false)->count();

        return response()->json([
            'notifications' => $notifications,
            'unread' => $unread,
        ]);
    }

    public function markRead(Request $request, $id = null)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) return response()->json(['success'=>false], 401);

        if ($id) {
            CustomerNotification::where('user_id', $userId)->where('id', $id)->update(['is_read'=>true]);
        } else {
            CustomerNotification::where('user_id', $userId)->update(['is_read'=>true]);
        }

        return response()->json(['success'=>true]);
    }

    public function markAllRead(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) return back();

        CustomerNotification::where('user_id', $userId)->update(['is_read'=>true]);

        if ($request->expectsJson()) {
            return response()->json(['success'=>true]);
        }
        return back()->with('success','All notifications marked as read');
    }

    public function open(Request $request, $id)
    {
        $userId = $request->session()->get('user_id');
        $notif = CustomerNotification::where('user_id', $userId)->where('id', $id)->firstOrFail();
        $notif->is_read = true;
        $notif->save();

        if ($notif->link) {
            return redirect()->to($notif->link);
        }
        return redirect()->route('profile.index', ['section'=>'overview']);
    }
}
