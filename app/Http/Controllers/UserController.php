<?php

namespace App\Http\Controllers;

use App\Events\NotificationEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function show(Request $request)
    {
        $order = config('database.default') === 'pgsql' ?
            "SPLIT_PART(users.name,' ', -1)" :
            "SUBSTRING_INDEX(users.name, ' ', -1)";

        $user = User::query()->orderByRaw($order)
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->select([
                'users.*',
                'roles.role',
            ])
            ->get();

        return Inertia::render('User', [
            'users' => $user,
            'roles' => Role::all(),
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ], [
            'user_id.*' => 'The selected user is invalid or does not exist.'
        ]);

        $user = User::query()->find($request->input('user_id'));

        if ($user->admin) {
            return response()->json(['success' => false, 'message' => 'You cannot delete an admin account.'],403);
        }

        $user->delete();

        return response()->json(['success' => true, 'user_id' => $request->input('user_id')]);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:users,id',
            'matriculation_number' => ['required', 'integer', Rule::unique('users')->ignore($request->input('id'))],
            'name' => 'required|string|max:50',
            'role' => 'required|array|min:1',
            'role.id' => 'required|integer|exists:roles,id',
            'email' => ['required', 'max:255', 'email', Rule::unique('users')->ignore($request->input('id'))],
            'password' => ['nullable', Rules\Password::defaults()],
        ], [
            'id.*' => 'The selected user is invalid or does not exist.',
            'role.id' => 'The selected role is invalid.'
        ]);

        if (User::query()->find($request->input('id'))->admin && !Auth::user()->admin) {
            return to_route('user')->withErrors(["id" => "You cannot edit an admin account"]);
        }

        $id = $request->input('id');

        $user = User::query()->find($id)->fill([
            'name' => $request->input('name'),
            'role_id' => $request->input('role.id'),
            'matriculation_number' => $request->input('matriculation_number'),
            'email' => $request->input('email'),
        ]);

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        event(new NotificationEvent($id));

        Redis::command('LPUSH', ["users:{$id}:notifications", 'INFO|Your profile information have been updated. Please visit your profile page for more information.|' . date('Y-m-d') . ' ' . date('h:i:sa')]);

        return to_route('user');
    }

    public function create(Request $request)
    {
        $request->validate([
            'matriculation_number' => 'required|integer|unique:users,matriculation_number',
            'name' => 'required|string|max:50,',
            'role' => 'required|array|min:1',
            'role.id' => 'required|integer|exists:roles,id',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', Rules\Password::defaults()],
        ],[
            'role.id' => 'The selected role in invalid.'
        ]);

        User::query()->create([
            'matriculation_number' => $request->input('matriculation_number'),
            'name' => $request->input('name'),
            'role_id' => $request->input('role.id'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        return to_route('user');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'userfile' => 'required|mimes:csv|mimetypes:text/csv|max:10000', // in kilobytes
        ]);

        if ($handle = fopen($request->file('userfile'), "r")) {
            $firstRowSkipped = false;

            DB::beginTransaction();

            while ($data = fgetcsv($handle, 1000)) {
                if (count($data) !== 4) {
                    DB::rollBack();
                    return to_route('user')->withErrors([
                        'message' => 'Incorrect file format. Please ensure there are only columns dedicated to the user\'s matriculation number, name, email and password.',
                    ]);
                }

                if (!$firstRowSkipped) {
                    $firstRowSkipped = true;
                    continue;
                }

                $validator = Validator::make(['matriculation_number' => $data[0],'name' => $data[1], 'email' => $data[2], 'password' => $data[3]], [
                    'matriculation_number' => 'required|integer|unique:users,matriculation_number',
                    'name' => 'required|string|max:50',
                    'email' => 'required|string|email|max:255|unique:users,email',
                    'password' => ['required', Rules\Password::defaults()],
                ]);

                if ($validator->fails()) {
                    DB::rollBack();
                    if (!empty($validator->failed()['email']['Unique'])) {
                        $validator->errors()->forget('email');
                        $validator->errors()->add('email', "The email '{$data[2]}' is already taken.");
                    }
                    if (!empty($validator->failed()['matriculation_number']['Unique'])) {
                        $validator->errors()->forget('matriculation_number');
                        $validator->errors()->add('matriculation_number', "The matriculation number '{$data[0]}' is already taken.");
                    }
                    return to_route('user')->withErrors($validator->errors()->all());
                }

                User::query()->create([
                    'matriculation_number' => $data[0],
                    'name' => $data[1],
                    'email' => $data[2],
                    'password' => Hash::make($data[3]),
                    'role_id' => Role::query()->where('role', 'ILIKE', 'Student')->first()->id,
                ]);
            }

            DB::commit();

            fclose($handle);
        }

        return to_route('user');
    }
}
