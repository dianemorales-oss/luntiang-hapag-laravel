<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Helpers\AdminNoteHelper;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function index()
    {
        $requests = ReturnRequest::with('user')->orderByDesc('created_at')->get();
        return view('admin.returns.index', compact('requests'));
    }

    public function update(Request $request, $id)
    {
        $rr = ReturnRequest::findOrFail($id);
        $status = $request->input('status');
        $note = $request->input('admin_note') ?: AdminNoteHelper::defaultAdminNote('return', $status);
        $rr->status = $status;
        $rr->admin_note = $note;
        $rr->save();
        return back()->with('success','Updated');
    }
}
