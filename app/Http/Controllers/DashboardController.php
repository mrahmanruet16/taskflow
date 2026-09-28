<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Real aggregation queries, scoped to the projects the current user
     * is a member of. Deliberately uses count() aggregates (COUNT(*) in
     * SQL) rather than loading full Task collections and counting them
     * in PHP — this is the dashboard, loaded on every login, exactly the
     * page where an accidental "load everything, then ->count()" N+1-
     * adjacent mistake would be most costly at scale. See
     * docs/backend-concepts/eloquent.md for the general N+1 pattern this
     * is avoiding a variant of.
     */
    public function index(): View
    {
        $projectIds = Auth::user()->projects()->pluck('projects.id');

        // clone the base query before each count() — count() executes
        // and consumes the query builder, so reusing $taskQuery directly
        // across multiple ->where(...)->count() calls would accumulate
        // WHERE clauses onto the SAME builder instead of running 4
        // independent queries.
        $taskQuery = Task::whereIn('project_id', $projectIds);

        $tasksCount = (clone $taskQuery)->count();
        $completedCount = (clone $taskQuery)->where('status', TaskStatus::Completed)->count();
        $pendingCount = (clone $taskQuery)
            ->whereNotIn('status', [TaskStatus::Completed, TaskStatus::Cancelled])
            ->count();
        $overdueCount = (clone $taskQuery)
            ->where('due_date', '<', now())
            ->whereNotIn('status', [TaskStatus::Completed, TaskStatus::Cancelled])
            ->count();

        return view('dashboard.index', [
            'user' => Auth::user(),
            'projectsCount' => $projectIds->count(),
            'tasksCount' => $tasksCount,
            'completedCount' => $completedCount,
            'pendingCount' => $pendingCount,
            'overdueCount' => $overdueCount,
        ]);
    }
}
