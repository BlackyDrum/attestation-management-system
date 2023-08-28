<?php

namespace App\Http\Controllers;

use App\Events\NotificationEvent;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Attestation;
use App\Models\AttestationTasks;
use App\Models\Semester;
use App\Models\User;
use App\Models\UserCanAccessAdditionalAttestation;
use App\Models\UserHasAttestation;
use App\Models\UserHasCheckedTask;
use App\Rules\CheckTitle;
use App\Rules\NoDuplicateTitle;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AttestationController extends Controller
{
    private string $finalAttestation;

    public function __construct()
    {
        $this->finalAttestation = env('FINAL_ATTESTATION_NAME','Final attestation');
    }

    public function show(Request $request)
    {
        $attestationQuery = AttestationController::createQuery();

        if (!Auth::user()->admin) {
            $tmp = UserCanAccessAdditionalAttestation::query()->where('user_id', '=', Auth::id())->select(['attestation_id'])->get();
            $attestationQuery->whereIn('attestation.id',$tmp)->orWhere('attestation.creator_id', '=', Auth::id());
        }

        $attestations = $attestationQuery->get();

        return Inertia::render('Attestations', [
            'users' => User::all(),
            'semester' => Semester::query()->orderBy('id', 'DESC')->limit(5)->get(),
            'attestations' => $attestations,
        ]);
    }

    public function create(Request $request)
    {
        $this->validateRequest($request);

        $attestation = Attestation::query()->create([
            'subject_number' => $request->input('subjectNumber'),
            'subject_name' => $request->input('subjectName'),
            'acronym' => $request->input('acronym'),
            'semester_id' => (Semester::query()->where('semester', '=', $request->input('semester'))->first())->id,
            'creator_id' => Auth::id(),
        ]);

        DB::beginTransaction();

        foreach ($request->input('attestations') as $task) {
            $f[] = AttestationTasks::query()->create([
                'attestation_id' => $attestation['id'],
                'title' => $task['title'],
                'description' => $task['description']
            ]);
        }

        // It's important to create the final attestation after creating all tasks,
        // ensuring the highest ID for the final attestation
        $finalAttestation = AttestationTasks::query()->create([
            'attestation_id' => $attestation['id'],
            'title' => $this->finalAttestation,
        ]);

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

            UserHasCheckedTask::query()->create([
                'user_id' => $user['id'],
                'task_id' => $finalAttestation['id'],
                'editor_id' => null,
            ]);
        }

        DB::commit();

        return to_route('attestations');
    }

    public function edit(Request $request)
    {
        $this->validateRequest($request);

        $request->validate([
            'id' => 'required|integer|exists:attestation,id',
            'attestations.*.task_id' => 'nullable|integer',
        ],[
            'id.*' => "The selected attestation id is invalid"
        ]);

        $attestation = Attestation::query()->find($request->input('id'));
        if (!Auth::user()->admin && $attestation->creator_id !== Auth::id()) {
            try {
                UserCanAccessAdditionalAttestation::query()
                    ->where('user_id', '=', Auth::id())
                    ->where('attestation_id', '=', $attestation->id)
                    ->firstOrFail();
            }
            catch (ModelNotFoundException $exception) {
                abort(403, "Forbidden");
            }
        }

        DB::beginTransaction();

        $attestation = Attestation::query()->find($request->input('id'))->fill([
            'subject_number' => $request->input('subjectNumber'),
            'subject_name' => $request->input('subjectName'),
            'acronym' => $request->input('acronym'),
            'semester_id' => (Semester::query()->where('semester', '=', $request->input('semester'))->first())->id,
        ]);

        $attestation->save();

        $tasks = [];
        foreach ($request->input('attestations') as $task) {
            // Save all tasks currently assigned to this subject
            $tasks[] = AttestationTasks::query()->findOrNew($task['task_id'])->fill([
                'attestation_id' => $attestation['id'],
                'title' => $task['title'],
                'description' => $task['description']
            ]);
        }

        $ids = [];
        foreach ($tasks as $item) {
            // Save all task ID's currently assigned to this subject
            $item->save();
            $ids[] = $item['id'];
        }

        // Get the current 'final attestation' for this subject and save the associated 'user_has_checked_task'-rows
        $checkedFinalAttestation = AttestationTasks::query()->where('attestation_id', '=', $attestation['id'])
            ->where('title', '=', $this->finalAttestation)
            ->join('user_has_checked_task', 'user_has_checked_task.task_id', '=', 'attestation_tasks.id')
            ->get();

        // Create a new 'final attestation' for this subject. We need to do that because we want to
        // ensure it consistently holds the highest ID among all tasks for a subject.
        $newFinalAttestation = AttestationTasks::query()->create([
            'attestation_id' => $attestation['id'],
            'title' => $this->finalAttestation,
        ]);
        $ids[] = $newFinalAttestation->id;

        foreach ($checkedFinalAttestation as $item) {
            // Create the 'user_has_checked_task'-rows using the existing data,
            // while substituting the 'task_id' with the new 'final attestation' ID.
            // This way we ensure that no data is lost during the creation of a new
            // 'final attestation'.
            UserHasCheckedTask::query()->create([
                'user_id' => $item->user_id,
                'task_id' => $newFinalAttestation->id,
                'checked' => $item->checked
            ]);
        }

        // Delete the old 'final attestation'
        AttestationTasks::query()->where('attestation_id', '=', $attestation['id'])
            ->where('title', '=', $this->finalAttestation)
            ->first()->delete();

        // Remove any tasks that are unassigned to this subject, often occurring
        // when a user deletes a task on the client side.
        AttestationTasks::query()->where('attestation_id', '=', $attestation['id'])
            ->whereNotIn('id', $ids)
            ->delete();

        $uids = [];
        foreach ($request->input('users') as $user) {
            // Save all user ID's currently assigned to this subject
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

            // Ensure that new users assigned to this subject are
            // provided a reference to the recently generated 'final attestation'.
            UserHasCheckedTask::query()->firstOrCreate([
                'user_id' => $user['id'],
                'task_id' => $newFinalAttestation->id,
            ]);

            foreach ($tasks as $item) {
                UserHasCheckedTask::query()->firstOrCreate([
                    'user_id' => $user['id'],
                    'task_id' => $item['id'],
                ]);
            }
        }

        // Remove all user's not assigned to this subject, often occurring
        // when an admin removes users on client side.
        UserHasAttestation::query()->where('attestation_id', '=', $attestation['id'])->whereNotIn('user_id', $uids)->delete();

        UserHasCheckedTask::query()->join('attestation_tasks', 'attestation_tasks.id', '=', 'user_has_checked_task.task_id')
            ->where('attestation_tasks.attestation_id', '=', $attestation['id'])->whereNotIn('user_id', $uids)->delete();

        DB::commit();

        return to_route('attestations');
    }

    public function delete(Request $request)
    {
        $request->validate([
            'attestation_id' => 'required|integer|exists:attestation,id'
        ]);

        $this->checkIncludedUser(Attestation::query()->find($request->input('attestation_id')));

        Attestation::query()->find($request->input('attestation_id'))->delete();

        return response()->json(['success' => true, 'attestation_id' => $request->input('attestation_id')]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:attestation,id',
            'userfile' => 'required|mimes:csv,txt|mimetypes:text/csv,text/plain|max:10000', // in kilobytes
        ],[
            'id.*' => 'Invalid subject'
        ]);

        $attestation = Attestation::query()->find($request->input('id'));

        if ($handle = fopen($request->file('userfile'), "r")) {
            $firstRowSkipped = false;

            DB::beginTransaction();

            while ($data = fgetcsv($handle, 1000)) {
                if (count($data) !== 1) {
                    DB::rollBack();
                    return to_route('attestations')->withErrors([
                        'message' => 'Incorrect file format. Please ensure there is only one column dedicated to the user\'s matriculation number.',
                    ]);
                }

                if (!$firstRowSkipped) {
                    $firstRowSkipped = true;
                    continue;
                }

                $validator = Validator::make(['matriculation_number' => $data[0]], [
                    'matriculation_number' => 'required|integer|exists:users,matriculation_number',
                ]);

                if ($validator->fails()) {
                    DB::rollBack();
                    if (!empty($validator->failed()['matriculation_number']['Exists'])) {
                        $validator->errors()->forget('matriculation_number');
                        $validator->errors()->add('matriculation_number', "The matriculation number '{$data[0]}' does not exist.");
                    }
                    return to_route('attestations')->withErrors($validator->errors()->all());
                }

                $user = User::query()->where('matriculation_number', '=', $data[0])->first();

                $userHasAttestation = UserHasAttestation::query()->firstOrCreate([
                    'user_id' => $user->id,
                    'attestation_id' => $request->input('id')
                ]);

                $semester = Semester::query()->find($attestation->semester_id)->semester;
                if ($userHasAttestation->wasRecentlyCreated) {
                    event(new NotificationEvent($user->id));

                    Redis::command('LPUSH', ["users:{$user->id}:notifications", "INFO|You have been assigned to the subject '{$attestation->subject_name}'({$attestation->subject_number}) for the {$semester}.|" . date('Y-m-d') . ' ' . date('h:i:sa')]);
                }

                $tasks = AttestationTasks::query()->where('attestation_id', '=', $request->input('id'))->get();

                foreach ($tasks as $task) {
                    UserHasCheckedTask::query()->firstOrCreate([
                        'user_id' => $user->id,
                        'task_id' => $task->id
                    ]);
                }
            }

            DB::commit();

            fclose($handle);
        }

        return to_route('attestations');
    }

    public function include_users_to_attestation(Request $request)
    {
        $request->validate([
            'attestation_id' => 'required|integer|exists:attestation,id',
            'users' => 'required|array|min:1',
            'users.*.id' => 'required|integer|exists:users,id'
        ],[
            'users.*.id.*' => 'The selected User is invalid or does not exist.'
        ]);

        $subject = Attestation::query()->find($request->input('attestation_id'));

        if (!Auth::user()->admin && $subject->creator_id !== Auth::id()) {
            abort(403, "Forbidden");
        }

        foreach ($request->input('users') as $user) {
            UserCanAccessAdditionalAttestation::query()->firstOrCreate([
                'attestation_id' => $request->input('attestation_id'),
                'user_id' => $user['id'],
            ]);
        }

        return to_route('attestations');
    }

    public static function checkIncludedUser($attestation)
    {
        if (!Auth::user()->admin && $attestation->creator_id !== Auth::id()) {
            try {
                UserCanAccessAdditionalAttestation::query()
                    ->where('user_id', '=', Auth::id())
                    ->where('attestation_id', '=', $attestation->id)
                    ->firstOrFail();
            }
            catch (ModelNotFoundException $exception) {
                abort(403, "Forbidden");
            }
        }
    }

    private function validateRequest(Request $request)
    {
        $request->validate([
            'users' => 'nullable|array',
            'users.*.id' => 'required|exists:users,id',
            'subjectNumber' => 'required|integer|min:1',
            'subjectName' => 'required|string|max:255',
            'acronym' => 'required|string|max:8',
            'semester' => 'required|exists:semester,semester',
            'attestations' => ['required', 'array', 'min:1', new NoDuplicateTitle],
            'attestations.*.title' => ['required', 'string', 'max:50', new CheckTitle],
            'attestations.*.description' => 'nullable|string|max:5000',
        ], [
            'users.*.id.exists' => "The selected user is invalid or does not exist.",
            'attestations.*.title.required' => "The title field is required.",
            'attestations.*.title.max' => "The title field must not be greater than :max characters.",
            'attestations.*.description.max' => "The description field must not be greater than :max characters."
        ]);
    }

    public static function createQuery(int $id = null) {

        $order = config('database.default') === 'pgsql' ?
            "SPLIT_PART(users.name,' ', -1)" :
            "SUBSTRING_INDEX(users.name, ' ', -1)";

        $attestationQuery = $id ? Attestation::query()->where('attestation.id', '=', $id) : Attestation::query();

        $attestationQuery
            ->join('semester', 'attestation.semester_id', '=', 'semester.id')
            ->join('attestation_tasks', 'attestation.id', '=', 'attestation_tasks.attestation_id')
            ->leftJoin('user_has_checked_task', 'user_has_checked_task.task_id', '=', 'attestation_tasks.id')
            ->leftJoin('user_has_attestation', function (JoinClause $join) {
                $join->on('user_has_attestation.user_id', '=', 'user_has_checked_task.user_id')
                    ->on('user_has_attestation.attestation_id', '=', 'attestation.id');
            })
            ->leftJoin('users', 'users.id', '=', 'user_has_attestation.user_id')
            ->leftJoin('users AS editor', 'editor.id', '=', 'user_has_checked_task.editor_id')
            ->orderByRaw($order)
            ->select([
                'attestation.id',
                'attestation.acronym',
                'attestation.subject_name',
                'attestation.subject_number',
                'attestation.creator_id',
                'semester.semester',
                'semester.id AS semester_id',
                'attestation_tasks.title',
                'attestation_tasks.description',
                'user_has_attestation.user_id',
                'users.name',
                'users.matriculation_number',
                'user_has_checked_task.checked',
                'attestation_tasks.id AS task_id',
                'user_has_checked_task.id AS checked_id',
                'user_has_checked_task.updated_at',
            ])
            ->orderBy('attestation_tasks.id');

        $privileges = HandleInertiaRequests::get_privileges();

        if (Auth::user()->admin) {
            $attestationQuery->addSelect([
                'editor.name AS editor_name',
                'user_has_checked_task.editor_id',
            ]);
            return $attestationQuery;
        }

        foreach ($privileges as $privilege)
        {
            if ($privilege['privilege'] === 'can_access_subject_page' && $privilege['checked']) {
                $attestationQuery->addSelect([
                    'editor.name AS editor_name',
                    'user_has_checked_task.editor_id',
                ]);
                break;
            }

        }

        return $attestationQuery;
    }
}
