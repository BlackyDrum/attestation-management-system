<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserController extends Controller
{
    public function show(Request $request)
    {
        $search = $request->input('search') ?? "";

        return Inertia::render('User', [
            'users' => User::query()->where('name', 'ILIKE','%'.$search.'%')->orderByRaw("SPLIT_PART(name,' ', -1)")->paginate(20),
            'search' => $search
        ]);
    }

    public function getUserBySearch(Request $request)
    {
        $search = $request->input('search') ?? "";

        $user = User::query()->where('name', 'ILIKE','%'.$search.'%')->orderByRaw("SPLIT_PART(name,' ', -1)")->paginate(20);

        return response()->json($user);
    }
}
