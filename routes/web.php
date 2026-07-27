<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SupportController;
use App\Models\Promotion;
use App\Models\ClaimedCoupon;

// Public pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/index.php', [HomeController::class, 'index']);
Route::get('/index', [HomeController::class, 'index']);

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products.php', [ProductController::class, 'index']);
Route::get('/product/{slug?}', [ProductController::class, 'show'])->name('products.show');
Route::get('/product.php', [ProductController::class, 'show']);

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::get('/cart.php', [CartController::class, 'index']);
Route::get('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart-ajax', [CartController::class, 'addAjax'])->name('cart.ajax');
Route::post('/cart-actions-ajax.php', [CartController::class, 'addAjax']);
Route::get('/cart-actions.php', [CartController::class, 'add']);
Route::post('/cart-actions.php', [CartController::class, 'add']);

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index')->middleware('customer.auth');
Route::get('/checkout.php', [CheckoutController::class, 'index'])->middleware('customer.auth');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('customer.auth');
Route::post('/checkout.php', [CheckoutController::class, 'store'])->middleware('customer.auth');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login.php', [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/login.php', [AuthController::class, 'login']);

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::get('/register.php', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/register.php', [AuthController::class, 'register']);

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout.php', [AuthController::class, 'logout']);

Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('forgot.password');
Route::get('/forgot-password.php', [AuthController::class, 'showForgot']);
Route::post('/forgot-password', [AuthController::class, 'forgot'])->name('forgot.password.submit');
Route::post('/forgot-password.php', [AuthController::class, 'forgot']);

Route::get('/reset-password/{token?}', [AuthController::class, 'showReset'])->name('reset.password');
Route::get('/reset-password.php', [AuthController::class, 'showReset']);
Route::post('/reset-password', [AuthController::class, 'reset'])->name('reset.password.submit');
Route::post('/reset-password.php', [AuthController::class, 'reset']);

Route::get('/about', [SupportController::class, 'about'])->name('about');
Route::get('/about.php', [SupportController::class, 'about']);
Route::get('/faq', [SupportController::class, 'faq'])->name('faq');
Route::get('/faq.php', [SupportController::class, 'faq']);
Route::get('/privacy', [SupportController::class, 'privacy'])->name('privacy');
Route::get('/privacy.php', [SupportController::class, 'privacy']);
Route::get('/terms', [SupportController::class, 'terms'])->name('terms');
Route::get('/terms.php', [SupportController::class, 'terms']);
Route::get('/contact-support', [SupportController::class, 'contact'])->name('contact');
Route::get('/contact-support.php', [SupportController::class, 'contact']);
Route::post('/contact-support', [SupportController::class, 'contactStore'])->name('contact.submit');
Route::post('/contact-support.php', [SupportController::class, 'contactStore']);
Route::get('/feedback', [SupportController::class, 'feedback'])->name('feedback');
Route::get('/feedback.php', [SupportController::class, 'feedback']);
Route::post('/feedback', [SupportController::class, 'feedbackStore'])->name('feedback.submit');
Route::post('/feedback.php', [SupportController::class, 'feedbackStore']);

// Claim coupon – now supports AJAX for instant vanish + regular redirect fallback
Route::post('/coupons/claim', function(\Illuminate\Http\Request $request){
    if (!session()->has('user_id')) {
        if ($request->expectsJson() || $request->wantsJson() || $request->header('X-Requested-With')==='XMLHttpRequest') {
            return response()->json(['success'=>false,'message'=>'Please login first'], 401);
        }
        return redirect()->route('login');
    }
    $promotionId = (int)$request->input('promotion_id');
    $userId = session()->get('user_id');
    $promotion = Promotion::where('id', $promotionId)
        ->where('is_active', 1)
        ->where(function($q){ $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString()); })
        ->first();

    $isJson = $request->expectsJson() || $request->wantsJson() || $request->ajax() || $request->header('Accept')==='application/json';

    if (!$promotion) {
        $msg = 'Coupon is not available.';
        return $isJson ? response()->json(['success'=>false,'message'=>$msg], 404) : back()->with('error', $msg);
    }
    $exists = ClaimedCoupon::where('user_id',$userId)->where('promotion_id',$promotionId)->exists();
    if ($exists) {
        $msg = 'You already claimed this coupon.';
        return $isJson ? response()->json(['success'=>false,'message'=>$msg], 409) : back()->with('error', $msg);
    }
    ClaimedCoupon::create(['user_id'=>$userId,'promotion_id'=>$promotionId]);

    if ($isJson) {
        return response()->json(['success'=>true,'message'=>'Coupon claimed! It will now vanish from home.','promotion_id'=>$promotionId]);
    }
    return back()->with('success','Coupon claimed');
})->name('coupons.claim')->middleware('customer.auth');

