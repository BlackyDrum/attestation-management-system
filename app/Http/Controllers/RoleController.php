<?php

namespace App\Http\Controllers;

use App\Models\Privileges;
use App\Models\Role;
use App\Models\RoleHasPrivilege;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function show(Request $request)
    {
        $privileges = Role::query()
            ->join('role_has_privilege', 'roles.id', '=', 'role_has_privilege.role_id')
            ->join('privileges', 'privileges.id', '=', 'role_has_privilege.privilege_id')
            ->get();

        return Inertia::render('Roles', [
            'roles' => Role::all(),
            'privileges' => $privileges,
            'role_has_privilege' => RoleHasPrivilege::all(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'privileges' => 'required|array|min:1',
            'privileges.*.checked' => 'required|boolean',
            'privileges.*.privilege' => 'required|string|exists:privileges,privilege',
            'privileges.*.role' => 'required|string|exists:roles,role'
        ]);

        foreach ($request->input('privileges') as $p) {
            $role_id = Role::query()->where('role', '=', $p['role'])->first()->id;
            $privilege_id = Privileges::query()->where('privilege', '=', $p['privilege'])->first()->id;

            RoleHasPrivilege::query()->where('role_id', '=', $role_id)
                ->where('privilege_id', '=', $privilege_id)->update([
                    'checked' => $p['checked']
                ]);
        }

    }
}
