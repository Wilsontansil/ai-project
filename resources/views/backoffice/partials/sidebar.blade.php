<style>
    /* Collapsed state */
    #bo-shell.bo-collapsed #bo-sidebar {
        width: 72px;
        min-width: 72px;
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

    @media (max-width: 1023px) {
        #bo-shell.bo-collapsed #bo-sidebar {
            width: 100% !important;
            min-width: 100% !important;
        }

        #bo-shell.bo-collapsed .bo-label,
        #bo-shell.bo-collapsed .bo-section-label,
        #bo-shell.bo-collapsed .bo-section-chevron,
        #bo-shell.bo-collapsed .bo-section-items {
            display: initial;
        }

        #bo-shell.bo-collapsed .bo-nav-item,
        #bo-shell.bo-collapsed .bo-section-header {
            justify-content: space-between;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        #bo-sidebar-toggle {
            display: none;
        }
    }
</style>

<aside id="bo-sidebar" class="flex flex-col" style="display:flex;flex-direction:column">
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
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
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
                <span class="bo-section-label flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14"
                        height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Customer Data
                </span>
                <svg class="bo-section-chevron h-3.5 w-3.5 rotated" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="bo-section-items mt-1 space-y-0.5 pl-5">
                <a href="{{ route('backoffice.dashboard') }}"
                    class="bo-nav-item group flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition {{ $active === 'customer' ? 'bg-white/15 font-semibold text-white' : 'text-white/70 hover:bg-white/10 hover:text-white/90' }}">
                    <svg class="h-4 w-4 shrink-0 {{ $active === 'customer' ? 'text-white' : 'text-white/50 group-hover:text-white/70' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="16"
                        height="16">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span class="bo-label">Customer</span>
                </a>
            </div>
        </div>

        {{-- Section: AI AGENT --}}
        <div class="bo-section" data-section="ai-agent">
            <button type="button"
                class="bo-section-header flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-white/80 transition hover:bg-white/10">
                <span class="bo-section-label flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14"
                        height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 3a2.25 2.25 0 00-2.25 2.25V9H5.25A2.25 2.25 0 003 11.25v3.5A2.25 2.25 0 005.25 17h2.25v3.75A2.25 2.25 0 009.75 23h4.5a2.25 2.25 0 002.25-2.25V17h2.25A2.25 2.25 0 0021 14.75v-3.5A2.25 2.25 0 0018.75 9H16.5V5.25A2.25 2.25 0 0014.25 3h-4.5z" />
                    </svg>
                    AI Agent
                </span>
                <svg class="bo-section-chevron h-3.5 w-3.5 rotated" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="bo-section-items mt-1 space-y-0.5 pl-5">
                {{-- Agents --}}
                <a href="{{ route('backoffice.chat-agents.index') }}"
                    class="bo-nav-item group flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition {{ ($boActive ?? ($active ?? '')) === 'chat-agents' ? 'bg-white/15 font-semibold text-white' : 'text-white/70 hover:bg-white/10 hover:text-white/90' }}">
                    <svg class="h-4 w-4 shrink-0 {{ ($boActive ?? ($active ?? '')) === 'chat-agents' ? 'text-white' : 'text-white/50 group-hover:text-white/70' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="16"
                        height="16">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                    </svg>
                    <span class="bo-label">Agents</span>
                </a>

                {{-- Tools --}}
                <a href="{{ route('backoffice.tools.index') }}"
                    class="bo-nav-item group flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition {{ ($boActive ?? ($active ?? '')) === 'tools' ? 'bg-white/15 font-semibold text-white' : 'text-white/70 hover:bg-white/10 hover:text-white/90' }}">
                    <svg class="h-4 w-4 shrink-0 {{ ($boActive ?? ($active ?? '')) === 'tools' ? 'text-white' : 'text-white/50 group-hover:text-white/70' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="16"
                        height="16">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11.42 15.17l-5.1 5.1a2.121 2.121 0 01-3-3l5.1-5.1m0 0L4.16 7.91a2.13 2.13 0 010-3l1.26-1.26a2.13 2.13 0 013 0l4.26 4.26m-1.26 1.26l6.97-6.97a2.121 2.121 0 013 3l-6.97 6.97" />
                    </svg>
                    <span class="bo-label">Tools</span>
                </a>

                {{-- Data Models --}}
                <a href="{{ route('backoffice.data-models.index') }}"
                    class="bo-nav-item group flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition {{ ($boActive ?? ($active ?? '')) === 'data-models' ? 'bg-white/15 font-semibold text-white' : 'text-white/70 hover:bg-white/10 hover:text-white/90' }}">
                    <svg class="h-4 w-4 shrink-0 {{ ($boActive ?? ($active ?? '')) === 'data-models' ? 'text-white' : 'text-white/50 group-hover:text-white/70' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="16"
                        height="16">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                    </svg>
                    <span class="bo-label">Data Models</span>
                </a>
            </div>
        </div>

        {{-- Section: SYSTEM --}}
        <div class="bo-section" data-section="system">
            <button type="button"
                class="bo-section-header flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-white/80 transition hover:bg-white/10">
                <span class="bo-section-label flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    </svg>
                    System
                </span>
                <svg class="bo-section-chevron h-3.5 w-3.5 rotated" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="bo-section-items mt-1 space-y-0.5 pl-5">
                <a href="{{ route('backoffice.settings.index') }}"
                    class="bo-nav-item group flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition {{ ($boActive ?? ($active ?? '')) === 'settings' ? 'bg-white/15 font-semibold text-white' : 'text-white/70 hover:bg-white/10 hover:text-white/90' }}">
                    <svg class="h-4 w-4 shrink-0 {{ ($boActive ?? ($active ?? '')) === 'settings' ? 'text-white' : 'text-white/50 group-hover:text-white/70' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="16"
                        height="16">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                    </svg>
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
                class="bo-nav-item group flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-white/70 transition hover:bg-white/10 hover:text-white/90">
                <svg class="h-4 w-4 shrink-0 text-white/50 group-hover:text-white/70" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
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
