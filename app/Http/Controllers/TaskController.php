<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    /**
     * Global task list across every project the user is a member of,
     * with query-string filtering/sorting per the spec:
     * /tasks?status=in_progress&priority=high&assignee=3&sort=due_date
     *
     * Each filter is applied only if present — an absent query param
     * means "don't filter on this," not "filter for null/empty."
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Task::class);

        $projectIds = Auth::user()->projects()->pluck('projects.id');

        $query = Task::query()
            ->whereIn('project_id', $projectIds)
            ->with(['project', 'assignee']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        if ($request->filled('assignee')) {
            $query->where('assigned_to', $request->integer('assignee'));
        }

        // Whitelist sortable columns — never interpolate the raw query
        // param into ORDER BY directly, since that would let a request
        // control arbitrary SQL (e.g. ?sort=1;DROP TABLE tasks or simply
        // an invalid/expensive expression). due_date and priority are the
        // two columns the spec calls out; created_at is the default.
        $sort = match ($request->string('sort')->toString()) {
            'due_date' => 'due_date',
            'priority' => 'priority',
            default => 'created_at',
        };
        $query->orderBy($sort, $sort === 'due_date' ? 'asc' : 'desc');

        $tasks = $query->paginate(15)->withQueryString();

        return view('tasks.index', [
            'tasks' => $tasks,
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
        ]);
    }

    public function create(Project $project): View
    {
        Gate::authorize('create', [Task::class, $project]);

        $project->load('members');

        return view('tasks.create', [
            'project' => $project,
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
        ]);
    }

    public function store(StoreTaskRequest $request, Project $project): RedirectResponse
    {
        $task = Task::create([
            ...$request->validated(),
            'project_id' => $project->id,
            'created_by' => Auth::id(),
        ]);

        // Logged against the TASK (not the project) — the task's own show
        // page displays $task->activities, and a task's lifecycle events
        // belong on the task itself. Project-level activity (created,
        // updated, member added/removed) stays scoped to the project;
        // "a task was deleted" is the one task event still logged against
        // the project below, since the task itself won't exist to view.
        ActivityLog::create([
            'user_id' => Auth::id(),
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'description' => Auth::user()->name.' created task "'.$task->title.'".',
        ]);

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'Task created.');
    }

    public function show(Task $task): View
    {
        Gate::authorize('view', $task);

        $task->load(['project.members', 'assignee', 'creator', 'activities.user', 'comments.user']);

        return view('tasks.show', ['task' => $task]);
    }

    public function edit(Task $task): View
    {
        Gate::authorize('update', $task);

        $task->load('project.members');

        return view('tasks.edit', [
            'task' => $task,
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
        ]);
    }

    /**
     * Logs a distinct activity message when the status specifically
     * changes (matching the spec's exact example: "John changed Task #15
     * status from 'todo' to 'completed'"), separate from the generic
     * "updated" message for other field changes — a status change is the
     * one field users most want a readable history of.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $previousStatus = $task->status;
        $previousAssignee = $task->assigned_to;

        $task->update($request->validated());

        if ($task->status !== $previousStatus) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'description' => Auth::user()->name.' changed task "'.$task->title.'" status from "'
                    .$previousStatus->label().'" to "'.$task->status->label().'".',
            ]);
        }

        if ($task->assigned_to !== $previousAssignee) {
            $assigneeName = $task->assignee?->name ?? 'nobody';
            ActivityLog::create([
                'user_id' => Auth::id(),
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'description' => Auth::user()->name.' assigned task "'.$task->title.'" to '.$assigneeName.'.',
            ]);
        }

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $projectId = $task->project_id;
        $title = $task->title;
        $task->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'subject_type' => Project::class,
            'subject_id' => $projectId,
            'description' => Auth::user()->name.' deleted task "'.$title.'".',
        ]);

        return redirect()
            ->route('projects.show', $projectId)
            ->with('status', 'Task deleted.');
    }
}
