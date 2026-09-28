<x-layout title="{{ $project->name }} — TaskFlow">
    <h1>{{ $project->name }}</h1>
    <p>{{ $project->description }}</p>

    <table>
        <tr><th>Status</th><td><span class="badge">{{ $project->status->label() }}</span></td></tr>
        <tr><th>Owner</th><td>{{ $project->owner->name }}</td></tr>
        <tr><th>Start Date</th><td>{{ $project->start_date?->format('Y-m-d') ?? '—' }}</td></tr>
        <tr><th>Due Date</th><td>{{ $project->due_date?->format('Y-m-d') ?? '—' }}</td></tr>
    </table>

    <p style="color:#6b7280;font-size:0.9rem;margin-top:1rem;">
        Members, Tasks, and Activity will appear here once those phases are implemented.
    </p>

    <div style="margin-top:1rem;display:flex;gap:0.75rem;">
        <a class="btn" href="{{ route('projects.edit', $project) }}">Edit Project</a>
        <a class="btn" href="{{ route('projects.index') }}">Back to Projects</a>
    </div>
</x-layout>
