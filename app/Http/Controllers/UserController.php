<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function show(Request $request)
    {
        $search = $request->input('search') ?? "";

        $order = config('database.default') === 'pgsql' ?
            "SPLIT_PART(users.name,' ', -1)" :
            "SUBSTRING_INDEX(users.name, ' ', -1)";

        $user = User::query()->where('name', 'ILIKE', '%' . $search . '%')->orderByRaw($order)->paginate(20);

        if (!empty($request->input('response')) && $request->input('response')) {
            return response()->json($user);
        }

        return Inertia::render('User', [
            'users' => $user,
            'search' => $search
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        User::query()->where('id', '=', $request->input('user_id'))->where('admin', '=', 'false')->delete();

        return response()->json(['success' => true, 'user_id' => $request->input('user_id')]);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:25',
            'email' => ['required', 'max:255', 'email', Rule::unique('users')->ignore($request->input('id'))],
            'password' => ['nullable', Rules\Password::defaults()],
        ]);

        $id = $request->input('id');
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::query()->find($id)->fill([
            'name' => $name,
            'email' => $email,
        ]);

        if ($request->filled('password')) {
            $user->password = Hash::make($password);
        }

        $user->save();

        return to_route('user');
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:25,',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', Rules\Password::defaults()],
        ]);

        User::query()->create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        return to_route('user');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'userfile' => 'mimes:csv|mimetypes:text/csv|max:1000000',
        ]);

        if ($handle = fopen($request->file('userfile'), "r")) {
            $firstRowSkipped = false;

            DB::beginTransaction();

            while ($data = fgetcsv($handle, 1000)) {
                if (!$firstRowSkipped) {
                    $firstRowSkipped = true;
                    continue;
                }

                $validator = Validator::make(['name' => $data[0], 'email' => $data[1], 'password' => $data[2]], [
                    'name' => 'required|string|max:25',
                    'email' => 'required|string|email|max:255|unique:users,email',
                    'password' => ['required', Rules\Password::defaults()],
                ]);

                if ($validator->fails()) {
                    DB::rollBack();
                    if (!empty($validator->failed()['email']['Unique'])) {
                        $validator->errors()->forget('email');
                        $validator->errors()->add('email', "The email '{$data[1]}' is already taken.");
                    }
                    return to_route('user')->withErrors($validator->errors()->all());
                }

                User::query()->create([
                    'name' => $data[0],
                    'email' => $data[1],
                    'password' => Hash::make($data[2]),
                ]);
            }

            DB::commit();

            fclose($handle);
        }

        return to_route('user');

    }
}
