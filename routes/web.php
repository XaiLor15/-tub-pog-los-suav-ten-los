<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\ElderController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\FundController;
use App\Http\Controllers\LeadershipController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
 * Access levels (Dev Handoff route table): public routes need no login; `access:pending`
 * = any signed-in account; `access:member` = approved members + admins; `access:admin`.
 * Fine-grained rules (own records, private columns) are enforced again in controllers via Gates.
 */

/* ---------------- Auth ---------------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/reset-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/reset-password/email', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::view('/unauthorized', 'errors.unauthorized')->name('unauthorized');

/* ---------------- Public (content filtered by role) ---------------- */
Route::redirect('/', '/dashboard');
Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::get('/search', SearchController::class)->middleware('throttle:60,1')->name('search');

Route::get('/members', [MemberController::class, 'index'])->name('members.index');
Route::get('/members/tree-data', [MemberController::class, 'treeData'])->name('members.tree-data');
Route::get('/members/{member}', [MemberController::class, 'show'])->whereNumber('member')->name('members.show');
Route::get('/members/{member}/lineage', [MemberController::class, 'lineage'])->whereNumber('member')->name('members.lineage');

Route::get('/leadership', [LeadershipController::class, 'index'])->name('leadership.index');
Route::get('/elders', [ElderController::class, 'index'])->name('elders.index');
Route::get('/elders/{elder}', [ElderController::class, 'show'])->whereNumber('elder')->name('elders.show');
Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
Route::get('/activities/{event}', [ActivityController::class, 'show'])->whereNumber('event')->name('activities.show');
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{news}', [NewsController::class, 'show'])->whereNumber('news')->name('news.show');

/* Media is streamed from the private disk after a permission check. */
Route::prefix('media')->name('media.')->controller(MediaController::class)->group(function () {
    Route::get('member/{member}', 'member')->name('member');
    Route::get('leadership/{leadership}', 'leadership')->name('leadership');
    Route::get('elder/{elder}', 'elder')->name('elder');
    Route::get('event-cover/{event}', 'eventCover')->name('event-cover');
    Route::get('item/{mediaItem}', 'item')->name('item');
    Route::get('avatar/{user}', 'avatar')->name('avatar');
    Route::get('slip/{donationPayment}', 'slip')->middleware('access:admin')->name('slip');
});

/* ---------------- Pending + (any signed-in account) ---------------- */
Route::middleware('access:pending')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::post('/profile/preferences', [ProfileController::class, 'preferences'])->name('profile.preferences');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/read', [NotificationController::class, 'read'])->name('notifications.read');
});

/* ---------------- Member + ---------------- */
Route::middleware('access:member')->group(function () {
    Route::get('/members/export.csv', [MemberController::class, 'export'])->name('members.export');
    Route::get('/members/options', [MemberController::class, 'options'])->name('members.options');
    Route::get('/members/duplicates', [MemberController::class, 'duplicates'])->name('members.duplicates');
    Route::get('/members/{member}/children', [MemberController::class, 'children'])->whereNumber('member')->name('members.children');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::put('/members/{member}', [MemberController::class, 'update'])->whereNumber('member')->name('members.update');
    Route::post('/members/{member}/move', [MemberController::class, 'move'])->whereNumber('member')->name('members.move');
    Route::post('/members/{member}/unlink', [MemberController::class, 'unlink'])->whereNumber('member')->name('members.unlink');
    Route::post('/members/reorder', [MemberController::class, 'reorder'])->name('members.reorder');

    Route::get('/leadership/export.csv', [LeadershipController::class, 'export'])->name('leadership.export');
    Route::get('/elders/export.csv', [ElderController::class, 'export'])->name('elders.export');

    Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
    Route::put('/activities/{event}', [ActivityController::class, 'update'])->name('activities.update');
    Route::post('/activities/{event}/join', [ActivityController::class, 'join'])->name('activities.join');
    Route::delete('/activities/{event}/join', [ActivityController::class, 'leave'])->name('activities.leave');
    Route::post('/activities/{event}/participants', [ActivityController::class, 'addParticipant'])->name('activities.participants.store');
    Route::delete('/activities/{event}/participants/{participant}', [ActivityController::class, 'removeParticipant'])->scopeBindings()->name('activities.participants.destroy');
    Route::post('/activities/{event}/media', [ActivityController::class, 'uploadMedia'])->name('activities.media.store');
    Route::delete('/activities/{event}/media/{mediaItem}', [ActivityController::class, 'deleteMedia'])->name('activities.media.destroy');

    Route::post('/news/{news}/comments', [NewsController::class, 'comment'])->middleware('throttle:20,1')->name('news.comments.store');
    Route::put('/comments/{comment}', [NewsController::class, 'updateComment'])->name('comments.update');
    Route::delete('/comments/{comment}', [NewsController::class, 'deleteComment'])->name('comments.destroy');

    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('/finance/export.csv', [FinanceController::class, 'export'])->name('finance.export');
    Route::get('/finance/similar', [FinanceController::class, 'similar'])->name('finance.similar');

    Route::get('/fund-contributors', [FundController::class, 'index'])->name('fund.index');
    Route::get('/fund-contributors/export.csv', [FundController::class, 'export'])->name('fund.export');

    Route::get('/donation-tracking', [DonationController::class, 'index'])->name('donations.index');
    Route::get('/donation-tracking/export.csv', [DonationController::class, 'export'])->name('donations.export');
});

