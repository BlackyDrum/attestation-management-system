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

        $user = User::query()->where('name', 'ILIKE', '%' . $search . '%')->orderByRaw("SPLIT_PART(name,' ', -1)")->paginate(20);

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
            'userid' => 'required',
        ]);

        $userid = $request->input('userid');

        $count = User::query()->where('id', '=', $userid)->where('admin', '=', 'false')->delete();

        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'User ID ' . $userid . ' not found'],404);
        }

        return response()->json(['success' => true, 'userid' => $userid]);
    }

    public function edit(Request $request)
    {
        $request->validate([
           'id' => 'required',
           'name' => 'required|max:255',
           'email' => ['required','max:255','email',Rule::unique('users')->ignore($request->input('id'))],
           'password' => 'max:255|min:6|nullable'
        ]);

        $id = $request->input('id');
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password') ?? null;

        if ($password)
        {
            User::query()->find($id)->fill([
               'name' => $name,
               'email' => $email,
               'password' => Hash::make($password)
            ])->save();

            return to_route('user');
        }

        User::query()->find($id)->fill([
            'name' => $name,
            'email' => $email,
        ])->save();

        return to_route('user');
    }
}
