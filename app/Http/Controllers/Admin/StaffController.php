<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('admin.staff', ['users' => User::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower(input_string($request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['required', Password::min(10)],
        ]);
        User::create(['name' => $data['name'], 'email' => $data['email'], 'role' => $data['role'], 'password' => Hash::make($data['password'])]);

        return back()->with('status', "Staff account created for {$data['email']}. Share the password with them in person.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::enum(Role::class)]]);
        if ($user->is($request->user()) && $data['role'] !== Role::Admin->value) {
            return back()->withErrors(['role' => 'You cannot remove your own administrator role.']);
        }
        $user->update($data);

        return back()->with('status', "{$user->name} is now {$user->role->label()}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }
        $user->delete();

        return back()->with('status', 'Staff account removed.');
    }
}
