<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('user_id')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            session(['customer_login_redirect' => $request->fullUrl()]);
            return redirect()->route('login')->with('error', 'Please log in first.');
        }
        return $next($request);
    }
}
