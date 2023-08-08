<?php

namespace App\Http\Controllers;

use App\Events\NotificationEvent;
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
            'matriculation_number' => ['required', 'integer', Rule::unique('users')->ignore($request->input('id'))],
            'name' => 'required|string|max:50',
            'email' => ['required', 'max:255', 'email', Rule::unique('users')->ignore($request->input('id'))],
            'password' => ['nullable', Rules\Password::defaults()],
        ]);

        $id = $request->input('id');
        $matriculation_number = $request->input('matriculation_number');
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::query()->find($id)->fill([
            'name' => $name,
            'matriculation_number' => $matriculation_number,
            'email' => $email,
        ]);

        if ($request->filled('password')) {
            $user->password = Hash::make($password);
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
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', Rules\Password::defaults()],
        ]);

        User::query()->create([
            'matriculation_number' => $request->input('matriculation_number'),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        return to_route('user');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'userfile' => 'required|mimes:csv|mimetypes:text/csv|max:1000000',
        ]);

        if ($handle = fopen($request->file('userfile'), "r")) {
            $firstRowSkipped = false;

            DB::beginTransaction();

            while ($data = fgetcsv($handle, 1000)) {
                if (count($data) !== 4) {
                    DB::rollBack();
                    return to_route('user')->withErrors([
                        'message' => 'Wrong file format',
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
                ]);
            }

            DB::commit();

            fclose($handle);
        }

        return to_route('user');

    }
}
