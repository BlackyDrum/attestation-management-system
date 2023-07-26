<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

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
           'name' => 'required|string|max:50',
           'email' => ['required','max:255','email',Rule::unique('users')->ignore($request->input('id'))],
           'password' => 'max:255|min:6|nullable'
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
}
