<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\WarrantyRequest;
use App\Helpers\AdminNoteHelper;
use Illuminate\Http\Request;

class WarrantyController extends Controller
{
    public function index()
    {
        $requests = WarrantyRequest::with('user')->orderByDesc('created_at')->get();
        return view('admin.warranty.index', compact('requests'));
    }

    public function update(Request $request, $id)
    {
        $wr = WarrantyRequest::findOrFail($id);
        $status = $request->input('status');
        $note = $request->input('admin_note') ?: AdminNoteHelper::defaultAdminNote('warranty', $status);
        $wr->status = $status;
        $wr->admin_note = $note;
        $wr->save();
        return back()->with('success','Updated');
    }
}
