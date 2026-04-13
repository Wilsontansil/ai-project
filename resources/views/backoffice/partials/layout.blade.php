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
        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        #bo-shell {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        #bo-sidebar {
            width: 260px;
            min-width: 260px;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            background: #3bb5a5;
            flex-shrink: 0;
            z-index: 10;
        }

        #bo-content {
            flex: 1;
            min-width: 0;
            height: 100vh;
            overflow-y: auto;
            background: linear-gradient(180deg, #020617, #0f172a 40%, #111827);
        }

        #bo-content input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]),
        #bo-content textarea,
        #bo-content select {
            background-color: rgba(15, 23, 42, 0.7);
            color: #e2e8f0;
        }

        /* Collapsed state */
        #bo-shell.bo-collapsed #bo-sidebar {
            width: 72px;
            min-width: 72px;
        }

        @media (max-width: 1023px) {
            #bo-shell {
                flex-direction: column;
            }

            #bo-sidebar {
                width: 100% !important;
                min-width: 100% !important;
                height: auto;
                overflow-y: visible;
            }

            #bo-content {
                height: auto;
                overflow-y: visible;
            }

            html,
            body {
                overflow: auto;
            }
        }
    </style>
</head>

<body class="bg-slate-950 text-slate-100">
    <div id="bo-shell">
        @include('backoffice.partials.sidebar', [
            'active' => $boActive ?? '',
        ])

        <div id="bo-content" class="p-4 sm:p-5 md:p-6">
            <div class="mx-auto max-w-6xl space-y-5">
                @yield('content')
            </div>
        </div>
    </div>

    @yield('scripts')
</body>

</html>
