<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Ticket;
use App\Models\WarrantyRequest;
use App\Models\ReturnRequest;
use App\Models\Feedback;
use App\Models\CustomerAddress;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $user = User::find($userId);
        if (!$user) return redirect()->route('login');

        $tickets = Ticket::where('user_id', $userId)->orderByDesc('created_at')->get();
        $warranty = WarrantyRequest::where('user_id', $userId)->orderByDesc('created_at')->get();
        $returns = ReturnRequest::where('user_id', $userId)->orderByDesc('created_at')->get();
        $orders = Order::where('user_id', $userId)->orderByDesc('created_at')->with('items')->get();
        $feedback = Feedback::where('user_id', $userId)->orderByDesc('created_at')->get();
        $addresses = CustomerAddress::where('user_id', $userId)->get();

        return view('profile.index', compact('user','tickets','warranty','returns','orders','feedback','addresses'));
    }

    public function edit(Request $request)
    {
        $user = User::find($request->session()->get('user_id'));
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = User::find($request->session()->get('user_id'));
        if (!$user) return redirect()->route('login');

        $first = trim($request->input('first_name'));
        $last = trim($request->input('last_name'));
        $phone = trim($request->input('phone'));
        $address = trim($request->input('address'));

        $request->validate([
            'first_name'=>'required',
            'last_name'=>'required',
            'phone'=>'required',
            'address'=>'required',
        ]);

        $user->first_name = $first;
        $user->last_name = $last;
        $user->phone = $phone;
        $user->address = $address;
        $user->save();

        $request->session()->put('first_name', $first);
        $request->session()->put('last_name', $last);

        return redirect()->route('profile.index')->with('success','Profile updated');
    }

    public function showChangePassword()
    {
        return view('profile.change-password');
    }

    public function changePassword(Request $request)
    {
        $user = User::find($request->session()->get('user_id'));
        $current = $request->input('current_password');
        $new = $request->input('new_password');
        $confirm = $request->input('confirm_password');

        if (!Hash::check($current, $user->password)) {
            return back()->with('error','Current password incorrect');
        }
        if ($new !== $confirm) {
            return back()->with('error','New passwords do not match');
        }
        if (strlen($new) < 8) {
            return back()->with('error','Password must be at least 8 chars');
        }
        $user->password = Hash::make($new);
        $user->save();
        return redirect()->route('profile.index')->with('success','Password changed');
    }
}
