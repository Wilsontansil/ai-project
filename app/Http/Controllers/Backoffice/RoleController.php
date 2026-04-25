<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();

        return view('backoffice.roles.index', [
            'roles'    => $roles,
            'boActive' => 'roles',
        ]);
    }

    public function create(): View
    {
        return view('backoffice.roles.create', [
            'boActive' => 'roles',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64', 'unique:roles,name', 'regex:/^[a-zA-Z0-9 _\-]+$/'],
        ]);

        Role::create(['name' => trim($data['name']), 'guard_name' => 'web']);

        return redirect()->route('backoffice.roles.index')
            ->with('success', __('backoffice.pages.roles.created_success'));
    }

    public function edit(Role $role): View
    {
        $allPermissions = Permission::orderBy('name')->get();

        return view('backoffice.roles.edit', [
            'role'           => $role,
            'allPermissions' => $allPermissions,
            'rolePerms'      => $role->permissions->pluck('name')->toArray(),
            'boActive'       => 'roles',
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9 _\-]+$/', "unique:roles,name,{$role->id}"],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update(['name' => trim($data['name'])]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('backoffice.roles.index')
            ->with('success', __('backoffice.pages.roles.updated_success'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'admin') {
            return redirect()->route('backoffice.roles.index')
                ->with('error', __('backoffice.pages.roles.cannot_delete_admin'));
        }

        $role->delete();

        return redirect()->route('backoffice.roles.index')
            ->with('success', __('backoffice.pages.roles.deleted_success', ['name' => $role->name]));
    }
}
