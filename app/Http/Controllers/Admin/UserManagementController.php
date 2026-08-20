<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    private const MANAGED_ROLES = [
        'Doctor',
        'Nurse',
        'Midwife',
        'Data Encoder',
    ];

    private const ACCOUNT_STATUSES = [
        'ACTIVE',
        'INACTIVE',
    ];

    public function index(Request $request): View
    {
        $users = User::query()
            ->with('role')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->whereHas('role', fn ($query) => $query->where('name', $request->string('role')));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('account_status', $request->string('status'));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $this->manageableRoles(),
            'statuses' => self::ACCOUNT_STATUSES,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => $this->manageableRoles(),
            'statuses' => self::ACCOUNT_STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role_id' => ['required', Rule::in($this->manageableRoles()->pluck('id')->all())],
            'account_status' => ['required', Rule::in(self::ACCOUNT_STATUSES)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'account_status' => $validated['account_status'],
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']),
        ]);

        AuditLog::record('admin.user_created', $request, $request->user(), [
            'target_user_id' => $user->id,
            'target_role' => $user->role?->name,
            'target_status' => $user->account_status,
        ]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'User account created successfully.');
    }

    public function show(User $user): View
    {
        return view('admin.users.show', [
            'managedUser' => $user->load('role'),
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'managedUser' => $user->load('role'),
            'roles' => $this->manageableRoles(),
            'statuses' => self::ACCOUNT_STATUSES,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $manageableRoleIds = $this->manageableRoles()->pluck('id')->all();
        $originalRole = $user->role?->name;
        $originalStatus = $user->account_status;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id' => ['required', Rule::in($manageableRoleIds)],
            'account_status' => ['required', Rule::in(self::ACCOUNT_STATUSES)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if ($request->user()->is($user)) {
            $validated['role_id'] = $user->role_id;
            $validated['account_status'] = 'ACTIVE';
        }

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'account_status' => $validated['account_status'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->load('role');

        AuditLog::record('admin.user_updated', $request, $request->user(), [
            'target_user_id' => $user->id,
            'original_role' => $originalRole,
            'new_role' => $user->role?->name,
            'original_status' => $originalStatus,
            'new_status' => $user->account_status,
            'password_changed' => ! empty($validated['password']),
        ]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'User account updated successfully.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'account_status' => ['required', Rule::in(self::ACCOUNT_STATUSES)],
        ]);

        if ($request->user()->is($user) && $validated['account_status'] !== 'ACTIVE') {
            return back()->withErrors([
                'account_status' => 'You cannot deactivate your own administrator account.',
            ]);
        }

        $originalStatus = $user->account_status;
        $user->forceFill([
            'account_status' => $validated['account_status'],
        ])->save();

        AuditLog::record(
            $user->account_status === 'ACTIVE' ? 'admin.user_activated' : 'admin.user_deactivated',
            $request,
            $request->user(),
            [
                'target_user_id' => $user->id,
                'original_status' => $originalStatus,
                'new_status' => $user->account_status,
            ],
        );

        return back()->with('status', 'User account status updated successfully.');
    }

    private function manageableRoles()
    {
        return Role::query()
            ->whereIn('name', self::MANAGED_ROLES)
            ->orderByRaw("CASE name WHEN 'Doctor' THEN 1 WHEN 'Nurse' THEN 2 WHEN 'Midwife' THEN 3 WHEN 'Data Encoder' THEN 4 ELSE 5 END")
            ->get();
    }
}