// Customer protected
Route::middleware(['customer.auth'])->group(function(){
    Route::match(['get', 'post'], '/my-profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::match(['get', 'post'], '/my-profile.php', [ProfileController::class, 'index']);
    Route::get('/edit-profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/edit-profile.php', [ProfileController::class, 'edit']);
    Route::post('/edit-profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/edit-profile.php', [ProfileController::class, 'update']);
    Route::get('/change-password', [ProfileController::class, 'showChangePassword'])->name('profile.change-password');
    Route::get('/change-password.php', [ProfileController::class, 'showChangePassword']);
    Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password.submit');
    Route::post('/change-password.php', [ProfileController::class, 'changePassword']);

    Route::get('/order-confirmation', [OrderController::class, 'confirmation'])->name('order.confirmation');
    Route::get('/order-confirmation.php', [OrderController::class, 'confirmation']);
    Route::get('/order-tracking', [OrderController::class, 'tracking'])->name('orders.tracking');
    Route::get('/order-tracking.php', [OrderController::class, 'tracking']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/submit-ticket', [TicketController::class, 'create'])->name('tickets.create');
    Route::get('/submit-ticket.php', [TicketController::class, 'create']);
    Route::post('/submit-ticket', [TicketController::class, 'store'])->name('tickets.store');
    Route::post('/submit-ticket.php', [TicketController::class, 'store']);
    Route::get('/ticket-view/{id}', [TicketController::class, 'show'])->name('tickets.show');
    Route::get('/ticket-view.php', [TicketController::class, 'show']);
    Route::post('/ticket-view/{id}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::get('/tickets/{id}/close', [TicketController::class, 'close'])->name('tickets.close');
    Route::get('/tickets/{id}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');

    Route::get('/returns-refund', [ReturnController::class, 'index'])->name('returns.index');
    Route::get('/returns-refund.php', [ReturnController::class, 'index']);
    Route::post('/returns-refund', [ReturnController::class, 'store'])->name('returns.store');
    Route::post('/returns-refund.php', [ReturnController::class, 'store']);

    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::post('/review-actions.php', [ReviewController::class, 'store']);
});

// Live Chat public (guest and logged in)
Route::get('/live-chat', [ChatController::class, 'index'])->name('chat.index');
Route::get('/live-chat.php', [ChatController::class, 'index']);
Route::post('/chat-send', [ChatController::class, 'send'])->name('chat.send');
Route::post('/chat-send.php', [ChatController::class, 'send']);
Route::get('/chat-poll', [ChatController::class, 'poll'])->name('chat.poll');
Route::get('/chat-poll.php', [ChatController::class, 'poll']);

// Admin routes
Route::get('/admin/login', [App\Http\Controllers\Admin\AuthController::class, 'showLogin'])->name('admin.login');
Route::get('/admin/admin-login.php', [App\Http\Controllers\Admin\AuthController::class, 'showLogin']);
Route::post('/admin/login', [App\Http\Controllers\Admin\AuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/admin-login.php', [App\Http\Controllers\Admin\AuthController::class, 'login']);
Route::get('/admin/logout', [App\Http\Controllers\Admin\AuthController::class, 'logout'])->name('admin.logout');
Route::get('/admin/admin-logout.php', [App\Http\Controllers\Admin\AuthController::class, 'logout']);

Route::middleware(['admin.auth'])->prefix('admin')->name('admin.')->group(function(){
    Route::get('/', function(){ return redirect()->route('admin.dashboard'); });
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin-dashboard.php', [App\Http\Controllers\Admin\DashboardController::class, 'index']);

    Route::get('/products', [App\Http\Controllers\Admin\ProductController::class, 'index'])->name('products.index');
    Route::get('/admin-products.php', [App\Http\Controllers\Admin\ProductController::class, 'index']);
    Route::post('/products', [App\Http\Controllers\Admin\ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{id}', [App\Http\Controllers\Admin\ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/orders', [App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/admin-orders.php', [App\Http\Controllers\Admin\OrderController::class, 'index']);
    Route::post('/orders/{id}/status', [App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('orders.update');

    Route::match(['get', 'post'], '/customers', [App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/admin-customers.php', [App\Http\Controllers\Admin\CustomerController::class, 'index']);

    Route::get('/tickets', [App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/admin-tickets.php', [App\Http\Controllers\Admin\TicketController::class, 'index']);
    Route::get('/tickets/{id}', [App\Http\Controllers\Admin\TicketController::class, 'show'])->name('tickets.show');
    Route::get('/admin-ticket-detail.php', [App\Http\Controllers\Admin\TicketController::class, 'show']);
    Route::post('/tickets/{id}/reply', [App\Http\Controllers\Admin\TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{id}/status', [App\Http\Controllers\Admin\TicketController::class, 'updateStatus'])->name('tickets.updateStatus');

    Route::get('/faqs', [App\Http\Controllers\Admin\FaqController::class, 'index'])->name('faqs.index');
    Route::get('/admin-faq.php', [App\Http\Controllers\Admin\FaqController::class, 'index']);
    Route::post('/faqs', [App\Http\Controllers\Admin\FaqController::class, 'store'])->name('faqs.store');
    Route::put('/faqs/{id}', [App\Http\Controllers\Admin\FaqController::class, 'update'])->name('faqs.update');
    Route::delete('/faqs/{id}', [App\Http\Controllers\Admin\FaqController::class, 'destroy'])->name('faqs.destroy');

    Route::get('/feedback', [App\Http\Controllers\Admin\FeedbackController::class, 'index'])->name('feedback.index');
    Route::get('/admin-feedback.php', [App\Http\Controllers\Admin\FeedbackController::class, 'index']);
    Route::delete('/feedback/{id}', [App\Http\Controllers\Admin\FeedbackController::class, 'destroy'])->name('feedback.destroy');

    Route::match(['get', 'post'], '/reviews', [App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/admin-reviews.php', [App\Http\Controllers\Admin\ReviewController::class, 'index']);
    Route::put('/reviews/{id}', [App\Http\Controllers\Admin\ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{id}', [App\Http\Controllers\Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('/promotions', [App\Http\Controllers\Admin\PromotionController::class, 'index'])->name('promotions.index');
    Route::get('/admin-promotions.php', [App\Http\Controllers\Admin\PromotionController::class, 'index']);
    Route::post('/promotions', [App\Http\Controllers\Admin\PromotionController::class, 'store'])->name('promotions.store');
    Route::put('/promotions/{id}', [App\Http\Controllers\Admin\PromotionController::class, 'update'])->name('promotions.update');
    Route::delete('/promotions/{id}', [App\Http\Controllers\Admin\PromotionController::class, 'destroy'])->name('promotions.destroy');

    Route::get('/warranty', [App\Http\Controllers\Admin\WarrantyController::class, 'index'])->name('warranty.index');
    Route::get('/admin-warranty.php', [App\Http\Controllers\Admin\WarrantyController::class, 'index']);
    Route::put('/warranty/{id}', [App\Http\Controllers\Admin\WarrantyController::class, 'update'])->name('warranty.update');

    Route::get('/returns', [App\Http\Controllers\Admin\ReturnController::class, 'index'])->name('returns.index');
    Route::get('/admin-returns.php', [App\Http\Controllers\Admin\ReturnController::class, 'index']);
    Route::put('/returns/{id}', [App\Http\Controllers\Admin\ReturnController::class, 'update'])->name('returns.update');

    Route::get('/live-chat', [App\Http\Controllers\Admin\LiveChatController::class, 'index'])->name('live-chat.index');
    Route::get('/admin-live-chat.php', [App\Http\Controllers\Admin\LiveChatController::class, 'index']);
    Route::get('/live-chat/{chatKey}', [App\Http\Controllers\Admin\LiveChatController::class, 'show'])->name('live-chat.show');
    Route::post('/live-chat/{chatKey}/send', [App\Http\Controllers\Admin\LiveChatController::class, 'send'])->name('live-chat.send');
    Route::get('/live-chat/{chatKey}/poll', [App\Http\Controllers\Admin\LiveChatController::class, 'poll'])->name('live-chat.poll');
    Route::match(['get', 'post'], '/live-chat/{chatKey}/delete', [App\Http\Controllers\Admin\LiveChatController::class, 'deleteConversation'])->name('live-chat.delete');
    Route::match(['get', 'post'], '/chat-delete.php', [App\Http\Controllers\Admin\LiveChatController::class, 'deleteConversation']);
    Route::match(['get', 'post'], '/chat-poll.php', [App\Http\Controllers\Admin\LiveChatController::class, 'poll']);
    Route::match(['get', 'post'], '/chat-send.php', [App\Http\Controllers\Admin\LiveChatController::class, 'send']);

    Route::get('/notifications', [App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications.php', [App\Http\Controllers\Admin\NotificationController::class, 'index']);
    Route::post('/notifications/mark-all', [App\Http\Controllers\Admin\NotificationController::class, 'markAll'])->name('notifications.markAll');
    Route::get('/notifications/mark-all', [App\Http\Controllers\Admin\NotificationController::class, 'markAll']);
    Route::get('/notifications/{id}/open', [App\Http\Controllers\Admin\NotificationController::class, 'open'])->name('notifications.open');
    Route::get('/notification-open.php', [App\Http\Controllers\Admin\NotificationController::class, 'open']);
    Route::get('/notifications-mark-all.php', [App\Http\Controllers\Admin\NotificationController::class, 'markAll']);

    Route::get('/reports', [App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/admin-reports.php', [App\Http\Controllers\Admin\ReportController::class, 'index']);
});
