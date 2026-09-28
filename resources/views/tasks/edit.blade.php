<x-layout title="Edit Task — {{ $task->title }}">
    <h1>Edit Task</h1>

    @if ($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0;padding-left:1.1rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tasks.update', $task) }}" class="stack">
        @csrf
        @method('PUT')
        @include('tasks._form', ['task' => $task, 'project' => $task->project])
        <button type="submit">Save Changes</button>
    </form>
</x-layout>
