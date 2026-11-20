<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $search = trim((string) $request->query('search', ''));
        $roleFilter = (string) $request->query('role', '');
        $statusFilter = (string) $request->query('status', '');

        $users = User::query()
            ->select(['id', 'role_id', 'name', 'email', 'status', 'created_at'])
            ->with('role:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $escaped = addcslashes($search, '%_\\');
                $query->where(function ($subQuery) use ($escaped) {
                    $subQuery->where('name', 'like', "%{$escaped}%")
                        ->orWhere('email', 'like', "%{$escaped}%");
                });
            })
            ->when($roleFilter !== '', function ($query) use ($roleFilter) {
                $query->whereHas('role', fn ($q) => $q->where('name', $roleFilter));
            })
            ->when($statusFilter !== '', function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => RoleName::cases(),
            'statuses' => UserStatus::cases(),
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'status' => $statusFilter,
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $validated = $request->safe()->only(['name', 'email', 'role', 'password']);

        DB::transaction(function () use ($validated, $request): void {
            $role = Role::query()->firstOrCreate([
                'name' => $validated['role'],
            ]);

            $newUser = new User([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $newUser->role_id = $role->id;
            $newUser->status = UserStatus::Active;
            $newUser->save();

            ActivityLogger::record(
                action: 'user.created_by_admin',
                description: "Admin membuat akun {$role->name}: {$newUser->email}",
                subject: $newUser,
                actor: $request->user(),
                request: $request,
            );
        });

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna baru berhasil dibuat.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('toggleStatus', $user);

        $newStatus = $user->isActive() ? UserStatus::Inactive : UserStatus::Active;

        DB::transaction(function () use ($user, $newStatus, $request): void {
            $user->status = $newStatus;
            $user->save();

            if ($newStatus === UserStatus::Inactive) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            ActivityLogger::record(
                action: 'user.status_changed',
                description: "Status akun {$user->email} diubah menjadi {$newStatus->value}.",
                subject: $user,
                actor: $request->user(),
                request: $request,
            );
        });

        $label = $newStatus->label();

        return redirect()->route('admin.users.index')->with('success', "Status akun {$user->name} berhasil diubah menjadi {$label}.");
    }
}
