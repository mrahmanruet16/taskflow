<x-layout title="Dashboard — TaskFlow">
    <h1>Dashboard</h1>
    <p>Welcome, {{ $user->name }} ({{ $user->email }}).</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:1rem;margin-top:1rem;">
        <div style="background:white;padding:1rem;border-radius:8px;text-align:center;">
            <div style="font-size:1.75rem;font-weight:700;">{{ $projectsCount }}</div>
            <div style="color:#6b7280;font-size:0.85rem;">Projects</div>
        </div>
        <div style="background:white;padding:1rem;border-radius:8px;text-align:center;">
            <div style="font-size:1.75rem;font-weight:700;">{{ $tasksCount }}</div>
            <div style="color:#6b7280;font-size:0.85rem;">Tasks</div>
        </div>
        <div style="background:white;padding:1rem;border-radius:8px;text-align:center;">
            <div style="font-size:1.75rem;font-weight:700;color:#166534;">{{ $completedCount }}</div>
            <div style="color:#6b7280;font-size:0.85rem;">Completed Tasks</div>
        </div>
        <div style="background:white;padding:1rem;border-radius:8px;text-align:center;">
            <div style="font-size:1.75rem;font-weight:700;color:#92400e;">{{ $pendingCount }}</div>
            <div style="color:#6b7280;font-size:0.85rem;">Pending Tasks</div>
        </div>
        <div style="background:white;padding:1rem;border-radius:8px;text-align:center;">
            <div style="font-size:1.75rem;font-weight:700;color:#991b1b;">{{ $overdueCount }}</div>
            <div style="color:#6b7280;font-size:0.85rem;">Overdue Tasks</div>
        </div>
    </div>

    <p style="margin-top:1.5rem;">
        <a href="{{ route('projects.index') }}">View Projects</a>
        &middot;
        <a href="{{ route('tasks.index') }}">View Tasks</a>
    </p>
</x-layout>
