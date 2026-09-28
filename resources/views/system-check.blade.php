<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Check — Phase 1 Bootstrap Verification</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem auto; max-width: 640px; color: #1a1a1a; }
        h1 { font-size: 1.25rem; }
        table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
        td, th { text-align: left; padding: 0.4rem 0.6rem; border-bottom: 1px solid #ddd; }
        th { width: 40%; color: #555; }
        .ok { color: #0a7a2f; font-weight: 600; }
    </style>
</head>
<body>
    <h1>Phase 1 Bootstrap Verification</h1>
    <p class="ok">This page was rendered by Blade using data queried live from PostgreSQL.</p>
    <table>
        <tr><th>PHP version</th><td>{{ $phpVersion }}</td></tr>
        <tr><th>Laravel version</th><td>{{ $laravelVersion }}</td></tr>
        <tr><th>DB driver</th><td>{{ $dbDriver }}</td></tr>
        <tr><th>PostgreSQL server version</th><td>{{ $dbServerVersion }}</td></tr>
        <tr><th>Database name</th><td>{{ $dbName }}</td></tr>
        <tr><th>DB server time (via SELECT now())</th><td>{{ $dbNow }}</td></tr>
        <tr><th>users table row count</th><td>{{ $userCount }}</td></tr>
    </table>
</body>
</html>
