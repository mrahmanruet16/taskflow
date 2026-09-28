{{-- Shared field partial for create.blade.php and edit.blade.php — kept as
     a partial (not a full Blade component) since it has no independent
     behavior of its own, just fields that both forms need identically. --}}
<div>
    <label for="name">Name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $project?->name) }}" required>
</div>

<div>
    <label for="description">Description</label>
    <input id="description" name="description" type="text" value="{{ old('description', $project?->description) }}">
</div>

<div>
    <label for="status">Status</label>
    <select id="status" name="status" required style="padding:0.5rem 0.65rem;border:1px solid #d1d5db;border-radius:6px;">
        @foreach (\App\Enums\ProjectStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected(old('status', $project?->status?->value) === $status->value)>
                {{ $status->label() }}
            </option>
        @endforeach
    </select>
</div>

<div>
    <label for="start_date">Start Date</label>
    <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $project?->start_date?->format('Y-m-d')) }}">
</div>

<div>
    <label for="due_date">Due Date</label>
    <input id="due_date" name="due_date" type="date" value="{{ old('due_date', $project?->due_date?->format('Y-m-d')) }}">
</div>
