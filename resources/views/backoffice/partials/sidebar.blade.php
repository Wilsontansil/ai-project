<style>
    #bo-sidebar {
        width: 260px;
        min-width: 260px;
        max-width: 260px;
        height: calc(100vh - 3rem);
        position: sticky;
        top: 1.5rem;
        transition: width 0.25s ease, min-width 0.25s ease, max-width 0.25s ease;
        background: #3bb5a5;
        flex-shrink: 0;
        flex-grow: 0;
    }

    #bo-shell.bo-collapsed #bo-sidebar {
        width: 72px;
        min-width: 72px;
        max-width: 72px;
    }

    #bo-shell.bo-collapsed .bo-label,
    #bo-shell.bo-collapsed .bo-section-label,
    #bo-shell.bo-collapsed .bo-section-chevron,
    #bo-shell.bo-collapsed .bo-section-items {
        display: none;
    }

    #bo-shell.bo-collapsed .bo-nav-item {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }

    #bo-shell.bo-collapsed .bo-section-header {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }

    .bo-section-items {
        overflow: hidden;
        max-height: 500px;
        transition: max-height 0.3s ease;
    }

    .bo-section-items.collapsed {
        max-height: 0;
    }

    .bo-section-chevron {
        transition: transform 0.2s ease;
    }

    .bo-section-chevron.rotated {
        transform: rotate(90deg);
    }
</style>

