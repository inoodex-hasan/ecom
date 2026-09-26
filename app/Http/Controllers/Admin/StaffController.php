<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $selectedRole = $request->input('role');

        $staff = User::query()
            ->with(['roles', 'permissions'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($selectedRole, function ($query, $role) {
                $query->whereHas('roles', fn ($q) => $q->where('name', $role));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $roles = Role::with('permissions')->withCount('users')->get();
        $permissions = Permission::all()->groupBy(function ($perm) {
            return explode('.', $perm->name)[0] ?? 'general';
        });

        return Inertia::render('Admin/Staff/Index', [
            'staff' => $staff,
            'roles' => $roles,
            'groupedPermissions' => $permissions,
            'filters' => [
                'search' => $search,
                'role' => $selectedRole,
            ],
        ]);
    }

    public function create(): Response
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($perm) {
            return explode('.', $perm->name)[0] ?? 'general';
        });

        return Inertia::render('Admin/Staff/Create', [
            'roles' => $roles,
            'groupedPermissions' => $permissions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', 'string', 'exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if ($validated['role'] === 'Super Admin' && ! $request->user()->isAdmin()) {
            abort(403, 'Only Super Admins can create another Super Admin account.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => strtolower(str_replace(' ', '_', $validated['role'])),
        ]);

        $user->assignRole($validated['role']);

        if (! empty($validated['permissions'])) {
            $user->syncPermissions($validated['permissions']);
        }

        return redirect()->route('admin.staff.index')->with('success', "Team member {$user->name} added successfully.");
    }

    public function edit(User $staff): Response
    {
        if ($staff->isAdmin() && ! request()->user()->isAdmin()) {
            abort(403, 'Only Super Admins can edit Super Admin accounts.');
        }

        $staff->load(['roles', 'permissions']);
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($perm) {
            return explode('.', $perm->name)[0] ?? 'general';
        });

        return Inertia::render('Admin/Staff/Edit', [
            'staff' => $staff,
            'roles' => $roles,
            'groupedPermissions' => $permissions,
        ]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        if ($staff->isAdmin() && ! $request->user()->isAdmin()) {
            abort(403, 'Only Super Admins can update Super Admin accounts.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff->id)],
            'password' => ['nullable', Password::defaults()],
            'role' => ['required', 'string', 'exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if ($validated['role'] === 'Super Admin' && ! $request->user()->isAdmin()) {
            abort(403, 'Only Super Admins can assign the Super Admin role.');
        }

        $staff->name = $validated['name'];
        $staff->email = $validated['email'];

        if (! empty($validated['password'])) {
            $staff->password = Hash::make($validated['password']);
        }

        $staff->role = strtolower(str_replace(' ', '_', $validated['role']));
        $staff->save();

        $staff->syncRoles([$validated['role']]);

        if (isset($validated['permissions'])) {
            $staff->syncPermissions($validated['permissions']);
        }

        return redirect()->route('admin.staff.index')->with('success', "Team member {$staff->name} updated successfully.");
    }

    public function destroy(Request $request, User $staff): RedirectResponse
    {
        if ($staff->id === $request->user()->id) {
            return redirect()->back()->with('error', 'You cannot delete your own active administrator account.');
        }

        if ($staff->isAdmin() && ! $request->user()->isAdmin()) {
            abort(403, 'Only Super Admins can delete Super Admin accounts.');
        }

        if ($staff->isAdmin() && User::role('Super Admin')->count() <= 1) {
            return redirect()->back()->with('error', 'Cannot delete the only remaining Super Admin account.');
        }

        $staffName = $staff->name;
        $staff->roles()->detach();
        $staff->permissions()->detach();
        $staff->delete();

        return redirect()->back()->with('success', "Team member {$staffName} deleted successfully.");
    }

    public function updateRolePermissions(Request $request, Role $role): RedirectResponse
    {
        if ($role->name === 'Super Admin') {
            return redirect()->back()->with('error', 'Super Admin permissions cannot be restricted.');
        }

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->back()->with('success', "Permissions for role '{$role->name}' updated successfully.");
    }

    public function bulkUpdateRolePermissions(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'matrix' => ['required', 'array'],
            'matrix.*.role_id' => ['required', 'exists:roles,id'],
            'matrix.*.permissions' => ['nullable', 'array'],
        ]);

        foreach ($validated['matrix'] as $item) {
            $role = Role::find($item['role_id']);
            if ($role) {
                // If Super Admin, keep all permissions
                if ($role->name === 'Super Admin') {
                    $role->syncPermissions(Permission::all());
                } else {
                    $role->syncPermissions($item['permissions'] ?? []);
                }
            }
        }

        return redirect()->back()->with('success', 'Role Permissions Matrix updated successfully.');
    }
}
