<x-layout title="Members — {{ $project->name }}">
    <h1>Members — {{ $project->name }}</h1>
    <p><a href="{{ route('projects.show', $project) }}">&larr; Back to project</a></p>

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
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($project->members as $member)
                <tr>
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->email }}</td>
                    <td><span class="badge">{{ \App\Enums\ProjectRole::from($member->pivot->role)->label() }}</span></td>
                    <td>
                        @can('manageMembers', $project)
                            <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}" style="display:inline" onsubmit="return confirm('Remove this member?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:none;border:none;color:#b91c1c;cursor:pointer;padding:0;font-size:0.9rem;">Remove</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @can('manageMembers', $project)
        <h2 style="margin-top:2rem;font-size:1.05rem;">Add Member</h2>
        <form method="POST" action="{{ route('projects.members.store', $project) }}" class="stack">
            @csrf
            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required placeholder="colleague@example.com">
            </div>
            <div>
                <label for="role">Role</label>
                <select id="role" name="role" required style="padding:0.5rem 0.65rem;border:1px solid #d1d5db;border-radius:6px;">
                    @foreach (\App\Enums\ProjectRole::cases() as $role)
                        <option value="{{ $role->value }}">{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit">Add Member</button>
        </form>
    @endcan
</x-layout>
