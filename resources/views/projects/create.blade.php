<x-layout title="Create Project — TaskFlow">
    <h1>Create Project</h1>

    @if ($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0;padding-left:1.1rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('projects.store') }}" class="stack">
        @csrf
        @include('projects._form', ['project' => null])
        <button type="submit">Create Project</button>
    </form>
</x-layout>