<aside id="bo-sidebar" class="shrink-0 flex flex-col rounded-2xl overflow-hidden">
    {{-- Brand area --}}
    <div class="flex items-center justify-between px-5 py-4" style="background: rgba(0,0,0,0.15);">
        <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20">
                <span class="text-sm font-bold text-white">AI</span>
            </div>
            <div class="bo-label flex flex-col">
                <span class="text-sm font-bold tracking-wide text-white">AI Backoffice</span>
                <span class="text-[10px] font-medium text-white/60">Agent:
                    {{ \App\Models\ProjectSetting::getValue('agent_kode', config('services.agent.kode', 'PG')) }}</span>
            </div>
        </div>
        <button id="bo-sidebar-toggle" type="button"
            class="flex h-7 w-7 items-center justify-center rounded-md bg-white/10 text-white/80 transition hover:bg-white/20 hover:text-white"
            title="Minimize navigation" aria-label="Toggle sidebar">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Navigation sections --}}
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">

        {{-- Section: CUSTOMER DATA --}}
        <div class="bo-section" data-section="customer-data">
            <button type="button"
                class="bo-section-header flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-white/80 transition hover:bg-white/10">
                <span class="bo-section-label text-[11px] font-bold uppercase tracking-widest">Customer Data</span>
                <svg class="bo-section-chevron h-3.5 w-3.5 rotated" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="bo-section-items mt-1 space-y-0.5">
                <a href="{{ route('backoffice.dashboard') }}"
                    class="bo-nav-item group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ $active === 'customer' ? 'bg-white/20 font-semibold text-white' : 'text-white/90 hover:bg-white/10' }}">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-md {{ $active === 'customer' ? 'bg-white/20' : 'bg-white/10 group-hover:bg-white/15' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    <span class="bo-label">Customer</span>
                </a>
            </div>
        </div>

        {{-- Section: AI AGENT --}}
        <div class="bo-section" data-section="ai-agent">
            <button type="button"
                class="bo-section-header flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-white/80 transition hover:bg-white/10">
                <span class="bo-section-label text-[11px] font-bold uppercase tracking-widest">AI Agent</span>
                <svg class="bo-section-chevron h-3.5 w-3.5 rotated" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="bo-section-items mt-1 space-y-0.5">
                <a href="{{ route('backoffice.tools.index') }}"
                    class="bo-nav-item group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ ($boActive ?? ($active ?? '')) === 'tools' ? 'bg-white/20 font-semibold text-white' : 'text-white/90 hover:bg-white/10' }}">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-md {{ ($boActive ?? ($active ?? '')) === 'tools' ? 'bg-white/20' : 'bg-white/10 group-hover:bg-white/15' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                        </svg>
                    </span>
                    <span class="bo-label">Tools</span>
                </a>
                <a href="{{ route('backoffice.data-models.index') }}"
                    class="bo-nav-item group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ ($boActive ?? ($active ?? '')) === 'data-models' ? 'bg-white/20 font-semibold text-white' : 'text-white/90 hover:bg-white/10' }}">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-md {{ ($boActive ?? ($active ?? '')) === 'data-models' ? 'bg-white/20' : 'bg-white/10 group-hover:bg-white/15' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 7a2 2 0 012-2h12a2 2 0 012 2v2H4V7zM4 11h16v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6zm4 2h2m0 0v2m0-2H8m2 0h2" />
                        </svg>
                    </span>
                    <span class="bo-label">Data Models</span>
                </a>
                <a href="{{ route('backoffice.ai-agent') }}"
                    class="bo-nav-item group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ $active === 'ai-agent' ? 'bg-white/20 font-semibold text-white' : 'text-white/90 hover:bg-white/10' }}">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-md {{ $active === 'ai-agent' ? 'bg-white/20' : 'bg-white/10 group-hover:bg-white/15' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    <span class="bo-label">Settings</span>
                </a>
                <a href="{{ route('backoffice.forbidden.index') }}"
                    class="bo-nav-item group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ ($boActive ?? ($active ?? '')) === 'forbidden' ? 'bg-white/20 font-semibold text-white' : 'text-white/90 hover:bg-white/10' }}">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-md {{ ($boActive ?? ($active ?? '')) === 'forbidden' ? 'bg-white/20' : 'bg-white/10 group-hover:bg-white/15' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </span>
                    <span class="bo-label">Forbidden</span>
                </a>
            </div>
        </div>

        {{-- Section: SYSTEM --}}
        <div class="bo-section" data-section="system">
            <button type="button"
                class="bo-section-header flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-white/80 transition hover:bg-white/10">
                <span class="bo-section-label text-[11px] font-bold uppercase tracking-widest">System</span>
                <svg class="bo-section-chevron h-3.5 w-3.5 rotated" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="bo-section-items mt-1 space-y-0.5">
                <a href="{{ route('backoffice.settings.index') }}"
                    class="bo-nav-item group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ ($boActive ?? ($active ?? '')) === 'settings' ? 'bg-white/20 font-semibold text-white' : 'text-white/90 hover:bg-white/10' }}">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-md {{ ($boActive ?? ($active ?? '')) === 'settings' ? 'bg-white/20' : 'bg-white/10 group-hover:bg-white/15' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                    </span>
                    <span class="bo-label">Settings</span>
                </a>
            </div>
        </div>

    </nav>

    {{-- Footer / Logout --}}
    <div class="border-t border-white/15 px-3 py-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="bo-nav-item group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/80 transition hover:bg-white/10">
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-white/10 group-hover:bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </span>
                <span class="bo-label">Logout</span>
            </button>
        </form>
    </div>
</aside>

<script>
    (() => {
        const shell = document.getElementById('bo-shell');
        const toggleButton = document.getElementById('bo-sidebar-toggle');

        if (!shell || !toggleButton || shell.dataset.sidebarReady === '1') return;
        shell.dataset.sidebarReady = '1';

        const storageKey = 'backoffice_sidebar_collapsed';
        const sectionKey = 'backoffice_sections';

        // Sidebar collapse / expand
        const applyCollapsed = (collapsed) => {
            shell.classList.toggle('bo-collapsed', collapsed);
            toggleButton.title = collapsed ? 'Expand navigation' : 'Minimize navigation';
            toggleButton.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Minimize sidebar');
        };

        applyCollapsed(localStorage.getItem(storageKey) === '1');

        toggleButton.addEventListener('click', () => {
            const next = !shell.classList.contains('bo-collapsed');
            applyCollapsed(next);
            localStorage.setItem(storageKey, next ? '1' : '0');
        });

        // Section collapse / expand (like reference panel)
        const savedSections = JSON.parse(localStorage.getItem(sectionKey) || '{}');

        document.querySelectorAll('.bo-section').forEach(section => {
            const name = section.dataset.section;
            const header = section.querySelector('.bo-section-header');
            const items = section.querySelector('.bo-section-items');
            const chevron = section.querySelector('.bo-section-chevron');

            if (!header || !items || !chevron) return;

            const applySection = (expanded) => {
                items.classList.toggle('collapsed', !expanded);
                chevron.classList.toggle('rotated', expanded);
            };

            // Default open unless explicitly saved as closed
            const isExpanded = savedSections[name] !== false;
            applySection(isExpanded);

            header.addEventListener('click', () => {
                const expanded = items.classList.contains('collapsed');
                applySection(expanded);
                savedSections[name] = expanded;
                localStorage.setItem(sectionKey, JSON.stringify(savedSections));
            });
        });
    })();
</script>
