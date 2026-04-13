<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Backoffice')</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #0f172a;
                color: #f8fafc;
            }
        </style>
    @endif
    <style>
        #bo-shell input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]),
        #bo-shell textarea,
        #bo-shell select {
            background-color: rgba(15, 23, 42, 0.7);
            color: #e2e8f0;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="min-h-screen bg-[linear-gradient(180deg,_#020617,_#0f172a_40%,_#111827)] p-3 sm:p-4 md:p-6">
        <div id="bo-shell" class="mx-auto flex max-w-7xl flex-col gap-4 lg:gap-6 xl:flex-row">
            @include('backoffice.partials.sidebar', [
                'active' => $boActive ?? '',
            ])

            <main class="min-w-0 flex-1 space-y-6">
                @yield('content')
            </main>
        </div>
    </div>

    @yield('scripts')
</body>

</html>
