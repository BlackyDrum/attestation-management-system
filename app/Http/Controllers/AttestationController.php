<?php

namespace App\Http\Controllers;

use App\Models\Attestation;
use App\Models\AttestationFields;
use App\Models\Semester;
use App\Models\User;
use App\Models\UserHasAttestation;
use App\Models\UserHasCheckedField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AttestationController extends Controller
{
    public function show(Request $request)
    {
        $attestations = Attestation::query()
            ->join('semester', 'attestation.current_semester', '=', 'semester.id')
            ->join('attestation_fields', 'attestation.id', '=', 'attestation_fields.attestation_id')
            ->join('user_has_checked_field', 'user_has_checked_field.field_id', '=', 'attestation_fields.id')
            ->join('user_has_attestation', function ($join) {
                $join->on('user_has_attestation.user_id', '=', 'user_has_checked_field.user_id')
                    ->on('user_has_attestation.attestation_id', '=', 'attestation.id');
            })
            ->join('users', 'users.id', '=', 'user_has_attestation.user_id')
            ->select([
                'attestation.id',
                'attestation.subject_name',
                'attestation.subject_number',
                'attestation.creator_id',
                'semester.semester',
                'attestation_fields.title',
                'attestation_fields.description',
                'user_has_attestation.user_id',
                'users.name',
                'user_has_checked_field.checked'
            ])
            ->orderBy('attestation_fields.id')
            ->get();


        return Inertia::render('Attestations', [
            'users' => User::all(),
            'semester' => Semester::all(),
            'attestations' => $attestations,
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
            $f[] = AttestationFields::query()->create([
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

            foreach ($f as $item) {
                UserHasCheckedField::query()->create([
                    'user_id' => $user['id'],
                    'field_id' => $item['id']
                ]);
            }
        }

        return to_route('attestations');
    }
}
