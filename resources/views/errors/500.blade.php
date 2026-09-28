{{--
    This page must render correctly even if something else in the app is
    broken (session, database, auth) — that's the whole point of a 500
    page. Unlike every other view in this app, it deliberately does NOT
    use <x-layout> (which calls @auth / route() and would itself fail if
    the thing that's broken is auth or routing). No dynamic data is read
    here beyond nothing — this file has zero dependencies on purpose.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>500 — Server Error</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f7f7f8; color: #1a1a1a; }
        .box { text-align: center; }
        h1 { font-size: 3rem; margin: 0; color: #991b1b; }
        p { color: #6b7280; }
        a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <div class="box">
        <h1>500</h1>
        <p>Something went wrong on our end. Please try again shortly.</p>
        <a href="/">Return home</a>
    </div>
</body>
</html>
