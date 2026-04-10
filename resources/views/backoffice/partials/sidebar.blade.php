<style>
    #bo-sidebar {
        width: 260px;
        min-width: 260px;
        transition: width 0.2s ease, min-width 0.2s ease;
    }

    #bo-shell.bo-collapsed #bo-sidebar {
        width: 84px;
        min-width: 84px;
    }

    #bo-shell.bo-collapsed .bo-label {
        display: none;
    }

    #bo-shell.bo-collapsed .bo-nav-link {
        justify-content: center;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    #bo-shell.bo-collapsed #bo-sidebar-toggle {
        transform: rotate(180deg);
    }
</style>

<aside id="bo-sidebar" class="shrink-0 rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
    <div class="flex items-center justify-between">
        <p class="bo-label text-xs uppercase tracking-[0.3em] text-cyan-300/80">Backoffice</p>
        <button id="bo-sidebar-toggle" type="button"
            class="rounded-lg border border-white/15 bg-white/10 px-2 py-1 text-xs text-white transition hover:bg-white/15"
            title="Minimize navigation" aria-label="Toggle sidebar">
            <span>◀</span>
        </button>
    </div>

    <p class="bo-label mt-2 text-sm text-slate-300">{{ auth()->user()->email }}</p>

    <nav class="mt-6 space-y-2">
        <a href="{{ route('backoffice.dashboard') }}"
            class="bo-nav-link flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition {{ $active === 'customer' ? 'bg-cyan-400 text-slate-950' : 'bg-slate-900/50 text-slate-200 hover:bg-slate-800/70' }}">
            <span class="text-base">👥</span>
            <span class="bo-label">Customer</span>
        </a>
        <a href="{{ route('backoffice.ai-agent') }}"
            class="bo-nav-link flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition {{ $active === 'ai-agent' ? 'bg-cyan-400 text-slate-950' : 'bg-slate-900/50 text-slate-200 hover:bg-slate-800/70' }}">
            <span class="text-base">⚙️</span>
            <span class="bo-label">AI Agent</span>
        </a>
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="mt-8">
        @csrf
        <button type="submit"
            class="bo-nav-link flex w-full items-center gap-3 rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/15">
            <span class="text-base">↩</span>
            <span class="bo-label">Logout</span>
        </button>
    </form>
</aside>

<script>
    (() => {
        const shell = document.getElementById('bo-shell');
        const toggleButton = document.getElementById('bo-sidebar-toggle');

        if (!shell || !toggleButton || shell.dataset.sidebarReady === '1') {
            return;
        }

        shell.dataset.sidebarReady = '1';

        const storageKey = 'backoffice_sidebar_collapsed';

        const applyState = (collapsed) => {
            shell.classList.toggle('bo-collapsed', collapsed);
            toggleButton.title = collapsed ? 'Expand navigation' : 'Minimize navigation';
            toggleButton.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Minimize sidebar');
        };

        const collapsed = localStorage.getItem(storageKey) === '1';
        applyState(collapsed);

        toggleButton.addEventListener('click', () => {
            const next = !shell.classList.contains('bo-collapsed');
            applyState(next);
            localStorage.setItem(storageKey, next ? '1' : '0');
        });
    })();
</script>
