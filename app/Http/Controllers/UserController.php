<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // ── Single source of truth for superadmin role name ───────────────
    private const SUPERADMIN_ROLE = 'superadmin'; // matches seeder

    private function isSuperAdmin(User $user): bool
    {
        return $user->id === 1 || $user->hasRole(self::SUPERADMIN_ROLE);
    }

    // ─────────────────────────────────────────────────────────────────

    public function index()
    {
        $users = User::with('roles')->orderBy('id')->get();
        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'username'              => 'required|string|max:100|unique:users,username',
            'password'              => 'required|string|min:6|confirmed',
            'role'                  => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'username'  => $request->username,
            'password'  => Hash::make($request->password),
            'is_active' => true,
        ]);

        $role = Role::findById($request->role);
        $user->assignRole($role);

        Log::info('[Users] Created', ['id' => $user->id, 'by' => auth()->id()]);

        return redirect()->route('users.index')
            ->with('success', 'User "' . $user->name . '" created successfully.');
    }

    public function show($id)
    {
        $user = User::with('roles:id,name')->findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'roles'    => $user->roles->map(fn($r) => ['id' => $r->id, 'name' => $r->name]),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username,' . $id,
            'role'     => 'required|exists:roles,id',
        ]);

        $user = User::findOrFail($id);

        try {
            $user->update($request->only(['name', 'username']));

            $role = Role::findById($request->role);
            $user->syncRoles([$role->name]);

            Log::info('[Users] Updated', ['id' => $user->id, 'by' => auth()->id()]);

            return redirect()->route('users.index')
                ->with('success', 'User updated successfully.');

        } catch (\Exception $e) {
            Log::error('[Users] Update error', ['id' => $id, 'msg' => $e->getMessage()]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating user: ' . $e->getMessage());
        }
    }

    public function changePassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::findOrFail($id);

        if ($this->isSuperAdmin($user)) {
            return redirect()->back()
                ->with('error', 'Cannot change the superadmin password from here.');
        }

        $user->update(['password' => Hash::make($request->password)]);

        Log::info('[Users] Password changed', ['id' => $user->id, 'by' => auth()->id()]);

        return redirect()->route('users.index')
            ->with('success', 'Password changed successfully.');
    }

    public function toggleActive($id)
    {
        $user = User::findOrFail($id);

        if ($this->isSuperAdmin($user)) {
            return redirect()->back()
                ->with('error', 'Cannot deactivate the superadmin account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        Log::info('[Users] Toggled active', ['id' => $user->id, 'status' => $status, 'by' => auth()->id()]);

        return redirect()->back()
            ->with('success', "User {$status} successfully.");
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($this->isSuperAdmin($user)) {
            return redirect()->back()
                ->with('error', 'Cannot delete the superadmin account.');
        }

        $user->delete();

        Log::info('[Users] Deleted', ['id' => $id, 'by' => auth()->id()]);

        return redirect()->back()
            ->with('success', 'User deleted successfully.');
    }
}