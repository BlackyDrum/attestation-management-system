<?php

namespace App\Http\Controllers;

use App\Models\Attestation;
use App\Models\AttestationFields;
use App\Models\Semester;
use App\Models\User;
use App\Models\UserHasAttestation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'subjectNumber' => 'required|integer|min:1',
            'subjectName' => 'required|string|max:255',
            'semester' => 'required|exists:semester,semester',
            'attestations' => 'required|array|min:1',
            'attestations.*.title' => 'required|string|max:255',
            'attestations.*.description' => 'nullable|string|max:2500',
        ]);

        $attestation = Attestation::query()->create([
           'subject_number' => $request->input('subjectNumber'),
           'subject_name' => $request->input('subjectName'),
           'current_semester' => (Semester::query()->where('semester','=',$request->input('semester'))->first())->id,
           'creator_id' => Auth::id(),
        ]);

        foreach ($request->input('attestations') as $field) {
            AttestationFields::query()->create([
                'attestation_id' => $attestation['id'],
                'title' => $field['title'],
                'description' => $field['description']
            ]);
        }

        foreach ($request->input('users') as $user) {
            UserHasAttestation::query()->create([
                'user_id' => $user['id'],
                'attestation_id' => $attestation['id']
            ]);
        }
    }
}
