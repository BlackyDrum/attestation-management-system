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
        return Inertia::render('User', [
            'users' => User::query()->orderByRaw("SPLIT_PART(name,' ', -1)")->paginate(20)
        ]);
    }
}
