<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline — {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f9fafb;
            color: #111827;
            padding: 2rem;
            text-align: center;
        }
        .wrap { max-width: 24rem; }
        h1 { font-size: 1.5rem; margin-bottom: 0.75rem; }
        p { color: #6b7280; margin-bottom: 1.5rem; line-height: 1.5; }
        button {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>You're offline</h1>
        <p>Check your connection and try again. Your changes are saved locally and will sync when you're back online.</p>
        <button onclick="window.location.reload()">Retry</button>
    </div>
</body>
</html>