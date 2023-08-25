<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MyAttestationsController extends Controller
{
    public function show(Request $request)
    {
        $attestations = AttestationController::createQuery()->where('users.id', '=', Auth::id())->get();

        return Inertia::render('MyAttestations', [
            'semester' => Semester::query()->orderBy('id', 'DESC')->limit(5)->get(),
            'attestations' => $attestations,
        ]);
    }
}
