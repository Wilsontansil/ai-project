<style>
    /* ── Sidebar transition ── */
    #bo-sidebar {
        transition: width 0.25s ease, min-width 0.25s ease;
    }

    /* ── Collapsed state ── */
    #bo-shell.bo-collapsed #bo-sidebar {
        width: 72px;
        min-width: 72px;
    }

    /* Hide text labels & section headers when collapsed */
    #bo-shell.bo-collapsed .bo-label,
    #bo-shell.bo-collapsed .bo-section-label,
    #bo-shell.bo-collapsed .bo-section-chevron {
        display: none !important;
    }

    #bo-shell.bo-collapsed .bo-section-header {
        display: none !important;
    }

    /* Child items visible as icon-only when collapsed */
    #bo-shell.bo-collapsed .bo-section-items {
        padding-left: 0 !important;
        margin-left: 0 !important;
        margin-top: 0 !important;
        border-left: none !important;
        max-height: 500px !important;
    }

    #bo-shell.bo-collapsed .bo-section-items.collapsed {
        max-height: 500px !important;
    }

    #bo-shell.bo-collapsed .bo-nav-item {
        justify-content: center !important;
        padding: 0.625rem 0 !important;
        border-radius: 0.5rem;
        position: relative;
        gap: 0 !important;
    }

    /* Tooltip on hover when collapsed */
    #bo-shell.bo-collapsed .bo-nav-item:hover .bo-tooltip {
        display: block;
    }

    .bo-tooltip {
        display: none;
        position: absolute;
        left: calc(100% + 10px);
        top: 50%;
        transform: translateY(-50%);
        background: #1e293b;
        color: #e2e8f0;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        white-space: nowrap;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        z-index: 100;
        pointer-events: none;
    }

    /* Brand area collapsed */
    #bo-shell.bo-collapsed .bo-brand-text {
        display: none !important;
    }

    #bo-shell.bo-collapsed .bo-brand-area {
        justify-content: center !important;
        padding: 0.75rem !important;
    }

    /* Toggle button: when collapsed, show as centered expand button */
    #bo-shell.bo-collapsed #bo-sidebar-toggle {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
    }

    #bo-shell.bo-collapsed .bo-brand-area {
        position: relative;
    }

    /* Toggle icon rotation */
    #bo-sidebar-toggle .bo-toggle-icon {
        transition: transform 0.25s ease;
    }

    #bo-shell.bo-collapsed #bo-sidebar-toggle .bo-toggle-icon {
        transform: rotate(180deg);
    }

    /* Sidebar overflow hidden to prevent text bleed */
    #bo-shell.bo-collapsed #bo-sidebar {
        overflow: hidden;
    }

    /* Separator lines hidden when collapsed */
    #bo-shell.bo-collapsed .bo-section+.bo-section {
        margin-top: 0.25rem;
        padding-top: 0.25rem;
    }

    /* ── Section items (expanded) ── */
    .bo-section-items {
        overflow: hidden;
        max-height: 500px;
        transition: max-height 0.3s ease;
        margin-left: 0.75rem;
        padding-left: 0.75rem;
        border-left: 2px solid rgba(255, 255, 255, 0.1);
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

    /* ── Separator between sections ── */
    .bo-section+.bo-section {
        margin-top: 0.5rem;
        padding-top: 0.5rem;
    }

    /* ── Responsive ── */
    @media (max-width: 1023px) {
        #bo-shell.bo-collapsed #bo-sidebar {
            width: 100% !important;
            min-width: 100% !important;
            overflow: visible !important;
        }

        #bo-shell.bo-collapsed .bo-label,
        #bo-shell.bo-collapsed .bo-section-header {
            display: flex !important;
        }

        #bo-shell.bo-collapsed .bo-section-label,
        #bo-shell.bo-collapsed .bo-section-chevron {
            display: flex !important;
        }

        #bo-shell.bo-collapsed .bo-section-items {
            padding-left: 0.75rem !important;
            margin-left: 0.75rem !important;
            border-left: 2px solid rgba(255, 255, 255, 0.1) !important;
        }

        #bo-shell.bo-collapsed .bo-nav-item {
            justify-content: flex-start !important;
            padding: 0.5rem 0.75rem !important;
            gap: 0.625rem !important;
        }

        #bo-shell.bo-collapsed .bo-brand-text {
            display: flex !important;
        }

        #bo-shell.bo-collapsed .bo-brand-area {
            justify-content: space-between !important;
            padding: 1rem 1.25rem !important;
        }

        #bo-shell.bo-collapsed #bo-sidebar-toggle {
            position: static;
        }
    }
