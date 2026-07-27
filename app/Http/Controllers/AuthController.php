<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Helpers\CartHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $loginInput = trim($request->input('login', ''));
        $password = $request->input('password', '');

        if (empty($loginInput) || empty($password)) {
            return back()->with('error', 'Please enter your email or mobile number and password.')->withInput();
        }

        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        $user = $isEmail ? User::where('email', $loginInput)->first() : User::where('phone', $loginInput)->first();

        if ($user && Hash::check($password, $user->password)) {
            $request->session()->put('user_id', $user->id);
            $request->session()->put('first_name', $user->first_name);
            $request->session()->put('last_name', $user->last_name);
            $request->session()->put('email', $user->email);

            $guestCart = $request->session()->get('cart', []);
            CartHelper::mergeGuestCart($user->id, $guestCart);

            return redirect()->route('profile.index');
        }

        return back()->with('error', 'Invalid email/mobile number or password.')->withInput();
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $first_name = trim($request->input('first_name'));
        $last_name = trim($request->input('last_name'));
        $email = trim($request->input('email'));
        $phone = trim($request->input('phone'));
        $street = trim($request->input('street'));
        $city = trim($request->input('city'));
        $province = trim($request->input('province'));
        $zip = trim($request->input('zip'));
        $password = $request->input('password');
        $confirm = $request->input('confirm_password');
        $accept = $request->has('accept_terms');

        $address = $street . ', ' . $city . ', ' . $province . ' ' . $zip;

        if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($street) || empty($city) || empty($province) || empty($zip) || empty($password) || empty($confirm)) {
            return back()->with('error', 'Please fill in all required fields.')->withInput();
        }
        if (!$accept) {
            return back()->with('error', 'You must agree to the Terms of Service and Privacy Policy.')->withInput();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'Please enter a valid email address.')->withInput();
        }
        if (!preg_match('/^[A-Za-z\s\-\.]{1,50}$/', $first_name)) {
            return back()->with('error', 'First name must contain letters only.')->withInput();
        }
        if (!preg_match('/^[A-Za-z\s\-\.]{1,50}$/', $last_name)) {
            return back()->with('error', 'Last name must contain letters only.')->withInput();
        }
        if (!preg_match('/^\d{11}$/', $phone)) {
            return back()->with('error', 'Please enter a valid 11-digit phone number.')->withInput();
        }
        if (!preg_match('/^\d{4}$/', $zip)) {
            return back()->with('error', 'Please enter a valid 4-digit ZIP Code.')->withInput();
        }
        if ($password !== $confirm) {
            return back()->with('error', 'Passwords do not match.')->withInput();
        }
        if (strlen($password) < 8) {
            return back()->with('error', 'Password must be at least 8 characters.')->withInput();
        }
        if (User::where('email', $email)->exists()) {
            return back()->with('error', 'Email already registered.')->withInput();
        }
        if (User::where('phone', $phone)->exists()) {
            return back()->with('error', 'Phone number already registered.')->withInput();
        }

        $user = User::create([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'password' => Hash::make($password),
        ]);

        // create default address
        \App\Models\CustomerAddress::create([
            'user_id' => $user->id,
            'label' => 'Default',
            'address' => $street,
            'city' => $city,
            'province' => $province,
            'zip' => $zip,
            'is_default' => true,
        ]);

        return redirect()->route('login')->with('success', 'Your account has been created successfully. You can now sign in.');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['user_id','first_name','last_name','email']);
        $request->session()->flush();
        return redirect()->route('home');
    }

    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function forgot(Request $request)
    {
        $email = trim($request->input('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'Please enter a valid email.');
        }
        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->with('error', 'No account found with that email.');
        }
        $token = bin2hex(random_bytes(32));
        $user->reset_token = $token;
        $user->reset_token_expires = now()->addHour();
        $user->save();

        // In dev, show preview link
        return view('auth.forgot-password', ['success'=>true, 'token'=>$token, 'email'=>$email]);
    }

    public function showReset(Request $request, $token = null)
    {
        $token = $token ?? $request->get('token');
        if (!$token) return redirect()->route('login');
        $user = User::where('reset_token', $token)->where('reset_token_expires', '>', now())->first();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Invalid or expired reset link.');
        }
        return view('auth.reset-password', compact('token'));
    }

    public function reset(Request $request)
    {
        $token = $request->input('token');
        $password = $request->input('password');
        $confirm = $request->input('confirm_password');

        if (empty($token) || empty($password) || empty($confirm)) {
            return back()->with('error', 'Please fill all fields.');
        }
        if ($password !== $confirm) {
            return back()->with('error', 'Passwords do not match.');
        }
        if (strlen($password) < 8) {
            return back()->with('error', 'Password must be at least 8 chars.');
        }

        $user = User::where('reset_token', $token)->where('reset_token_expires', '>', now())->first();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Invalid or expired reset link.');
        }

        $user->password = Hash::make($password);
        $user->reset_token = null;
        $user->reset_token_expires = null;
        $user->save();

        return redirect()->route('login')->with('success', 'Password reset successful. You can now sign in.');
    }
}
