<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Http\Requests\StoreProjectMemberRequest;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ProjectMemberController extends Controller
{
    public function index(Project $project): View
    {
        Gate::authorize('viewMembers', $project);

        $project->load('members');

        return view('projects.members', ['project' => $project]);
    }

    public function store(StoreProjectMemberRequest $request, Project $project): RedirectResponse
    {
        $user = User::where('email', $request->validated('email'))->firstOrFail();
        $role = $request->validated('role');

        $project->members()->attach($user->id, ['role' => $role]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'subject_type' => Project::class,
            'subject_id' => $project->id,
            'description' => Auth::user()->name." added {$user->name} to \"{$project->name}\" as ".ProjectRole::from($role)->label().'.',
        ]);

        return redirect()
            ->route('projects.members.index', $project)
            ->with('status', 'Member added.');
    }

    public function destroy(Project $project, User $user): RedirectResponse
    {
        Gate::authorize('manageMembers', $project);

        // Guard against orphaning a project: if this is the last Owner,
        // refuse the removal rather than leaving a project with zero
        // owners (a state ProjectPolicy::delete() could never satisfy
        // again, since it requires an Owner role). This is a business
        // rule — it depends on the state of OTHER membership rows, not
        // just the shape of this request — so it lives here, not in a
        // Form Request's rules().
        $isLastOwner = $project->roleOf($user) === ProjectRole::Owner
            && $project->members()->wherePivot('role', ProjectRole::Owner->value)->count() === 1;

        if ($isLastOwner) {
            return redirect()
                ->route('projects.members.index', $project)
                ->withErrors(['member' => 'Cannot remove the last owner of a project.']);
        }

        // Removing a member is a security-relevant, access-revoking
        // action worth an application log entry (who removed whom, from
        // which project) distinct from the ActivityLog row below — the
        // latter is user-facing project history, this is for anyone
        // auditing access changes at the system level.
        Log::info('Project member removed', [
            'actor_id' => Auth::id(),
            'removed_user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        $project->members()->detach($user->id);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'subject_type' => Project::class,
            'subject_id' => $project->id,
            'description' => Auth::user()->name." removed {$user->name} from \"{$project->name}\".",
        ]);

        return redirect()
            ->route('projects.members.index', $project)
            ->with('status', 'Member removed.');
    }
}
