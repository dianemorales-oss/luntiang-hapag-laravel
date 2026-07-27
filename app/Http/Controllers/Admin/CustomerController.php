<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = User::orderByDesc('created_at')->get();
        return view('admin.customers.index', compact('customers'));
    }
}
