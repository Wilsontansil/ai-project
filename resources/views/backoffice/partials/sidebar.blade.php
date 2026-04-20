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

    /* ── Responsive (mobile drawer) ── */
    @media (max-width: 1023px) {

        /* When sidebar is open as mobile drawer, always show full labels */
        #bo-shell.bo-collapsed #bo-sidebar.bo-mobile-open {
            width: 280px !important;
            min-width: 280px !important;
            overflow: visible !important;
        }

        #bo-shell.bo-collapsed #bo-sidebar.bo-mobile-open .bo-label,
        #bo-shell.bo-collapsed #bo-sidebar.bo-mobile-open .bo-section-header {
            display: flex !important;
        }

        #bo-shell.bo-collapsed #bo-sidebar.bo-mobile-open .bo-section-label,
        #bo-shell.bo-collapsed #bo-sidebar.bo-mobile-open .bo-section-chevron {
            display: flex !important;
        }

        #bo-shell.bo-collapsed #bo-sidebar.bo-mobile-open .bo-section-items {
            padding-left: 0.75rem !important;
            margin-left: 0.75rem !important;
            border-left: 2px solid rgba(255, 255, 255, 0.1) !important;
        }

        #bo-shell.bo-collapsed #bo-sidebar.bo-mobile-open .bo-nav-item {
            justify-content: flex-start !important;
            padding: 0.5rem 0.75rem !important;
            gap: 0.625rem !important;
        }

        #bo-shell.bo-collapsed #bo-sidebar.bo-mobile-open .bo-brand-text {
            display: flex !important;
        }

        #bo-shell.bo-collapsed #bo-sidebar.bo-mobile-open .bo-brand-area {
            justify-content: space-between !important;
            padding: 1rem 1.25rem !important;
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
                    {{ __('backoffice.section.customer_data') }}
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
                    <span class="bo-label">{{ __('backoffice.menu.customer') }}</span>
                    <span class="bo-tooltip">{{ __('backoffice.menu.customer') }}</span>
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
                    {{ __('backoffice.section.ai_agent') }}
                </span>
                <svg class="bo-section-chevron rotated" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    width="12" height="12" style="width:12px;height:12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="bo-section-items" style="margin-top:0.25rem;">
                {{-- Agents --}}
                @can('manage agents')
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
                        <span class="bo-label">{{ __('backoffice.menu.agents') }}</span>
                        <span class="bo-tooltip">{{ __('backoffice.menu.agents') }}</span>
                    </a>
                @endcan

                {{-- Tools --}}
                @can('manage tools')
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
                        <span class="bo-label">{{ __('backoffice.menu.tools') }}</span>
                        <span class="bo-tooltip">{{ __('backoffice.menu.tools') }}</span>
                    </a>
                @endcan

                {{-- Data Models --}}
                @can('manage data-models')
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
                        <span class="bo-label">{{ __('backoffice.menu.data_models') }}</span>
                        <span class="bo-tooltip">{{ __('backoffice.menu.data_models') }}</span>
                    </a>
                @endcan

                {{-- Website Pages --}}
                @can('manage settings')
                    <a href="{{ route('backoffice.website-pages.index') }}" class="bo-nav-item group"
                        style="display:flex;align-items:center;gap:0.625rem;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.8125rem;text-decoration:none;transition:background 0.15s;{{ ($boActive ?? ($active ?? '')) === 'website-pages' ? 'background:rgba(255,255,255,0.12);font-weight:600;color:#fff;' : 'color:rgba(255,255,255,0.7);' }}"
                        onmouseover="this.style.background='rgba(255,255,255,0.1)';this.style.color='#fff'"
                        onmouseout="this.style.background='{{ ($boActive ?? ($active ?? '')) === 'website-pages' ? 'rgba(255,255,255,0.12)' : 'transparent' }}';this.style.color='{{ ($boActive ?? ($active ?? '')) === 'website-pages' ? '#fff' : 'rgba(255,255,255,0.7)' }}'">
                        <svg style="width:18px;height:18px;flex-shrink:0;{{ ($boActive ?? ($active ?? '')) === 'website-pages' ? 'color:#fff;' : 'color:rgba(255,255,255,0.45);' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="18"
                            height="18">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                        <span class="bo-label">{{ __('backoffice.menu.website_pages') }}</span>
                        <span class="bo-tooltip">{{ __('backoffice.menu.website_pages') }}</span>
                    </a>
                @endcan
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
                    {{ __('backoffice.section.system') }}
                </span>
                <svg class="bo-section-chevron rotated" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    width="12" height="12" style="width:12px;height:12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="bo-section-items" style="margin-top:0.25rem;">
                @can('manage settings')
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
                        <span class="bo-label">{{ __('backoffice.menu.settings') }}</span>
                        <span class="bo-tooltip">{{ __('backoffice.menu.settings') }}</span>
                    </a>
                @endcan

                {{-- DB Connections --}}
                @can('manage database-connections')
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
                        <span class="bo-label">{{ __('backoffice.menu.database_connections') }}</span>
                        <span class="bo-tooltip">{{ __('backoffice.menu.database_connections') }}</span>
                    </a>
                @endcan

                {{-- Metrics --}}
                @can('view metrics')
                    <a href="{{ route('backoffice.metrics.index') }}" class="bo-nav-item group"
                        style="display:flex;align-items:center;gap:0.625rem;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.8125rem;text-decoration:none;transition:background 0.15s;{{ ($boActive ?? ($active ?? '')) === 'metrics' ? 'background:rgba(255,255,255,0.12);font-weight:600;color:#fff;' : 'color:rgba(255,255,255,0.7);' }}"
                        onmouseover="this.style.background='rgba(255,255,255,0.1)';this.style.color='#fff'"
                        onmouseout="this.style.background='{{ ($boActive ?? ($active ?? '')) === 'metrics' ? 'rgba(255,255,255,0.12)' : 'transparent' }}';this.style.color='{{ ($boActive ?? ($active ?? '')) === 'metrics' ? '#fff' : 'rgba(255,255,255,0.7)' }}'">
                        <svg style="width:18px;height:18px;flex-shrink:0;{{ ($boActive ?? ($active ?? '')) === 'metrics' ? 'color:#fff;' : 'color:rgba(255,255,255,0.45);' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="18"
                            height="18">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                        <span class="bo-label">{{ __('backoffice.menu.metrics') }}</span>
                        <span class="bo-tooltip">{{ __('backoffice.menu.metrics') }}</span>
                    </a>
                @endcan

                {{-- Users --}}
                @can('manage users')
                    <a href="{{ route('backoffice.users.index') }}" class="bo-nav-item group"
                        style="display:flex;align-items:center;gap:0.625rem;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.8125rem;text-decoration:none;transition:background 0.15s;{{ ($boActive ?? ($active ?? '')) === 'users' ? 'background:rgba(255,255,255,0.12);font-weight:600;color:#fff;' : 'color:rgba(255,255,255,0.7);' }}"
                        onmouseover="this.style.background='rgba(255,255,255,0.1)';this.style.color='#fff'"
                        onmouseout="this.style.background='{{ ($boActive ?? ($active ?? '')) === 'users' ? 'rgba(255,255,255,0.12)' : 'transparent' }}';this.style.color='{{ ($boActive ?? ($active ?? '')) === 'users' ? '#fff' : 'rgba(255,255,255,0.7)' }}'">
                        <svg style="width:18px;height:18px;flex-shrink:0;{{ ($boActive ?? ($active ?? '')) === 'users' ? 'color:#fff;' : 'color:rgba(255,255,255,0.45);' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="18"
                            height="18">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                        <span class="bo-label">{{ __('backoffice.menu.users') }}</span>
                        <span class="bo-tooltip">{{ __('backoffice.menu.users') }}</span>
                    </a>
                @endcan
            </div>
        </div>

    </nav>
</aside>

<script>
    (() => {
        // Section collapse / expand
        const sectionKey = 'backoffice_sections';
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
