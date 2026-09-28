<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Project::class);

        // Scoped to "projects I created" — there is no membership concept
        // yet (Project Membership phase adds it). This is a *query* scope,
        // separate from the viewAny() gate above: the gate says "you may
        // load this page", the query says "which rows you may see on it".
        //
        // with('owner'): the index view prints $project->owner->name per
        // row. Without eager loading, that's 1 query for the project list
        // + N queries (one per row) to resolve each owner — the classic
        // N+1 pattern (see docs/backend-concepts/eloquent.md, added once
        // Tasks exist for a fuller example). Eager loading collapses that
        // to exactly 2 queries regardless of row count.
        $projects = Auth::user()
            ->projectsCreated()
            ->with('owner')
            ->latest()
            ->paginate(10);

        return view('projects.index', ['projects' => $projects]);
    }

    public function create(): View
    {
        Gate::authorize('create', Project::class);

        return view('projects.create');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Project created.');
    }

    public function show(Project $project): View
    {
        Gate::authorize('view', $project);

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

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('status', 'Project deleted.');
    }
}
