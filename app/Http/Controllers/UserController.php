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
        if (!Auth::check() || !Auth::user()->admin)
        {
            abort(403);
        }
        return Inertia::render('User', [
            'users' => User::query()->paginate(20)
        ]);
    }
}
