<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'TaskFlow' }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; margin: 0; color: #1a1a1a; background: #f7f7f8; }
        nav { display: flex; align-items: center; gap: 1.25rem; padding: 0.75rem 1.5rem; background: #1f2937; color: white; }
        nav a { color: #e5e7eb; text-decoration: none; font-size: 0.95rem; }
        nav a:hover { color: white; }
        nav .brand { font-weight: 700; margin-right: 1rem; color: white; }
        nav form { margin-left: auto; }
        nav button { background: none; border: none; color: #e5e7eb; cursor: pointer; font-size: 0.95rem; padding: 0; }
        main { max-width: 960px; margin: 2rem auto; padding: 0 1.5rem; }
        .alert { padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        form.stack { display: flex; flex-direction: column; gap: 0.9rem; max-width: 360px; }
        label { font-size: 0.85rem; font-weight: 600; color: #374151; }
        input { padding: 0.5rem 0.65rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; }
        .field-error { color: #991b1b; font-size: 0.8rem; }
        button[type="submit"], .btn { background: #2563eb; color: white; border: none; padding: 0.55rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.95rem; }
        button[type="submit"]:hover, .btn:hover { background: #1d4ed8; }
        table { border-collapse: collapse; width: 100%; margin-top: 1rem; background: white; }
        th, td { text-align: left; padding: 0.5rem 0.75rem; border-bottom: 1px solid #e5e7eb; }
        .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.75rem; background: #e5e7eb; }
    </style>
</head>
<body>
    <nav>
        <span class="brand">TaskFlow</span>
        @auth
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Logout ({{ auth()->user()->name }})</button>
            </form>
        @else
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('register') }}">Register</a>
        @endauth
    </nav>
    <main>
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
