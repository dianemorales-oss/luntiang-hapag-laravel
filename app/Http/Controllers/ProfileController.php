<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Ticket;
use App\Models\WarrantyRequest;
use App\Models\ReturnRequest;
use App\Models\Feedback;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ClaimedCoupon;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $user = User::find($userId);
        if (!$user) return redirect()->route('login');

        $section = $request->get('section', 'overview');

        // Handle Reorder
        if ($request->has('reorder')) {
            $oid = (int)$request->get('reorder');
            $order = Order::where('id', $oid)->where('user_id', $userId)->first();
            if ($order) {
                $cart = [];
                foreach ($order->items as $item) {
                    $cart[] = [
                        'id' => (int)$item->product_id,
                        'qty' => (int)$item->quantity
                    ];
                }
                $request->session()->put('cart', $cart);
                return redirect()->route('cart.index')->with('success', 'Items added to cart.');
            }
        }

        // Handle Delete Address
        if ($request->has('deladdr')) {
            $daid = (int)$request->get('deladdr');
            CustomerAddress::where('id', $daid)->where('user_id', $userId)->delete();
            return redirect()->route('profile.index', ['section' => 'addresses'])->with('success', 'Address deleted.');
        }

        // Handle Set Default Address
        if ($request->has('setdefault')) {
            $sdid = (int)$request->get('setdefault');
            CustomerAddress::where('user_id', $userId)->update(['is_default' => 0]);
            CustomerAddress::where('id', $sdid)->where('user_id', $userId)->update(['is_default' => 1]);
            return redirect()->route('profile.index', ['section' => 'addresses'])->with('success', 'Default address updated.');
        }

        // Handle Add Address Post
        if ($request->isMethod('post') && $request->has('save_address')) {
            $alabel = trim($request->input('address_label', 'Home'));
            $aaddr = trim($request->input('address'));
            $acity = ucwords(strtolower(trim($request->input('city'))));
            $aprov = ucwords(strtolower(trim($request->input('province'))));
            $azip = trim($request->input('zip'));

            if ($aaddr && $acity) {
                CustomerAddress::create([
                    'user_id' => $userId,
                    'label' => $alabel,
                    'address' => $aaddr,
                    'city' => $acity,
                    'province' => $aprov,
                    'zip' => $azip,
                    'is_default' => CustomerAddress::where('user_id', $userId)->count() === 0 ? 1 : 0
                ]);
                return redirect()->route('profile.index', ['section' => 'addresses', 'saved' => 1]);
            }
        }

        // Retrieve Order Statistics
        $orderStats = [];
        foreach (['preparing', 'ready', 'delivered', 'completed', 'cancelled'] as $s) {
            $orderStats[$s] = Order::where('user_id', $userId)->where('status', $s)->count();
        }
        $totalOrders = array_sum($orderStats);

        // Active Order
        $activeOrder = Order::where('user_id', $userId)
            ->whereNotIn('status', ['completed', 'cancelled', 'delivered'])
            ->orderByDesc('created_at')
            ->first();

        $activeItems = $activeOrder ? $activeOrder->items : [];

        // All Orders based on Tab
        $orderTab = $request->get('otab', 'all');
        $orderQuery = Order::where('user_id', $userId);
        if ($orderTab === 'active') {
            $orderQuery->whereNotIn('status', ['completed', 'cancelled', 'delivered']);
        } elseif ($orderTab !== 'all') {
            $orderQuery->where('status', $orderTab);
        }
        $allOrders = $orderQuery->orderByDesc('created_at')->limit(20)->get();

        // Support Statistics
        $openTickets = Ticket::where('user_id', $userId)->whereIn('status', ['open', 'in_progress'])->count();
        $pendingReturns = ReturnRequest::where('user_id', $userId)->where('status', 'pending')->count();

        // Saved Addresses
        $addresses = CustomerAddress::where('user_id', $userId)->orderByDesc('is_default')->get();

        // Claimed Coupons
        $coupons = Promotion::join('claimed_coupons', 'promotions.id', '=', 'claimed_coupons.promotion_id')
            ->where('claimed_coupons.user_id', $userId)
            ->where('promotions.is_active', 1)
            ->select('promotions.*', 'claimed_coupons.claimed_at')
            ->orderByDesc('claimed_coupons.claimed_at')
            ->get();

        $tickets = Ticket::where('user_id', $userId)->orderByDesc('created_at')->get();
        $warranty = WarrantyRequest::where('user_id', $userId)->orderByDesc('created_at')->get();
        $returns = ReturnRequest::where('user_id', $userId)->orderByDesc('created_at')->get();

        return view('profile.index', compact(
            'user', 'section', 'orderStats', 'totalOrders', 'activeOrder', 'activeItems',
            'orderTab', 'allOrders', 'openTickets', 'pendingReturns', 'addresses', 'coupons',
            'tickets', 'warranty', 'returns'
        ));
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

        return redirect()->route('profile.index', ['section' => 'profile'])->with('success','Profile updated');
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
        return redirect()->route('profile.index', ['section' => 'profile'])->with('success','Password changed');
    }
}
