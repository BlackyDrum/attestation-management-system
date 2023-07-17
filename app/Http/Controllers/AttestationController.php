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

    public function create(Request $request)
    {
        $request->validate([
           'users' => 'required|array|min:1',
            'users.*.id' => 'required|exists:users,id',
            'subjectNumber' => 'required|integer|min:1', // add unique constraint here
            'subjectName' => 'required|string|max:255',
            'semester' => 'required|exists:semester,semester'
        ]);
    }
}
