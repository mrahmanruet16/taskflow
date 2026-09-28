<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// The 'guest' middleware alias (built into Laravel) redirects an already
// logged-in user away from these routes — a logged-in user has no reason
// to see a registration/login form, and Auth::attempt() would misbehave
// if called on top of an existing session.
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// The 'auth' middleware alias rejects any request without a valid session,
// redirecting to the 'login' named route (configured in
// App\Providers\AppServiceProvider or the framework default). This is the
// actual authorization boundary — removing this middleware is Failure
// Experiment 1 in docs/testing/failure-experiments.md (added in a later
// phase) alongside the Policy-based experiments.
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Route::resource maps the 7 conventional REST actions (index, create,
    // store, show, edit, update, destroy) to 7 routes/URIs in one line.
    // Authorization is NOT done here — it happens per-action inside
    // ProjectController (Gate::authorize / Form Request authorize()),
    // because "is logged in" (this middleware) and "is allowed to touch
    // THIS project" (the Policy) are different questions.
    Route::resource('projects', ProjectController::class);

    // Nested under /projects/{project}/members, matching the spec's
    // required screen. Named routes explicitly (rather than a full
    // Route::resource) since only index/store/destroy are needed —
    // there's no "edit a membership" screen, only add/remove.
    Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index'])->name('projects.members.index');
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
    Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');

    // /tasks (global, filterable) is separate from task *creation*, which
    // is nested under its project (/projects/{project}/tasks/create) since
    // a task can't exist without a project to belong to. /tasks/{task}
    // itself is NOT nested under /projects/{project} — matches the spec's
    // exact required URL and reflects that once a task exists, it's
    // addressable on its own.
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/projects/{project}/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // Comments have no standalone index/show — they render inline on the
    // task show page. Only store/edit/update/destroy exist as routes.
    Route::post('/tasks/{task}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::get('/comments/{comment}/edit', [CommentController::class, 'edit'])->name('comments.edit');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

// Phase 1 bootstrap-only verification route: proves Blade can render data
// that actually came from PostgreSQL via a live PDO connection, not a stub.
// This route is temporary scaffolding and is expected to be replaced once
// Phase 2 introduces the real dashboard.
Route::get('/system-check', function () {
    $pdo = DB::connection()->getPdo();

    return view('system-check', [
        'phpVersion' => PHP_VERSION,
        'laravelVersion' => app()->version(),
        'dbDriver' => DB::connection()->getDriverName(),
        'dbServerVersion' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
        'dbName' => DB::connection()->getDatabaseName(),
        'dbNow' => DB::selectOne('select now() as now')->now,
        'userCount' => DB::table('users')->count(),
    ]);
});
