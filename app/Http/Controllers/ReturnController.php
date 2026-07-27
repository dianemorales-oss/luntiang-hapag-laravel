<?php
namespace App\Http\Controllers;
use App\Models\ReturnRequest;
use App\Models\WarrantyRequest;
use App\Helpers\FormHelper;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $formData = $request->session()->get('pending_return', [
            'order_number'=>'','product_name'=>'','purchase_date'=>'','reason_category'=>'','reason'=>'','product_condition'=>''
        ]);
        $reasons = ['Wrong Item','Damaged Item','Wilted Lettuce','Missing Item','Quality Issue','Other'];
        $conditions = ['Unopened','Opened','Damaged'];
        return view('returns.index', compact('formData','reasons','conditions'));
    }

    public function store(Request $request)
    {
        $reasons = ['Wrong Item','Damaged Item','Wilted Lettuce','Missing Item','Quality Issue','Other'];
        $conditions = ['Unopened','Opened','Damaged'];

        if ($request->has('confirm_submit')) {
            $submitted = $request->session()->get('pending_return');
            if (!$submitted) return redirect()->route('returns.index')->with('error','Session expired');

            $rr = ReturnRequest::create([
                'user_id' => $request->session()->get('user_id'),
                'order_number' => $submitted['order_number'],
                'product_name' => $submitted['product_name'],
                'purchase_date' => $submitted['purchase_date'],
                'reason_category' => $submitted['reason_category'],
                'reason' => $submitted['reason'],
                'product_condition' => $submitted['product_condition'],
                'proof_of_purchase_path' => $submitted['proof_path'] ?? null,
                'damage_photo_path' => $submitted['damage_path'] ?? null,
                'status' => 'pending',
            ]);

            $user = \App\Models\User::find($request->session()->get('user_id'));
            NotificationHelper::create('return_new', $rr->id, 'New Return & Refund Request', "Reason: " . $rr->reason_category, $user->first_name.' '.$user->last_name);

            $request->session()->forget('pending_return');
            return redirect()->route('profile.index')->with('success','Return request submitted');
        }

        $order_number = trim($request->input('order_number',''));
        $product_name = trim($request->input('product_name',''));
        $purchase_date = trim($request->input('purchase_date',''));
        $reason_category = trim($request->input('reason_category',''));
        $reason = trim($request->input('reason',''));
        $product_condition = trim($request->input('product_condition',''));

        $formData = compact('order_number','product_name','purchase_date','reason_category','reason','product_condition');

        if (empty($order_number) || empty($product_name) || empty($purchase_date) || empty($reason_category) || empty($reason) || empty($product_condition)) {
            return back()->with('error','Please fill all required fields')->withInput();
        }
        if (!FormHelper::isValidOrderNumber($order_number)) {
            return back()->with('error', FormHelper::ORDER_NUMBER_HELP_TEXT)->withInput();
        }
        if (!in_array($reason_category, $reasons, true)) return back()->with('error','Invalid reason')->withInput();
        if (!in_array($product_condition, $conditions, true)) return back()->with('error','Invalid condition')->withInput();

        $proofUpload = FormHelper::handleUpload($request->file('proof_of_purchase'), ['jpg','jpeg','png','pdf'], storage_path('app/public/returns'), 'uploads/returns', true);
        if (!$proofUpload['ok']) return back()->with('error',$proofUpload['error'])->withInput();
        $damageUpload = FormHelper::handleUpload($request->file('damage_photo'), ['jpg','jpeg','png'], storage_path('app/public/returns'), 'uploads/returns', false);
        if (!$damageUpload['ok']) return back()->with('error',$damageUpload['error'])->withInput();

        $request->session()->put('pending_return', [
            'order_number'=>$order_number,
            'product_name'=>$product_name,
            'purchase_date'=>$purchase_date,
            'reason_category'=>$reason_category,
            'reason'=>$reason,
            'product_condition'=>$product_condition,
            'proof_path'=>FormHelper::encodeAttachmentPaths($proofUpload['paths']),
            'damage_path'=>FormHelper::encodeAttachmentPaths($damageUpload['paths']),
            'proof_names'=>$proofUpload['names'],
            'damage_names'=>$damageUpload['names'],
        ]);

        return view('returns.confirm', ['submittedData'=>$request->session()->get('pending_return'), 'reasons'=>$reasons, 'conditions'=>$conditions]);
    }
}
