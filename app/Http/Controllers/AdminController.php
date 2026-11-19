<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\UserAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin (/admin, ?tab=audit): approvals, roles, member links, account status, audit log. */
class AdminController extends Controller
{
    public function __construct(private UserAdminService $users) {}

    public function index(Request $request): View
    {
        Gate::authorize('admin');
        $tab = $request->query('tab') === 'audit' ? 'audit' : 'users';
        $role = in_array($request->query('role'), ['member', 'admin'], true) ? $request->query('role') : '';

        $pending = User::where('role', 'pending')->whereNull('rejected_at')->where('is_disabled', false)->orderByDesc('created_at')->get();
        $q = trim((string) $request->query('q'));
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';

        $users = User::with('member')->where('role', '!=', 'pending')
            ->when($role, fn ($w) => $w->where('role', $role))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('display_name', 'like', $like)->orWhere('email', 'like', $like)))
            ->orderByRaw("CASE role WHEN 'admin' THEN 0 ELSE 1 END")->orderBy('display_name')
            ->paginate(20)->withQueryString();

        $audit = AuditLog::with('actor')
            ->when($tab === 'audit' && $request->filled('action'), fn ($w) => $w->where('action', $request->query('action')))
            ->latest('id');

        return view('admin.index', [
            'tab' => $tab,
            'role' => $role,
            'pending' => $pending,
            'suggestions' => $pending->mapWithKeys(fn ($u) => [$u->id => $this->users->suggestions($u)]),
            'users' => $users,
            'counts' => [
                'all' => User::where('role', '!=', 'pending')->count(),
                'member' => User::where('role', 'member')->count(),
                'admin' => User::where('role', 'admin')->count(),
            ],
            'auditRecent' => (clone $audit)->limit(6)->get(),
            'auditPage' => $tab === 'audit' ? $audit->paginate(30)->withQueryString() : null,
            'actions' => AuditLogger::ACTIONS,
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('admin');
        $v = $request->validate(['member_id' => ['nullable', 'integer', 'exists:members,id']]);
        $this->users->approve($user, $v['member_id'] ?? null, $request->user());

        return back()->with('success', 'ອະນຸມັດ '.$user->display_name.' ເປັນສະມາຊິກແລ້ວ');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('admin');
        $this->users->reject($user, $request->user());

        return back()->with('success', 'ປະຕິເສດ '.$user->display_name.' ແລ້ວ');
    }

    public function role(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('admin');
        $v = $request->validate(['role' => ['required', Rule::in(['member', 'admin'])]]);
        $this->users->changeRole($user, $v['role'], $request->user());

        return back()->with('success', 'ປ່ຽນ Role ຂອງ '.$user->display_name.' ແລ້ວ');
    }

    public function linkMember(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('admin');
        $v = $request->validate(['member_id' => ['nullable', 'integer', 'exists:members,id']]);
        $this->users->linkMember($user, $v['member_id'] ?? null, $request->user());

        return back()->with('success', 'ບັນທຶກການເຊື່ອມສະມາຊິກແລ້ວ');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('admin');
        $this->users->toggleDisabled($user, $request->user());

        return back()->with('success', $user->fresh()->is_disabled ? 'ປິດບັນຊີແລ້ວ' : 'ເປີດບັນຊີແລ້ວ');
    }
}