/* ---------------- Admin ---------------- */
Route::middleware('access:admin')->group(function () {
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->whereNumber('member')->name('members.destroy');

    Route::post('/leadership', [LeadershipController::class, 'store'])->name('leadership.store');
    Route::put('/leadership/{leadership}', [LeadershipController::class, 'update'])->name('leadership.update');
    Route::delete('/leadership/{leadership}', [LeadershipController::class, 'destroy'])->name('leadership.destroy');
    Route::post('/leadership/reorder', [LeadershipController::class, 'reorder'])->name('leadership.reorder');

    Route::post('/elders', [ElderController::class, 'store'])->name('elders.store');
    Route::put('/elders/{elder}', [ElderController::class, 'update'])->name('elders.update');
    Route::delete('/elders/{elder}', [ElderController::class, 'destroy'])->name('elders.destroy');

    Route::delete('/activities/{event}', [ActivityController::class, 'destroy'])->name('activities.destroy');

    Route::post('/news', [NewsController::class, 'store'])->name('news.store');
    Route::put('/news/{news}', [NewsController::class, 'update'])->name('news.update');
    Route::delete('/news/{news}', [NewsController::class, 'destroy'])->name('news.destroy');
    Route::post('/news/{news}/pin', [NewsController::class, 'pin'])->name('news.pin');
    Route::post('/news/{news}/media', [NewsController::class, 'uploadMedia'])->name('news.media.store');
    Route::delete('/news/{news}/media/{mediaItem}', [NewsController::class, 'deleteMedia'])->name('news.media.destroy');

    Route::post('/finance/transactions', [FinanceController::class, 'store'])->name('finance.store');
    Route::put('/finance/transactions/{transaction}', [FinanceController::class, 'update'])->name('finance.update');
    Route::delete('/finance/transactions/{transaction}', [FinanceController::class, 'destroy'])->name('finance.destroy');

    Route::post('/fund-contributors', [FundController::class, 'store'])->name('fund.store');
    Route::delete('/fund-contributors/{fundMember}', [FundController::class, 'destroy'])->name('fund.destroy');

    Route::post('/donation-tracking/pledges', [DonationController::class, 'storePledge'])->name('donations.pledges.store');
    Route::put('/donation-tracking/pledges/{pledge}', [DonationController::class, 'updatePledge'])->name('donations.pledges.update');
    Route::delete('/donation-tracking/pledges/{pledge}', [DonationController::class, 'destroyPledge'])->name('donations.pledges.destroy');
    Route::post('/donation-tracking/payments', [DonationController::class, 'storePayment'])->name('donations.payments.store');
    Route::put('/donation-tracking/payments/{payment}', [DonationController::class, 'updatePayment'])->name('donations.payments.update');
    Route::delete('/donation-tracking/payments/{payment}', [DonationController::class, 'destroyPayment'])->name('donations.payments.destroy');

    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/admin/users/{user}/approve', [AdminController::class, 'approve'])->name('admin.approve');
    Route::post('/admin/users/{user}/reject', [AdminController::class, 'reject'])->name('admin.reject');
    Route::put('/admin/users/{user}/role', [AdminController::class, 'role'])->name('admin.role');
    Route::put('/admin/users/{user}/member', [AdminController::class, 'linkMember'])->name('admin.link');
    Route::post('/admin/users/{user}/toggle', [AdminController::class, 'toggle'])->name('admin.toggle');
});
