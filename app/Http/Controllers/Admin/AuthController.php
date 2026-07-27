<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $email = strtolower(trim($request->input('email', '')));
        $password = $request->input('password', '');

        if ($email === '' || $password === '') {
            return back()->with('error', 'Please enter your admin email and password.')->withInput();
        }

        $admin = Admin::whereRaw('LOWER(email) = ?', [$email])->first();

        // Development fallback: ensure the documented default admin can log in.
        if (!$admin && $email === 'admin@luntianghapag.com' && $password === 'Admin@123') {
            $admin = Admin::create([
                'name' => 'Luntiang H.A.P.A.G. Admin',
                'email' => 'admin@luntianghapag.com',
                'password' => Hash::make('Admin@123'),
                'role' => 'Super Admin',
            ]);
        }

        $valid = false;
        if ($admin) {
            $valid = Hash::check($password, $admin->password);

            // Support accidental/plaintext old admin records, then repair them.
            if (!$valid && hash_equals((string)$admin->password, (string)$password)) {
                $valid = true;
                $admin->password = Hash::make($password);
                $admin->save();
            }
        }

        if (!$admin || !$valid) {
            return back()->with('error','Invalid credentials')->withInput();
        }

        $request->session()->regenerate();
        $request->session()->put('admin_id', $admin->id);
        $request->session()->put('admin_name', $admin->name);
        $request->session()->put('admin_email', $admin->email);
        $request->session()->put('admin_role', $admin->role);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_id','admin_name','admin_email','admin_role']);
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
