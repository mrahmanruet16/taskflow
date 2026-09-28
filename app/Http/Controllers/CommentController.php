<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    /**
     * No index/show routes — comments have no standalone page in this
     * app; they render inline on the task show page (Task::comments()).
     */
    public function store(StoreCommentRequest $request, Task $task): RedirectResponse
    {
        $comment = Comment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'body' => $request->validated('body'),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'description' => Auth::user()->name.' commented on task "'.$task->title.'".',
        ]);

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'Comment added.');
    }

    public function edit(Comment $comment): View
    {
        Gate::authorize('update', $comment);

        return view('comments.edit', ['comment' => $comment]);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): RedirectResponse
    {
        $comment->update($request->validated());

        return redirect()
            ->route('tasks.show', $comment->task)
            ->with('status', 'Comment updated.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $task = $comment->task;
        $comment->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'description' => Auth::user()->name.' deleted a comment on task "'.$task->title.'".',
        ]);

        return redirect()
            ->route('tasks.show', $task)
            ->with('status', 'Comment deleted.');
    }
}
