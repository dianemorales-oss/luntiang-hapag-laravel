<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Helpers\AdminNoteHelper;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->get('status', 'all');
        $allowedStatuses = ['pending', 'approved', 'denied', 'completed'];

        $query = ReturnRequest::with('user');
        if (in_array($statusFilter, $allowedStatuses, true)) {
            $query->where('status', $statusFilter);
        }
        $requests = $query->orderByDesc('created_at')->get();

        // Calculate counts
        $statusCounts = [];
        foreach ($allowedStatuses as $s) {
            $statusCounts[$s] = ReturnRequest::where('status', $s)->count();
        }
        $totalCount = ReturnRequest::count();

        return view('admin.returns.index', compact('requests', 'statusFilter', 'statusCounts', 'totalCount'));
    }

    public function update(Request $request, $id)
    {
        $rr = ReturnRequest::findOrFail($id);
        $status = $request->input('new_status') ?: $request->input('status');
        $note = $request->input('admin_note') ?: AdminNoteHelper::defaultAdminNote('return', $status);
        $rr->status = $status;
        $rr->admin_note = $note;
        $rr->save();
        return back()->with('success', 'Return request #' . $id . ' updated to "' . ucfirst($status) . '".');
    }
}
