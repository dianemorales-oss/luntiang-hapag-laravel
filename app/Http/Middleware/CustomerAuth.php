<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            session(['customer_login_redirect' => $request->fullUrl()]);
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        // User uses SoftDeletes, so find() returns null for a deleted account.
        // This invalidates an already-open customer session immediately after an
        // administrator soft-deletes the account.
        if (!\App\Models\User::find($userId)) {
            $request->session()->forget(['user_id', 'first_name', 'last_name', 'email']);
            $request->session()->regenerateToken();
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Account unavailable'], 401);
            }
            return redirect()->route('login')->with('error', 'This customer account is unavailable.');
        }

        return $next($request);
    }
}
