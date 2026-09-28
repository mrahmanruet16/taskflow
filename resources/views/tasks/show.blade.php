<x-layout title="{{ $task->title }} — TaskFlow">
    <p><a href="{{ route('projects.show', $task->project) }}">&larr; {{ $task->project->name }}</a></p>
    <h1>{{ $task->title }}</h1>
    <p>{{ $task->description }}</p>

    @if ($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0;padding-left:1.1rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <table>
        <tr><th>Status</th><td><span class="badge">{{ $task->status->label() }}</span></td></tr>
        <tr><th>Priority</th><td><span class="badge">{{ $task->priority->label() }}</span></td></tr>
        <tr><th>Assigned User</th><td>{{ $task->assignee?->name ?? 'Unassigned' }}</td></tr>
        <tr><th>Due Date</th><td>{{ $task->due_date?->format('Y-m-d') ?? '—' }}</td></tr>
        <tr><th>Created By</th><td>{{ $task->creator->name }}</td></tr>
    </table>

    <div style="margin-top:1rem;display:flex;gap:0.75rem;">
        @can('update', $task)
            <a class="btn" href="{{ route('tasks.edit', $task) }}">Edit</a>
        @endcan
        @can('delete', $task)
            <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn" style="background:#b91c1c;">Delete</button>
            </form>
        @endcan
    </div>

    {{-- Quick status-change form — separate from the full Edit form
         because changing status is the single most common action on a
         task (spec: "Change Status" is its own listed action, distinct
         from a general edit), and the assignee (who may not have update
         rights over other fields conceptually, though this app's policy
         does allow it — see TaskPolicy::update) shouldn't need the full
         edit form just to mark a task done. --}}
    @can('update', $task)
        <h2 style="margin-top:1.5rem;font-size:1.05rem;">Change Status</h2>
        <form method="POST" action="{{ route('tasks.update', $task) }}" style="display:flex;gap:0.5rem;align-items:center;">
            @csrf
            @method('PUT')
            <input type="hidden" name="title" value="{{ $task->title }}">
            <input type="hidden" name="priority" value="{{ $task->priority->value }}">
            <input type="hidden" name="due_date" value="{{ $task->due_date?->format('Y-m-d') }}">
            <input type="hidden" name="assigned_to" value="{{ $task->assigned_to }}">
            <select name="status" style="padding:0.4rem;border:1px solid #d1d5db;border-radius:6px;">
                @foreach (\App\Enums\TaskStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected($task->status === $status)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button type="submit">Update Status</button>
        </form>
    @endcan

    <h2 style="margin-top:1.5rem;font-size:1.05rem;">Comments ({{ $task->comments->count() }})</h2>
    @forelse ($task->comments as $comment)
        <div style="border-bottom:1px solid #e5e7eb;padding:0.5rem 0;">
            <p style="font-size:0.9rem;margin:0;">
                <strong>{{ $comment->user?->name ?? 'Deleted user' }}</strong>
                <span style="color:#9ca3af;">— {{ $comment->created_at->diffForHumans() }}</span>
            </p>
            <p style="margin:0.25rem 0;">{{ $comment->body }}</p>
            @can('update', $comment)
                <a href="{{ route('comments.edit', $comment) }}" style="font-size:0.85rem;">Edit</a>
            @endcan
            @can('delete', $comment)
                &middot;
                <form method="POST" action="{{ route('comments.destroy', $comment) }}" style="display:inline" onsubmit="return confirm('Delete this comment?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="background:none;border:none;color:#b91c1c;cursor:pointer;padding:0;font-size:0.85rem;">Delete</button>
                </form>
            @endcan
        </div>
    @empty
        <p style="color:#6b7280;font-size:0.9rem;">No comments yet.</p>
    @endforelse

    @can('create', [\App\Models\Comment::class, $task])
        <form method="POST" action="{{ route('comments.store', $task) }}" class="stack" style="margin-top:1rem;">
            @csrf
            <div>
                <label for="body">Add a comment</label>
                <input id="body" name="body" type="text" required>
            </div>
            <button type="submit">Post Comment</button>
        </form>
    @endcan

    <h2 style="margin-top:1.5rem;font-size:1.05rem;">Activity</h2>
    @forelse ($task->activities as $activity)
        <p style="font-size:0.9rem;color:#374151;">
            {{ $activity->description }}
            <span style="color:#9ca3af;">— {{ $activity->created_at->diffForHumans() }}</span>
        </p>
    @empty
        <p style="color:#6b7280;font-size:0.9rem;">No activity yet.</p>
    @endforelse
</x-layout>
