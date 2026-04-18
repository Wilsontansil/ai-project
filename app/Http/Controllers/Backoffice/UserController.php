<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('roles')->orderBy('name')->get();

        return view('backoffice.users.index', [
            'users' => $users,
            'boActive' => 'users',
        ]);
    }

    public function create(): View
    {
        return view('backoffice.users.create', [
            'roles' => Role::orderBy('name')->get(),
            'boActive' => 'users',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username', 'regex:/^[a-zA-Z0-9_]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'username' => Str::lower(trim($data['username'])),
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($data['role']);

        return redirect()->route('backoffice.users.index')
            ->with('success', __('backoffice.pages.users.created_success'));
    }

    public function edit(User $user): View
    {
        return view('backoffice.users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'boActive' => 'users',
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id), 'regex:/^[a-zA-Z0-9_]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::min(8), 'confirmed'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->update([
            'username' => Str::lower(trim($data['username'])),
            'name' => trim($data['name']),
            'email' => trim($data['email']),
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->syncRoles([$data['role']]);

        return redirect()->route('backoffice.users.index')
            ->with('success', __('backoffice.pages.users.updated_success'));
    }

    public function destroy(User $user): RedirectResponse
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return redirect()->route('backoffice.users.index')
                ->with('error', __('backoffice.pages.users.cannot_delete_self'));
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('backoffice.users.index')
            ->with('success', __('backoffice.pages.users.deleted_success', ['name' => $name]));
    }
}
