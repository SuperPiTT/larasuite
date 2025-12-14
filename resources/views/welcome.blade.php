<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Larasuite') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen flex flex-col items-center justify-center">
        <h1 class="text-4xl font-bold text-gray-800 dark:text-white mb-4">
            Larasuite
        </h1>
        <p class="text-gray-600 dark:text-gray-400 text-lg">
            B2B SaaS Platform for Field Service Companies
        </p>
        <div class="mt-8 space-y-2 text-center">
            <div class="text-sm text-gray-500">
                Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})
            </div>
            <div class="flex gap-4 justify-center mt-4">
                <a href="/app" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    App Panel
                </a>
                <a href="/central" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    Central Panel
                </a>
            </div>
        </div>
    </div>
</body>
</html>
