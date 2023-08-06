<?php

namespace App\Http\Controllers;

use App\Events\NotificationEvent;
use App\Models\Attestation;
use App\Models\AttestationTasks;
use App\Models\Semester;
use App\Models\User;
use App\Models\UserHasAttestation;
use App\Models\UserHasCheckedTask;
use App\Rules\NoDuplicateTitle;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;

class AttestationController extends Controller
{
    public function show(Request $request)
    {
        $order = config('database.default') === 'pgsql' ?
            "SPLIT_PART(users.name,' ', -1)" :
            "SUBSTRING_INDEX(users.name, ' ', -1)";

        $attestationQuery = Attestation::query()
            ->join('semester', 'attestation.semester_id', '=', 'semester.id')
            ->join('attestation_tasks', 'attestation.id', '=', 'attestation_tasks.attestation_id')
            ->join('user_has_checked_task', 'user_has_checked_task.task_id', '=', 'attestation_tasks.id')
            ->join('user_has_attestation', function ($join) {
                $join->on('user_has_attestation.user_id', '=', 'user_has_checked_task.user_id')
                    ->on('user_has_attestation.attestation_id', '=', 'attestation.id');
            })
            ->join('users', 'users.id', '=', 'user_has_attestation.user_id')
            ->leftJoin('users AS editor', 'editor.id', '=', 'user_has_checked_task.editor_id')
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
                'users.matriculation_number',
                'user_has_checked_task.checked',
                'attestation_tasks.id AS task_id',
                'user_has_checked_task.id AS checked_id',
                'user_has_checked_task.editor_id',
                'user_has_checked_task.updated_at',
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
            'attestations' => ['required', 'array', 'min:1', new NoDuplicateTitle],
            'attestations.*.title' => 'required|string|max:255',
            'attestations.*.description' => 'nullable|string|max:5000',
        ], [
            'users.*.id.exists' => "The selected user is invalid or does not exist",
            'attestations.*.title.required' => "The title field is required",
            'attestations.*.title.max' => "The title field must not be greater than :max characters",
            'attestations.*.description.max' => "The description field must not be greater than :max characters"
        ]);

        $attestation = Attestation::query()->create([
            'subject_number' => $request->input('subjectNumber'),
            'subject_name' => $request->input('subjectName'),
            'semester_id' => (Semester::query()->where('semester', '=', $request->input('semester'))->first())->id,
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

            event(new NotificationEvent($user['id']));

            $semester = Semester::query()->find($attestation->semester_id)->semester;
            Redis::command('LPUSH', ["users:{$user['id']}:notifications", "INFO|You have been assigned to the subject '{$attestation->subject_name}'({$attestation->subject_number}) for the {$semester}.|" . date('Y-m-d') . ' ' . date('h:i:sa')]);

            foreach ($f as $item) {
                UserHasCheckedTask::query()->create([
                    'user_id' => $user['id'],
                    'task_id' => $item['id'],
                    'editor_id' => null,
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
            'attestations' => ['required', 'array', 'min:1', new NoDuplicateTitle],
            'attestations.*.title' => 'required|string|max:255',
            'attestations.*.description' => 'nullable|string|max:5000',
            'attestations.*.task_id' => 'nullable',
        ], [
            'users.*.id.exists' => "The selected user is invalid or does not exist",
            'attestations.*.title.required' => "The title field is required",
            'attestations.*.title.max' => "The title field must not be greater than :max characters",
            'attestations.*.description.max' => "The description field must not be greater than :max characters"
        ]);

        $attestation = Attestation::query()->find($request->input('id'))->fill([
            'subject_number' => $request->input('subjectNumber'),
            'subject_name' => $request->input('subjectName'),
            'semester_id' => (Semester::query()->where('semester', '=', $request->input('semester'))->first())->id,
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

        AttestationTasks::query()->where('attestation_id', '=', $attestation['id'])->whereNotIn('id', $ids)->delete();

        $uids = [];
        foreach ($request->input('users') as $user) {
            $uids[] = $user['id'];
            $userHasAttestation = UserHasAttestation::query()->firstOrCreate([
                'user_id' => $user['id'],
                'attestation_id' => $attestation['id']
            ]);

            $semester = Semester::query()->find($attestation->semester_id)->semester;
            if ($userHasAttestation->wasRecentlyCreated) {
                event(new NotificationEvent($user['id']));

                Redis::command('LPUSH', ["users:{$user['id']}:notifications", "INFO|You have been assigned to the subject '{$attestation->subject_name}'({$attestation->subject_number}) for the {$semester}.|" . date('Y-m-d') . ' ' . date('h:i:sa')]);
            }


            foreach ($tasks as $item) {
                UserHasCheckedTask::query()->firstOrCreate([
                    'user_id' => $user['id'],
                    'task_id' => $item['id'],
                ]);
            }
        }

        UserHasAttestation::query()->where('attestation_id', '=', $attestation['id'])->whereNotIn('user_id', $uids)->delete();

        UserHasCheckedTask::query()->join('attestation_tasks', 'attestation_tasks.id', '=', 'user_has_checked_task.task_id')
            ->where('attestation_tasks.attestation_id', '=', $attestation['id'])->whereNotIn('user_id', $uids)->delete();

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
