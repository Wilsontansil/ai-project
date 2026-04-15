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

        /* Top bar */
        #bo-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 56px;
            padding: 0 1.5rem;
            background: rgba(2, 6, 23, 0.85);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 20;
            flex-shrink: 0;
        }

        .bo-topbar-left {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #e2e8f0;
        }

        .bo-topbar-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .bo-user-menu {
            position: relative;
        }

        .bo-user-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.5rem;
            padding: 0.375rem 0.75rem;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
            color: #e2e8f0;
            font-size: 0.8125rem;
        }

        .bo-user-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .bo-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #22d3ee, #06b6d4);
            color: #0f172a;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .bo-user-name {
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .bo-chevron-down {
            opacity: 0.5;
            flex-shrink: 0;
            transition: transform 0.2s;
        }

        .bo-user-menu.open .bo-chevron-down {
            transform: rotate(180deg);
        }

        .bo-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            min-width: 200px;
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            z-index: 50;
        }

        .bo-user-menu.open .bo-dropdown {
            display: block;
        }

        .bo-dropdown-header {
            padding: 0.75rem 1rem;
        }

        .bo-dropdown-email {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .bo-dropdown-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.08);
        }

        .bo-dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.625rem 1rem;
            font-size: 0.8125rem;
            color: #e2e8f0;
            background: none;
            border: none;
            cursor: pointer;
            transition: background 0.15s;
            text-align: left;
        }

        .bo-dropdown-item:hover {
            background: rgba(255, 255, 255, 0.06);
        }

        .bo-dropdown-logout {
            color: #fca5a5;
        }

        .bo-dropdown-logout:hover {
            background: rgba(239, 68, 68, 0.1);
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

        <div id="bo-content">
            {{-- Top bar --}}
            <header id="bo-topbar">
                <div class="bo-topbar-left">
                    @yield('page-title')
                </div>
                <div class="bo-topbar-right">
                    @auth
                        <div class="bo-user-menu" id="bo-user-menu">
                            <button type="button" class="bo-user-btn" id="bo-user-btn" aria-haspopup="true"
                                aria-expanded="false">
                                <span
                                    class="bo-avatar">{{ strtoupper(substr(Auth::user()->name ?? Auth::user()->email, 0, 1)) }}</span>
                                <span class="bo-user-name">{{ Auth::user()->name ?? Auth::user()->email }}</span>
                                <svg class="bo-chevron-down" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div class="bo-dropdown" id="bo-user-dropdown">
                                <div class="bo-dropdown-header">
                                    <span class="bo-dropdown-email">{{ Auth::user()->email }}</span>
                                </div>
                                <div class="bo-dropdown-divider"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="bo-dropdown-item bo-dropdown-logout">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            stroke-width="1.5" width="16" height="16">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                        </svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </header>

            <div class="p-4 sm:p-5 md:p-6">
                <div class="mx-auto max-w-6xl space-y-5">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const menu = document.getElementById('bo-user-menu');
            const btn = document.getElementById('bo-user-btn');
            if (!menu || !btn) return;

            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.classList.toggle('open');
                btn.setAttribute('aria-expanded', menu.classList.contains('open'));
            });

            document.addEventListener('click', (e) => {
                if (!menu.contains(e.target)) {
                    menu.classList.remove('open');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    menu.classList.remove('open');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });
        })();
    </script>

    @yield('scripts')
</body>

</html>
