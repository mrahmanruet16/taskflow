<x-layout title="Edit Project — TaskFlow">
    <h1>Edit Project</h1>

    @if ($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0;padding-left:1.1rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('projects.update', $project) }}" class="stack">
        @csrf
        @method('PUT')
        @include('projects._form', ['project' => $project])
        <button type="submit">Save Changes</button>
    </form>
</x-layout>
