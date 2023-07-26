<?php

namespace App\Http\Controllers;

use App\Models\Attestation;
use App\Models\AttestationTasks;
use App\Models\Semester;
use App\Models\User;
use App\Models\UserHasAttestation;
use App\Models\UserHasCheckedTask;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AttestationController extends Controller
{
    public function show(Request $request)
    {
        $order = config('database.default') === 'pgsql' ?
            "SPLIT_PART(users.name,' ', -1)" :
            "SUBSTRING_INDEX(users.name, ' ', -1)";

        $attestationQuery = Attestation::query()
            ->join('semester', 'attestation.current_semester', '=', 'semester.id')
            ->join('attestation_tasks', 'attestation.id', '=', 'attestation_tasks.attestation_id')
            ->join('user_has_checked_task', 'user_has_checked_task.task_id', '=', 'attestation_tasks.id')
            ->join('user_has_attestation', function ($join) {
                $join->on('user_has_attestation.user_id', '=', 'user_has_checked_task.user_id')
                    ->on('user_has_attestation.attestation_id', '=', 'attestation.id');
            })
            ->join('users', 'users.id', '=', 'user_has_attestation.user_id')
            ->orderByRaw($order)
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
            ->orderBy('attestation_tasks.id');

        if (!Auth::user()->admin) {
            $attestationQuery->where('users.id', '=', Auth::id());
        }

        $attestations = $attestationQuery->get();

        return Inertia::render('Attestations', [
            'users' => Auth::user()->admin ? User::all() : [],
            'semester' => Auth::user()->admin ? Semester::all() : [],
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

        foreach ($request->input('attestations') as $task) {
            $f[] = AttestationTasks::query()->create([
                'attestation_id' => $attestation['id'],
                'title' => $task['title'],
                'description' => $task['description']
            ]);
        }

        foreach ($request->input('users') as $user) {
            UserHasAttestation::query()->create([
                'user_id' => $user['id'],
                'attestation_id' => $attestation['id']
            ]);

            foreach ($f as $item) {
                UserHasCheckedTask::query()->create([
                    'user_id' => $user['id'],
                    'task_id' => $item['id']
                ]);
            }
        }

        return to_route('attestations');
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:attestation,id',
            'users' => 'required|array|min:1',
            'users.*.id' => 'required|exists:users,id',
            'subjectNumber' => 'required|integer|min:1',
            'subjectName' => 'required|string|max:255',
            'semester' => 'required|exists:semester,semester',
            'attestations' => 'required|array|min:1',
            'attestations.*.title' => 'required|string|max:255',
            'attestations.*.description' => 'nullable|string|max:2500',
            'attestations.*.task_id' => 'nullable',
        ]);

        $attestation = Attestation::query()->find($request->input('id'))->fill([
            'subject_number' => $request->input('subjectNumber'),
            'subject_name' => $request->input('subjectName'),
            'current_semester' => (Semester::query()->where('semester','=',$request->input('semester'))->first())->id,
        ]);

        $attestation->save();

        $tasks = [];
        foreach ($request->input('attestations') as $task) {
            $tasks[] = AttestationTasks::query()->findOrNew($task['task_id'])->fill([
                'attestation_id' => $attestation['id'],
                'title' => $task['title'],
                'description' => $task['description']
            ]);
        }

        $ids = [];
        foreach ($tasks as $item) {
            $item->save();
            $ids[] = $item['id'];
        }

        AttestationTasks::query()->where('attestation_id','=',$attestation['id'])->whereNotIn('id',$ids)->delete();

        $uids = [];
        foreach ($request->input('users') as $user) {
            $uids[] = $user['id'];
            UserHasAttestation::query()->firstOrCreate([
                'user_id' => $user['id'],
                'attestation_id' => $attestation['id']
            ]);
            foreach ($tasks as $item) {
                UserHasCheckedTask::query()->firstOrCreate([
                    'user_id' => $user['id'],
                    'task_id' => $item['id']
                ]);
            }
        }

        UserHasAttestation::query()->where('attestation_id','=',$attestation['id'])->whereNotIn('user_id',$uids)->delete();

        UserHasCheckedTask::query()->join('attestation_tasks','attestation_tasks.id','=','user_has_checked_task.task_id')
            ->where('attestation_tasks.attestation_id','=',$attestation['id'])->whereNotIn('user_id',$uids)->delete();

        return to_route('attestations');
    }

    public function delete(Request $request)
    {
        $request->validate([
            'attestation_id' => 'required|integer|exists:attestation,id'
        ]);

        Attestation::query()->find($request->input('attestation_id'))->delete();

        return response()->json(['success' => true, 'attestation_id' => $request->input('attestation_id')]);
    }
}
