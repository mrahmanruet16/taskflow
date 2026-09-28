<x-layout title="Tasks — TaskFlow">
    <h1>Tasks</h1>

    {{-- GET form (not POST): filters belong in the URL as query params
         (?status=&priority=&assignee=), per the spec's exact example, so
         the filtered view is itself a shareable/bookmarkable/back-button-
         friendly link — a POST-based filter form couldn't do that. --}}
    <form method="GET" action="{{ route('tasks.index') }}" style="display:flex;gap:0.75rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:1rem;">
        <div>
            <label for="status">Status</label><br>
            <select id="status" name="status" style="padding:0.4rem;border:1px solid #d1d5db;border-radius:6px;">
                <option value="">Any</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="priority">Priority</label><br>
            <select id="priority" name="priority" style="padding:0.4rem;border:1px solid #d1d5db;border-radius:6px;">
                <option value="">Any</option>
                @foreach ($priorities as $priority)
                    <option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>{{ $priority->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="sort">Sort by</label><br>
            <select id="sort" name="sort" style="padding:0.4rem;border:1px solid #d1d5db;border-radius:6px;">
                <option value="created_at" @selected(request('sort', 'created_at') === 'created_at')>Newest</option>
                <option value="due_date" @selected(request('sort') === 'due_date')>Due Date</option>
                <option value="priority" @selected(request('sort') === 'priority')>Priority</option>
            </select>
        </div>
        <button type="submit">Filter</button>
        @if (request()->hasAny(['status', 'priority', 'assignee', 'sort']))
            <a href="{{ route('tasks.index') }}" style="align-self:center;font-size:0.85rem;">Clear filters</a>
        @endif
    </form>

    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Project</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Assignee</th>
                <th>Due Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tasks as $task)
                <tr>
                    <td><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></td>
                    <td>{{ $task->project->name }}</td>
                    <td><span class="badge">{{ $task->status->label() }}</span></td>
                    <td><span class="badge">{{ $task->priority->label() }}</span></td>
                    <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                    <td>{{ $task->due_date?->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No tasks match these filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:1rem;">
        {{ $tasks->links() }}
    </div>
</x-layout>
