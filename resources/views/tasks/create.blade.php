<x-layout title="Create Task — {{ $project->name }}">
    <h1>Create Task — {{ $project->name }}</h1>

    @if ($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0;padding-left:1.1rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tasks.store', $project) }}" class="stack">
        @csrf
        @include('tasks._form', ['task' => null, 'project' => $project])
        <button type="submit">Create Task</button>
    </form>
</x-layout>
