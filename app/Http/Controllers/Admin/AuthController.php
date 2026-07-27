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
        $email = trim($request->input('email'));
        $password = $request->input('password');

        $admin = Admin::where('email', $email)->first();
        if (!$admin || !Hash::check($password, $admin->password)) {
            return back()->with('error','Invalid credentials');
        }

        $request->session()->put('admin_id', $admin->id);
        $request->session()->put('admin_name', $admin->name);
        $request->session()->put('admin_email', $admin->email);
        $request->session()->put('admin_role', $admin->role);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_id','admin_name','admin_email','admin_role']);
        return redirect()->route('admin.login');
    }
}
