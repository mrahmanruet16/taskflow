<x-layout title="Projects — TaskFlow">
    <h1>Projects</h1>
    <a class="btn" href="{{ route('projects.create') }}">Create Project</a>

    <table>
        <thead>
            <tr>
                <th>Project Name</th>
                <th>Status</th>
                <th>Owner</th>
                <th>Due Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($projects as $project)
                <tr>
                    <td>{{ $project->name }}</td>
                    <td><span class="badge">{{ $project->status->label() }}</span></td>
                    <td>{{ $project->owner->name }}</td>
                    <td>{{ $project->due_date?->format('Y-m-d') ?? '—' }}</td>
                    <td>
                        <a href="{{ route('projects.show', $project) }}">View</a>
                        &middot;
                        <a href="{{ route('projects.edit', $project) }}">Edit</a>
                        &middot;
                        <form method="POST" action="{{ route('projects.destroy', $project) }}" style="display:inline" onsubmit="return confirm('Delete this project?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="background:none;border:none;color:#b91c1c;cursor:pointer;padding:0;font-size:0.9rem;">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No projects yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:1rem;">
        {{ $projects->links() }}
    </div>
</x-layout>
