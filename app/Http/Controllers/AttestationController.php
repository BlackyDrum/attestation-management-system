<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttestationController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('Attestations', [
            'users' => User::all(),
            'semester' => Semester::all(),
        ]);
    }
}
