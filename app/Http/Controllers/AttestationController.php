<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AttestationController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('Attestations');
    }
}
