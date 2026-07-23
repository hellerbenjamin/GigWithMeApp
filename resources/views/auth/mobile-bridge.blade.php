<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Opening GigWithMe</title>
    <script>
        // Hand the one-time token to the app via its custom URL scheme.
        // Runs on load; the button below is the fallback if the browser
        // blocks the automatic jump.
        window.location.replace(@json($deepLink));
    </script>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0; min-height: 100vh; box-sizing: border-box;
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 1rem; padding: 2rem;
            text-align: center; background: #faf7ff; color: #1f1a2e;
        }
        h1 { font-size: 1.25rem; margin: 0; }
        p { color: #6b7280; margin: 0; max-width: 22rem; }
        a.button {
            display: inline-block; margin-top: 0.5rem;
            padding: 0.75rem 1.5rem; border-radius: 0.5rem;
            background: #7c3aed; color: #fff; text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <h1>Opening GigWithMe</h1>
    <p>If the app does not open on its own, tap the button below.</p>
    <a class="button" href="{{ $deepLink }}">Open GigWithMe</a>
</body>
</html>
