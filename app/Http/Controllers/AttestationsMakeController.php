<?php

namespace App\Http\Controllers;

use App\Models\Attestation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttestationsMakeController extends Controller
{
    public function show(Request $request, int $id)
    {
        try {
            Attestation::query()->findOrFail($id);
        }
        catch (ModelNotFoundException $exception) {
            abort(404);
        }

        $attestations = Attestation::query()
            ->where('attestation.id','=',$id)
            ->join('semester', 'attestation.current_semester', '=', 'semester.id')
            ->join('attestation_tasks', 'attestation.id', '=', 'attestation_tasks.attestation_id')
            ->join('user_has_checked_task', 'user_has_checked_task.task_id', '=', 'attestation_tasks.id')
            ->join('user_has_attestation', function ($join) {
                $join->on('user_has_attestation.user_id', '=', 'user_has_checked_task.user_id')
                    ->on('user_has_attestation.attestation_id', '=', 'attestation.id');
            })
            ->join('users', 'users.id', '=', 'user_has_attestation.user_id')
            ->orderByRaw("SPLIT_PART(users.name,' ', -1)")
            ->select([
                'attestation.id',
                'attestation.subject_name',
                'attestation.subject_number',
                'attestation.creator_id',
                'semester.semester',
                'attestation_tasks.title',
                'attestation_tasks.description',
                'user_has_attestation.user_id',
                'users.name',
                'user_has_checked_task.checked',
                'attestation_tasks.id AS task_id',
                'user_has_checked_task.id AS checked_id',
            ])
            ->orderBy('attestation_tasks.id')
            ->get();

        return Inertia::render('AttestationsMake', [
            'attestations' => $attestations,
            'id' => $id,
        ]);
    }
}
