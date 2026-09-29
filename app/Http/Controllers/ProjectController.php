<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Project::class);

        // Scoped to "projects I'm a MEMBER of" (not just "created by me")
        // now that membership exists — this is a *query* scope, separate
        // from the viewAny() gate above: the gate says "you may load this
        // page", the query says "which rows you may see on it".
        //
        // with('owner'): the index view prints $project->owner->name per
        // row. Without eager loading, that's 1 query for the project list
        // + N queries (one per row) to resolve each owner — the classic
        // N+1 pattern (see docs/backend-concepts/eloquent.md, added once
        // Tasks exist for a fuller example). Eager loading collapses that
        // to exactly 2 queries regardless of row count.
        $projects = Auth::user()
            ->projects()
            ->with('owner')
            ->latest('projects.created_at')
            ->paginate(10);

        return view('projects.index', ['projects' => $projects]);
    }

    public function create(): View
    {
        Gate::authorize('create', Project::class);

        return view('projects.create');
    }

    /**
     * Creating a project is really THREE related writes: the project row
     * itself, a project_user row making the creator its Owner, and an
     * activity log entry recording the creation. DB::transaction() makes
     * all three succeed or all three roll back together — see ADR 008 for
     * why this matters and what happens without it (Failure Experiment 3).
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request) {
            $project = Project::create([
                ...$request->validated(),
                'created_by' => Auth::id(),
            ]);

            $project->members()->attach(Auth::id(), ['role' => ProjectRole::Owner->value]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'subject_type' => Project::class,
                'subject_id' => $project->id,
                'description' => Auth::user()->name.' created project "'.$project->name.'".',
            ]);

            return $project;
        });

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Project created.');
    }

    public function show(Project $project): View
    {
        Gate::authorize('view', $project);

        // tasks.assignee: the tasks table on this page prints
        // $task->assignee?->name per row — without eager-loading through
        // the tasks relation, that's N additional queries (one per task)
        // on top of the 1 query for the task list itself.
        $project->load(['members', 'activities.user', 'tasks.assignee']);

        return view('projects.show', ['project' => $project]);
    }

    public function edit(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('projects.edit', ['project' => $project]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        ActivityLog::create([
            'user_id' => Auth::id(),
            'subject_type' => Project::class,
            'subject_id' => $project->id,
            'description' => Auth::user()->name.' updated project "'.$project->name.'".',
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        // System/application log (storage/logs/laravel.log), NOT the same
        // thing as the ActivityLog row above — this is for developers/ops
        // monitoring the running application, not for end users browsing
        // a project's history. 'warning' level (not 'info'): deletion is
        // destructive and cascades to every task/comment/membership row
        // via the migrations' cascadeOnDelete() — worth a human noticing
        // in aggregate log monitoring, unlike routine reads or updates.
        // See docs/backend-concepts/logging.md for the full distinction.
        Log::warning('Project deleted', [
            'user_id' => Auth::id(),
            'project_id' => $project->id,
            'project_name' => $project->name,
        ]);

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('status', 'Project deleted.');
    }
}
