{{-- Shared field partial for create.blade.php and edit.blade.php,
     mirroring the projects/_form.blade.php pattern. --}}
<div>
    <label for="title">Title</label>
    <input id="title" name="title" type="text" value="{{ old('title', $task?->title) }}" required>
</div>

<div>
    <label for="description">Description</label>
    <input id="description" name="description" type="text" value="{{ old('description', $task?->description) }}">
</div>

<div>
    <label for="status">Status</label>
    <select id="status" name="status" required style="padding:0.5rem 0.65rem;border:1px solid #d1d5db;border-radius:6px;">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', $task?->status?->value) === $status->value)>
                {{ $status->label() }}
            </option>
        @endforeach
    </select>
</div>

<div>
    <label for="priority">Priority</label>
    <select id="priority" name="priority" required style="padding:0.5rem 0.65rem;border:1px solid #d1d5db;border-radius:6px;">
        @foreach ($priorities as $priority)
            <option value="{{ $priority->value }}" @selected(old('priority', $task?->priority?->value) === $priority->value)>
                {{ $priority->label() }}
            </option>
        @endforeach
    </select>
</div>

<div>
    <label for="due_date">Due Date</label>
    <input id="due_date" name="due_date" type="date" value="{{ old('due_date', $task?->due_date?->format('Y-m-d')) }}">
</div>

<div>
    <label for="assigned_to">Assignee</label>
    <select id="assigned_to" name="assigned_to" style="padding:0.5rem 0.65rem;border:1px solid #d1d5db;border-radius:6px;">
        <option value="">Unassigned</option>
        @foreach ($project->members as $member)
            <option value="{{ $member->id }}" @selected((int) old('assigned_to', $task?->assigned_to) === $member->id)>
                {{ $member->name }}
            </option>
        @endforeach
    </select>
</div>
