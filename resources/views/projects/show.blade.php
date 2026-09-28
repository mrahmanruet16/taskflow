<x-layout title="{{ $project->name }} — TaskFlow">
    <h1>{{ $project->name }}</h1>
    <p>{{ $project->description }}</p>

    <table>
        <tr><th>Status</th><td><span class="badge">{{ $project->status->label() }}</span></td></tr>
        <tr><th>Owner</th><td>{{ $project->owner->name }}</td></tr>
        <tr><th>Start Date</th><td>{{ $project->start_date?->format('Y-m-d') ?? '—' }}</td></tr>
        <tr><th>Due Date</th><td>{{ $project->due_date?->format('Y-m-d') ?? '—' }}</td></tr>
    </table>

    <h2 style="margin-top:1.5rem;font-size:1.05rem;">Members ({{ $project->members->count() }})</h2>
    <ul style="padding-left:1.2rem;">
        @foreach ($project->members as $member)
            <li>{{ $member->name }} — <span class="badge">{{ \App\Enums\ProjectRole::from($member->pivot->role)->label() }}</span></li>
        @endforeach
    </ul>
    <p><a href="{{ route('projects.members.index', $project) }}">Manage Members</a></p>

    <h2 style="margin-top:1.5rem;font-size:1.05rem;">Tasks ({{ $project->tasks->count() }})</h2>
    @can('create', [\App\Models\Task::class, $project])
        <a class="btn" href="{{ route('tasks.create', $project) }}">Create Task</a>
    @endcan
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Assignee</th>
                <th>Due Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($project->tasks as $task)
                <tr>
                    <td><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></td>
                    <td><span class="badge">{{ $task->status->label() }}</span></td>
                    <td><span class="badge">{{ $task->priority->label() }}</span></td>
                    <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                    <td>{{ $task->due_date?->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No tasks yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2 style="margin-top:1.5rem;font-size:1.05rem;">Activity</h2>
    @forelse ($project->activities as $activity)
        <p style="font-size:0.9rem;color:#374151;">
            {{ $activity->description }}
            <span style="color:#9ca3af;">— {{ $activity->created_at->diffForHumans() }}</span>
        </p>
    @empty
        <p style="color:#6b7280;font-size:0.9rem;">No activity yet.</p>
    @endforelse

    <div style="margin-top:1rem;display:flex;gap:0.75rem;">
        @can('update', $project)
            <a class="btn" href="{{ route('projects.edit', $project) }}">Edit Project</a>
        @endcan
        <a class="btn" href="{{ route('projects.index') }}">Back to Projects</a>
    </div>
</x-layout>
