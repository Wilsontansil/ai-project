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
                    {{ config('services.agent.kode', 'PG') }}</span>
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
                @php
                    $sidebarTools = \App\Models\Tool::query()->where('class_name', '!=', '')->orderBy('id')->get();
                @endphp
                @foreach ($sidebarTools as $sidebarTool)
                    <a href="{{ route('backoffice.tools.show', $sidebarTool->slug) }}"
                        class="bo-nav-item group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ ($currentTool ?? '') === $sidebarTool->tool_name ? 'bg-white/20 font-semibold text-white' : 'text-white/90 hover:bg-white/10' }}">
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-md {{ ($currentTool ?? '') === $sidebarTool->tool_name ? 'bg-white/20' : 'bg-white/10 group-hover:bg-white/15' }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="{{ $sidebarTool->meta['icon'] ?? 'M13 10V3L4 14h7v7l9-11h-7z' }}" />
                            </svg>
                        </span>
                        <span class="bo-label">{{ $sidebarTool->display_name }}</span>
                        @unless ($sidebarTool->is_enabled)
                            <span class="bo-label ml-auto text-[10px] text-rose-400">OFF</span>
                        @endunless
                    </a>
                @endforeach
                <a href="{{ route('backoffice.ai-agent') }}"
                    class="bo-nav-item group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ $active === 'ai-agent' && empty($currentTool) ? 'bg-white/20 font-semibold text-white' : 'text-white/90 hover:bg-white/10' }}">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-md {{ $active === 'ai-agent' && empty($currentTool) ? 'bg-white/20' : 'bg-white/10 group-hover:bg-white/15' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    <span class="bo-label">Settings</span>
                </a>
            </div>
        </div>

        {{-- Section: CASE REPORT --}}
        <div class="bo-section" data-section="case-report">
            <button type="button"
                class="bo-section-header flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-white/80 transition hover:bg-white/10">
                <span class="bo-section-label text-[11px] font-bold uppercase tracking-widest">Case Report</span>
                <svg class="bo-section-chevron h-3.5 w-3.5 rotated" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="bo-section-items mt-1 space-y-0.5">
                <a href="{{ route('backoffice.cases.index') }}"
                    class="bo-nav-item group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ ($boActive ?? ($active ?? '')) === 'cases' ? 'bg-white/20 font-semibold text-white' : 'text-white/90 hover:bg-white/10' }}">
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-md {{ ($boActive ?? ($active ?? '')) === 'cases' ? 'bg-white/20' : 'bg-white/10 group-hover:bg-white/15' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </span>
                    <span class="bo-label">Cases</span>
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
