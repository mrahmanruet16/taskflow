<x-layout title="Edit Comment — TaskFlow">
    <p><a href="{{ route('tasks.show', $comment->task) }}">&larr; Back to task</a></p>
    <h1>Edit Comment</h1>

    @if ($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0;padding-left:1.1rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('comments.update', $comment) }}" class="stack">
        @csrf
        @method('PUT')
        <div>
            <label for="body">Comment</label>
            <input id="body" name="body" type="text" value="{{ old('body', $comment->body) }}" required>
        </div>
        <button type="submit">Save Changes</button>
    </form>
</x-layout>
