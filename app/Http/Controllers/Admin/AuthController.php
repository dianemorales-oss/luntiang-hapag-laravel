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

        if (\App\Models\User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return back()->with('error', 'Customer accounts cannot sign in to the Admin Portal.')->withInput();
        }

        $admin = Admin::whereRaw('LOWER(email) = ?', [$email])->first();

        // Development fallback: ensure the documented default admin can log in.
        if (!$admin && $email === 'admin@luntianghapag.com' && $password === 'Admin@123') {
            $admin = Admin::create([
                'name' => 'Luntiang H.A.P.A.G. Admin',
                'first_name' => 'Luntiang',
                'last_name' => 'H.A.P.A.G. Admin',
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
        $request->session()->put('admin_name', trim(($admin->first_name ?? '').' '.($admin->last_name ?? '')) ?: $admin->name);
        $request->session()->put('admin_email', $admin->email);
        $request->session()->put('admin_role', $admin->role);
        $request->session()->put('admin_picture', $admin->profile_picture);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_id','admin_name','admin_email','admin_role','admin_picture']);
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    public function showProfile(Request $request)
    {
        $admin = Admin::find($request->session()->get('admin_id'));
        if (!$admin) return redirect()->route('admin.login');
        return view('admin.profile', compact('admin'));
    }

    public function updateProfile(Request $request)
    {
        $admin = Admin::find($request->session()->get('admin_id'));
        if (!$admin) return redirect()->route('admin.login');

        $firstName = trim($request->input('first_name',''));
        $lastName = trim($request->input('last_name',''));
        $email = strtolower(trim($request->input('email','')));
        $password = $request->input('password','');
        $passwordConfirm = $request->input('password_confirmation','');

        // Validation
        if (empty($firstName) || strlen($firstName) < 2) {
            return back()->with('error','First name must be at least 2 characters.')->withInput();
        }
        if (empty($lastName) || strlen($lastName) < 2) {
            return back()->with('error','Last name must be at least 2 characters.')->withInput();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error','Please enter a valid email address.')->withInput();
        }
        if (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)) {
            return back()->with('error','Invalid email format.')->withInput();
        }
        // Check email uniqueness excluding current admin
        if (Admin::whereRaw('LOWER(email) = ?', [$email])->where('id','!=',$admin->id)->exists()) {
            return back()->with('error','Email already in use by another admin.')->withInput();
        }

        // Profile picture upload
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $allowed = ['jpg','jpeg','png','webp'];
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $allowed)) {
                return back()->with('error','Profile picture must be JPG, PNG, or WEBP.')->withInput();
            }
            if ($file->getSize() > 2*1024*1024) {
                return back()->with('error','Profile picture must be less than 2MB.')->withInput();
            }
            $dir = public_path('uploads/admin');
            if (!is_dir($dir)) mkdir($dir,0755,true);
            $filename = 'admin_'.$admin->id.'_'.time().'.'.$ext;
            $file->move($dir, $filename);
            // Delete old picture if exists
            if ($admin->profile_picture && file_exists(public_path($admin->profile_picture))) {
                @unlink(public_path($admin->profile_picture));
            }
            $admin->profile_picture = 'uploads/admin/'.$filename;
        }

        // Password validation if provided
        if (!empty($password)) {
            if (strlen($password) < 8) {
                return back()->with('error','Password must be at least 8 characters.')->withInput();
            }
            if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
                return back()->with('error','Password must include uppercase, lowercase, number, and special character.')->withInput();
            }
            if ($password !== $passwordConfirm) {
                return back()->with('error','Password confirmation does not match.')->withInput();
            }
            $admin->password = Hash::make($password);
        }

        $admin->first_name = $firstName;
        $admin->last_name = $lastName;
        $admin->name = trim($firstName.' '.$lastName);
        $admin->email = $email;
        $admin->save();

        // Update session
        $request->session()->put('admin_name', $admin->name);
        $request->session()->put('admin_email', $admin->email);
        $request->session()->put('admin_picture', $admin->profile_picture);

        return back()->with('success','Profile updated successfully!');
    }
}

