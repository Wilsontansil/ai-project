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
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.15s;
        }

        #bo-content input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):focus,
        #bo-content textarea:focus,
        #bo-content select:focus {
            border-color: #22d3ee;
        }

        #bo-content textarea {
            line-height: 1.6;
        }

        #bo-content select option {
            background: #0f172a;
            color: #e2e8f0;
        }

        /* Buttons */
        #bo-content .bo-btn-primary {
            display: inline-block;
            border-radius: 0.5rem;
            background: #22d3ee;
            padding: 0.625rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }

        #bo-content .bo-btn-primary:hover {
            background: #67e8f9;
        }

        #bo-content .bo-btn-secondary {
            display: inline-block;
            border-radius: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            color: #94a3b8;
            background: transparent;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }

        #bo-content .bo-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
        }

        #bo-content .bo-btn-danger {
            display: inline-block;
            border-radius: 0.5rem;
            border: 1px solid rgba(248, 113, 113, 0.2);
            background: rgba(239, 68, 68, 0.1);
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            color: #fca5a5;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }

        #bo-content .bo-btn-danger:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        #bo-content .bo-btn-sm {
            display: inline-block;
            border-radius: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            color: #cbd5e1;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }

        #bo-content .bo-btn-sm:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Checkbox container */
        #bo-content .bo-checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(15, 23, 42, 0.7);
            padding: 0.625rem 1rem;
            cursor: pointer;
            font-size: 0.875rem;
            color: #e2e8f0;
        }

        /* Form label */
        #bo-content .bo-label {
            display: block;
            margin-bottom: 0.375rem;
            font-size: 0.875rem;
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