</style>

<aside id="bo-sidebar" style="display:flex;flex-direction:column">
    {{-- Brand area --}}
    <div class="bo-brand-area"
        style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;background:rgba(0,0,0,0.15);">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <div
                style="display:flex;height:32px;width:32px;align-items:center;justify-content:center;border-radius:0.5rem;background:rgba(255,255,255,0.2);flex-shrink:0;">
                <span style="font-size:0.875rem;font-weight:700;color:#fff;">AI</span>
            </div>
            <div class="bo-brand-text" style="display:flex;flex-direction:column;">
                <span style="font-size:0.875rem;font-weight:700;letter-spacing:0.025em;color:#fff;">AI Backoffice</span>
                <span style="font-size:10px;font-weight:500;color:rgba(255,255,255,0.6);">Agent:
                    {{ \App\Models\ProjectSetting::getValue('agent_kode', config('services.agent.kode', 'PG')) }}</span>
            </div>
        </div>
        <button id="bo-sidebar-toggle" type="button"
            style="display:flex;height:28px;width:28px;align-items:center;justify-content:center;border-radius:0.375rem;background:rgba(255,255,255,0.1);border:none;color:rgba(255,255,255,0.8);cursor:pointer;transition:background 0.15s;"
            title="Minimize navigation" aria-label="Toggle sidebar">
            <svg class="bo-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16"
                height="16" style="width:16px;height:16px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
        </button>
    </div>

    {{-- Navigation sections --}}
    <nav style="flex:1;overflow-y:auto;overflow-x:hidden;padding:1rem 0.75rem;">

        {{-- Section: CUSTOMER DATA --}}
        <div class="bo-section" data-section="customer-data">
            <button type="button" class="bo-section-header"
                style="display:flex;width:100%;align-items:center;justify-content:space-between;border-radius:0.5rem;padding:0.5rem 0.75rem;color:rgba(255,255,255,0.5);background:none;border:none;cursor:pointer;transition:background 0.15s;"
                onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='none'">
                <span class="bo-section-label"
                    style="display:flex;align-items:center;gap:0.5rem;font-size:0.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;">
                    Customer Data
                </span>
                <svg class="bo-section-chevron rotated" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    width="12" height="12" style="width:12px;height:12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="bo-section-items" style="margin-top:0.25rem;">
                <a href="{{ route('backoffice.dashboard') }}" class="bo-nav-item group"
                    style="display:flex;align-items:center;gap:0.625rem;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.8125rem;text-decoration:none;transition:background 0.15s;{{ $active === 'customer' ? 'background:rgba(255,255,255,0.12);font-weight:600;color:#fff;' : 'color:rgba(255,255,255,0.7);' }}"
                    onmouseover="this.style.background='rgba(255,255,255,0.1)';this.style.color='#fff'"
                    onmouseout="this.style.background='{{ $active === 'customer' ? 'rgba(255,255,255,0.12)' : 'transparent' }}';this.style.color='{{ $active === 'customer' ? '#fff' : 'rgba(255,255,255,0.7)' }}'">
                    <svg style="width:18px;height:18px;flex-shrink:0;{{ $active === 'customer' ? 'color:#fff;' : 'color:rgba(255,255,255,0.45);' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="18"
                        height="18">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span class="bo-label">Customer</span>
                    <span class="bo-tooltip">Customer</span>
                </a>
            </div>
        </div>

        {{-- Section: AI AGENT --}}
        <div class="bo-section" data-section="ai-agent">
            <button type="button" class="bo-section-header"
                style="display:flex;width:100%;align-items:center;justify-content:space-between;border-radius:0.5rem;padding:0.5rem 0.75rem;color:rgba(255,255,255,0.5);background:none;border:none;cursor:pointer;transition:background 0.15s;"
                onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='none'">
                <span class="bo-section-label"
                    style="display:flex;align-items:center;gap:0.5rem;font-size:0.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;">
                    AI Agent
                </span>
                <svg class="bo-section-chevron rotated" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    width="12" height="12" style="width:12px;height:12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="bo-section-items" style="margin-top:0.25rem;">
                {{-- Agents --}}
                <a href="{{ route('backoffice.chat-agents.index') }}" class="bo-nav-item group"
                    style="display:flex;align-items:center;gap:0.625rem;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.8125rem;text-decoration:none;transition:background 0.15s;{{ ($boActive ?? ($active ?? '')) === 'chat-agents' ? 'background:rgba(255,255,255,0.12);font-weight:600;color:#fff;' : 'color:rgba(255,255,255,0.7);' }}"
                    onmouseover="this.style.background='rgba(255,255,255,0.1)';this.style.color='#fff'"
                    onmouseout="this.style.background='{{ ($boActive ?? ($active ?? '')) === 'chat-agents' ? 'rgba(255,255,255,0.12)' : 'transparent' }}';this.style.color='{{ ($boActive ?? ($active ?? '')) === 'chat-agents' ? '#fff' : 'rgba(255,255,255,0.7)' }}'">
                    <svg style="width:18px;height:18px;flex-shrink:0;{{ ($boActive ?? ($active ?? '')) === 'chat-agents' ? 'color:#fff;' : 'color:rgba(255,255,255,0.45);' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="18"
                        height="18">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                    </svg>
                    <span class="bo-label">Agents</span>
                    <span class="bo-tooltip">Agents</span>
                </a>

                {{-- Tools --}}
                <a href="{{ route('backoffice.tools.index') }}" class="bo-nav-item group"
                    style="display:flex;align-items:center;gap:0.625rem;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.8125rem;text-decoration:none;transition:background 0.15s;{{ ($boActive ?? ($active ?? '')) === 'tools' ? 'background:rgba(255,255,255,0.12);font-weight:600;color:#fff;' : 'color:rgba(255,255,255,0.7);' }}"
                    onmouseover="this.style.background='rgba(255,255,255,0.1)';this.style.color='#fff'"
                    onmouseout="this.style.background='{{ ($boActive ?? ($active ?? '')) === 'tools' ? 'rgba(255,255,255,0.12)' : 'transparent' }}';this.style.color='{{ ($boActive ?? ($active ?? '')) === 'tools' ? '#fff' : 'rgba(255,255,255,0.7)' }}'">
                    <svg style="width:18px;height:18px;flex-shrink:0;{{ ($boActive ?? ($active ?? '')) === 'tools' ? 'color:#fff;' : 'color:rgba(255,255,255,0.45);' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="18"
                        height="18">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11.42 15.17l-5.1 5.1a2.121 2.121 0 01-3-3l5.1-5.1m0 0L4.16 7.91a2.13 2.13 0 010-3l1.26-1.26a2.13 2.13 0 013 0l4.26 4.26m-1.26 1.26l6.97-6.97a2.121 2.121 0 013 3l-6.97 6.97" />
                    </svg>
                    <span class="bo-label">Tools</span>
                    <span class="bo-tooltip">Tools</span>
                </a>

                {{-- Data Models --}}
                <a href="{{ route('backoffice.data-models.index') }}" class="bo-nav-item group"
                    style="display:flex;align-items:center;gap:0.625rem;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.8125rem;text-decoration:none;transition:background 0.15s;{{ ($boActive ?? ($active ?? '')) === 'data-models' ? 'background:rgba(255,255,255,0.12);font-weight:600;color:#fff;' : 'color:rgba(255,255,255,0.7);' }}"
                    onmouseover="this.style.background='rgba(255,255,255,0.1)';this.style.color='#fff'"
                    onmouseout="this.style.background='{{ ($boActive ?? ($active ?? '')) === 'data-models' ? 'rgba(255,255,255,0.12)' : 'transparent' }}';this.style.color='{{ ($boActive ?? ($active ?? '')) === 'data-models' ? '#fff' : 'rgba(255,255,255,0.7)' }}'">
                    <svg style="width:18px;height:18px;flex-shrink:0;{{ ($boActive ?? ($active ?? '')) === 'data-models' ? 'color:#fff;' : 'color:rgba(255,255,255,0.45);' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="18"
                        height="18">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                    </svg>
                    <span class="bo-label">Data Models</span>
                    <span class="bo-tooltip">Data Models</span>
                </a>
            </div>
        </div>

        {{-- Section: SYSTEM --}}
        <div class="bo-section" data-section="system">
            <button type="button" class="bo-section-header"
                style="display:flex;width:100%;align-items:center;justify-content:space-between;border-radius:0.5rem;padding:0.5rem 0.75rem;color:rgba(255,255,255,0.5);background:none;border:none;cursor:pointer;transition:background 0.15s;"
                onmouseover="this.style.background='rgba(255,255,255,0.06)'"
                onmouseout="this.style.background='none'">
                <span class="bo-section-label"
                    style="display:flex;align-items:center;gap:0.5rem;font-size:0.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;">
                    System
                </span>
                <svg class="bo-section-chevron rotated" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    width="12" height="12" style="width:12px;height:12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="bo-section-items" style="margin-top:0.25rem;">
                <a href="{{ route('backoffice.settings.index') }}" class="bo-nav-item group"
                    style="display:flex;align-items:center;gap:0.625rem;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.8125rem;text-decoration:none;transition:background 0.15s;{{ ($boActive ?? ($active ?? '')) === 'settings' ? 'background:rgba(255,255,255,0.12);font-weight:600;color:#fff;' : 'color:rgba(255,255,255,0.7);' }}"
                    onmouseover="this.style.background='rgba(255,255,255,0.1)';this.style.color='#fff'"
                    onmouseout="this.style.background='{{ ($boActive ?? ($active ?? '')) === 'settings' ? 'rgba(255,255,255,0.12)' : 'transparent' }}';this.style.color='{{ ($boActive ?? ($active ?? '')) === 'settings' ? '#fff' : 'rgba(255,255,255,0.7)' }}'">
                    <svg style="width:18px;height:18px;flex-shrink:0;{{ ($boActive ?? ($active ?? '')) === 'settings' ? 'color:#fff;' : 'color:rgba(255,255,255,0.45);' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="18"
                        height="18">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                    </svg>
                    <span class="bo-label">Settings</span>
                    <span class="bo-tooltip">Settings</span>
                </a>

                {{-- DB Connections --}}
                <a href="{{ route('backoffice.database-connections.index') }}" class="bo-nav-item group"
                    style="display:flex;align-items:center;gap:0.625rem;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.8125rem;text-decoration:none;transition:background 0.15s;{{ ($boActive ?? ($active ?? '')) === 'database-connections' ? 'background:rgba(255,255,255,0.12);font-weight:600;color:#fff;' : 'color:rgba(255,255,255,0.7);' }}"
                    onmouseover="this.style.background='rgba(255,255,255,0.1)';this.style.color='#fff'"
                    onmouseout="this.style.background='{{ ($boActive ?? ($active ?? '')) === 'database-connections' ? 'rgba(255,255,255,0.12)' : 'transparent' }}';this.style.color='{{ ($boActive ?? ($active ?? '')) === 'database-connections' ? '#fff' : 'rgba(255,255,255,0.7)' }}'">
                    <svg style="width:18px;height:18px;flex-shrink:0;{{ ($boActive ?? ($active ?? '')) === 'database-connections' ? 'color:#fff;' : 'color:rgba(255,255,255,0.45);' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="18"
                        height="18">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                    </svg>
                    <span class="bo-label">DB Connections</span>
                    <span class="bo-tooltip">DB Connections</span>
                </a>
            </div>
        </div>

    </nav>
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

        // Section collapse / expand
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
