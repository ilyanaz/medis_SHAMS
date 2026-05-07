<?php
require_once dirname(__DIR__) . '/auth/view_bootstrap.php';

if (! function_exists('medis_nav_icon')) {
    function medis_nav_icon(string $name): string
    {
        $icons = [
            'menu' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"></path><path d="M4 12h16"></path><path d="M4 17h16"></path></svg>',
            'sidebar_toggle' => '<svg class="sidebar-toggle-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"></rect><path d="M16 3v18"></path><path class="toggle-chevron" d="M11.5 8 8 12l3.5 4"></path></svg>',
            'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="1"></rect><rect x="13" y="3" width="8" height="8" rx="1"></rect><rect x="3" y="13" width="8" height="8" rx="1"></rect><rect x="13" y="13" width="8" height="8" rx="1"></rect></svg>',
            'surveillance' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3h5"></path><path d="M9.2 3v5.6L5.4 16.2A2.8 2.8 0 0 0 7.8 20h7.4a2.8 2.8 0 0 0 2.4-3.8l-3.8-7.6V6.8"></path><path d="M7.5 14.5h8.8"></path><path d="M8.4 10.8h5.2"></path><path d="M16.2 4.4l4.3 1.6"></path><path d="M15.1 6.3l4.3 1.6"></path><path d="M16.4 3.7l1.3-2 2.9 1.7-1.2 2"></path><path d="M13.7 9.1c.4-.8 1.2-1.4 2.2-1.7"></path><path d="M13.3 11.1a1.3 1.3 0 1 0 0-2.6 1.3 1.3 0 0 0 0 2.6Z"></path></svg>',
            'audiometry' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16.2 4.2a5.4 5.4 0 0 0-5.4 5.4c0 2 1 3.3 2 4.2.8.7 1.3 1.3 1.3 2.2"></path><path d="M16.2 4.2a4.7 4.7 0 0 1 4.7 4.7c0 2.2-1.2 3.7-2.7 4.9-1 .8-1.6 1.7-1.6 3.1"></path><path d="M15.9 17.1c-.2 1.7-1 2.8-2 3.6"></path><path d="M7.5 11.8h2.2l1.1-2.2 1.6 4.3 1.1-2.1h1.8"></path></svg>',
            'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4"></path><path d="M8 3v4"></path><path d="M3 10h18"></path></svg>',
            'examination' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4v5a4 4 0 0 0 8 0V4"></path><path d="M8 4H6.8A1.8 1.8 0 0 0 5 5.8V9a7 7 0 0 0 14 0V5.8A1.8 1.8 0 0 0 17.2 4H16"></path><path d="M12 16v2.5c0 1.7 1.3 3 3 3h.3"></path><path d="M17.3 21.5a1.8 1.8 0 1 0 0-3.6 1.8 1.8 0 0 0 0 3.6Z"></path></svg>',
            'report' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7z"></path><path d="M14 3v5h5"></path><path d="M10 13h6"></path><path d="M10 17h4"></path></svg>',
            'settings' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.54V22a2 2 0 0 1-4 0v-.09a1.7 1.7 0 0 0-1-1.54 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.54-1H3a2 2 0 0 1 0-4h.09a1.7 1.7 0 0 0 1.54-1 1.7 1.7 0 0 0-.34-1.87l-.06-.06A2 2 0 1 1 7.06 4.2l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.54V3a2 2 0 0 1 4 0v.09a1.7 1.7 0 0 0 1 1.54 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.23.58.8.97 1.43 1H21a2 2 0 0 1 0 4h-.09c-.63.03-1.2.42-1.51 1z"></path></svg>',
            'help' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M9.1 9a3 3 0 1 1 5.3 2c-.9.9-1.4 1.4-1.4 3"></path><path d="M12 17h.01"></path></svg>',
            'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>',
            'mail' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 7l9 6 9-6"></path></svg>',
            'bell' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9"></path><path d="M10 21a2 2 0 0 0 4 0"></path></svg>',
            'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>',
            'profile' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"></circle><path d="M5 19a7 7 0 0 1 14 0"></path></svg>',
            'workplaces' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v11H4z"></path><path d="M4 7l4-4h8l4 4"></path></svg>',
            'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"></circle><path d="M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6"></path></svg>',
            'inquiries' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v12H5.7L4 18.5V4z"></path><path d="M4 4l8 7 8-7"></path></svg>',
            'banners' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h10v16H5z"></path><path d="M15 4h4v4h-4z"></path></svg>',
            'blogs' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10v18H7z"></path><path d="M7 7h10"></path><path d="M7 12h10"></path><path d="M7 17h6"></path></svg>',
            'document' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7z"></path><path d="M14 3v5h5"></path><path d="M10 13h6"></path><path d="M10 17h4"></path></path></svg>',
            'shortcuts' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8h.01"></path><path d="M16 8h.01"></path><path d="M8 16h.01"></path><path d="M16 16h.01"></path><path d="M9.5 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"></path><path d="M17.5 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"></path><path d="M9.5 16a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"></path><path d="M17.5 16a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"></path></svg>',
        ];

        return $icons[$name] ?? $icons['dashboard'];
    }
}

if (! function_exists('medis_named_route')) {
    function medis_named_route(array $names, string $fallback = '#'): string
    {
        if (! function_exists('route') || ! class_exists('\\Illuminate\\Support\\Facades\\Route')) {
            return $fallback;
        }

        foreach ($names as $name) {
            try {
                if (\Illuminate\Support\Facades\Route::has($name)) {
                    return route($name);
                }
            } catch (\Throwable $e) {
            }
        }

        return $fallback;
    }
}

if (! function_exists('medis_initials')) {
    function medis_initials(string $value, int $limit = 2): string
    {
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, $limit) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        if ($initials !== '') {
            return $initials;
        }

        $fallbackInitial = strtoupper((string) substr($value, 0, 1));

        return $fallbackInitial !== '' ? $fallbackInitial : 'C';
    }
}

if (! function_exists('medis_render_navigation_start')) {
    function medis_render_navigation_start(array $config = []): void
    {
        $clinicName = $config['clinicName'] ?? 'Medis SHAMS';
        $defaultExpandedLogoUrl = function_exists('asset') ? asset('images/logos/medis-logo-left-right.png') : '/images/logos/medis-logo-left-right.png';
        $defaultCollapsedLogoUrl = function_exists('asset') ? asset('images/logos/medis-logo-up-down.png') : '/images/logos/medis-logo-up-down.png';
        $expandedLogoUrl = (string) ($config['expandedLogoUrl'] ?? $defaultExpandedLogoUrl);
        $collapsedLogoUrl = (string) ($config['collapsedLogoUrl'] ?? $defaultCollapsedLogoUrl);
        $systemLogoUrl = (string) ($config['systemLogoUrl'] ?? $expandedLogoUrl);
        $username = $config['username'] ?? 'User';
        $active = $config['active'] ?? 'dashboard';
        $bodyClass = trim((string) ($config['bodyClass'] ?? ''));
        $brandText = trim((string) ($config['brandText'] ?? ''));
        $showThemeControls = (bool) ($config['showThemeControls'] ?? false);
        $showLanguageControls = (bool) ($config['showLanguageControls'] ?? false);
        $esc = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $dashboardUrl = medis_named_route(['panel.dashboard', 'dashboard']);
        $surveillanceUrl = medis_named_route(['surveillance.company']);
        $audiometryUrl = medis_named_route(['audiometry.company']);
        $logoutUrl = medis_named_route(['logout.page', 'panel.logout']);
        $profileUrl = medis_named_route(['profile']);
        $settingsUrl = medis_named_route(['settings', 'admin.settings', 'panel.settings']);
        $accountSettingsUrl = medis_named_route(['account.settings', 'panel.account_settings']);
        $reportUrl = medis_named_route(['general.report', 'surveillance.report']);
        $examinationUrl = medis_named_route(['general.examination']);
        $pdfMode = ! empty($config['pdfMode']);
        $GLOBALS['medis_pdf_mode'] = $pdfMode;
        $resolvedUser = class_exists('\\Illuminate\\Support\\Facades\\Auth') ? \Illuminate\Support\Facades\Auth::user() : null;
        $firstName = trim((string) (($resolvedUser->first_name ?? $resolvedUser->firstName ?? '') ?: ''));
        $lastName = trim((string) (($resolvedUser->last_name ?? $resolvedUser->lastName ?? '') ?: ''));
        $nameField = trim((string) (($resolvedUser->name ?? '') ?: ''));
        $role = strtolower((string) (($resolvedUser->role ?? '') ?: ''));
        $rawUsername = trim((string) (($resolvedUser->username ?? $username) ?: $username));

        if ($role === 'admin' || strtolower($rawUsername) === 'admin') {
            $displayName = 'Admin';
        } elseif ($firstName !== '' || $lastName !== '') {
            $displayName = trim($firstName . ' ' . $lastName);
        } elseif ($nameField !== '') {
            $displayName = $nameField;
        } else {
            $displayName = $rawUsername !== '' ? $rawUsername : 'User';
        }

        $sessionStore = function_exists('session') ? session() : null;
        $panelOriginalRole = strtolower((string) (
            $sessionStore?->get('panel_user_original_role')
            ?? $sessionStore?->get('panel_user_role')
            ?? ''
        ));
        $panelMode = strtolower((string) (
            $sessionStore?->get('panel_mode')
            ?? ($panelOriginalRole === 'admin' ? 'admin' : 'clinic')
        ));
        $activeClinicId = (int) ($sessionStore?->get('active_clinic_id', 0) ?? 0);
        $clinicSwitcherAdminUrl = medis_named_route(['panel.admin.switch']);
        $clinicAdminPageUrl = medis_named_route(['admin.dashboard', 'panel.admin_dashboard']);
        $clinicSwitcherManageUrl = medis_named_route(['admin.clinic_list', 'admin.clinic_setup', 'panel.clinic_list', 'panel.clinic_setup']);
        $clinicSwitcherToken = function_exists('csrf_token') ? (string) csrf_token() : '';
        $clinicRecords = [];
        $activeClinic = null;

        if (class_exists('\\Illuminate\\Support\\Facades\\DB')) {
            try {
                $clinicQuery = \Illuminate\Support\Facades\DB::table('clinic')
                    ->select('clinic_id', 'clinic_name', 'clinic_email');

                if (class_exists('\\Illuminate\\Support\\Facades\\Schema')
                    && \Illuminate\Support\Facades\Schema::hasColumn('clinic', 'clinic_status')) {
                    $clinicQuery->where('clinic_status', 'active');
                }

                $clinicRecords = $clinicQuery
                    ->orderBy('clinic_name')
                    ->get()
                    ->map(static function ($clinic) {
                        return [
                            'id' => (int) $clinic->clinic_id,
                            'name' => (string) $clinic->clinic_name,
                            'email' => (string) ($clinic->clinic_email ?? ''),
                        ];
                    })
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                $clinicRecords = [];
            }
        }

        foreach ($clinicRecords as $clinicRecord) {
            if ($clinicRecord['id'] === $activeClinicId) {
                $activeClinic = $clinicRecord;
                break;
            }
        }

        $inAdminMode = $panelMode === 'admin';

        if ($inAdminMode) {
            $displayName = 'Admin';
        }

        if (! $inAdminMode && $activeClinic !== null) {
            $clinicName = $activeClinic['name'];
        }

        $clinicSwitcherLabel = $inAdminMode || $activeClinic === null ? 'Admin Panel' : $clinicName;
        $clinicSwitcherMeta = $inAdminMode ? 'Choose a clinic to enter daily navigation' : 'Current clinic navigation';
        $clinicSwitcherInitials = medis_initials($clinicSwitcherLabel);

        $userEmail = strtolower(str_replace(' ', '.', $rawUsername !== '' ? $rawUsername : $displayName)) . '@' . strtolower(preg_replace('/\s+/', '', $clinicName)) . '.com';

        $defaultNavItems = [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => $dashboardUrl, 'icon' => 'dashboard'],
            ['key' => 'surveillance', 'label' => 'Surveillance', 'href' => $surveillanceUrl, 'icon' => 'surveillance'],
            ['key' => 'audiometry', 'label' => 'Audiometry', 'href' => $audiometryUrl, 'icon' => 'audiometry'],
            ['key' => 'examination', 'label' => 'Examination', 'href' => $examinationUrl, 'icon' => 'examination'],
            ['key' => 'report', 'label' => 'Report', 'href' => $reportUrl, 'icon' => 'report'],
        ];

        $defaultGeneralItems = [
            ['key' => 'settings', 'label' => 'Settings', 'href' => $settingsUrl, 'icon' => 'settings'],
            ['key' => 'logout', 'label' => 'Logout', 'href' => $logoutUrl, 'icon' => 'logout'],
        ];

        $navItems = $config['navItems'] ?? $defaultNavItems;
        $generalItems = $config['generalItems'] ?? $defaultGeneralItems;
        $navGroups = isset($config['navGroups']) && is_array($config['navGroups'])
            ? $config['navGroups']
            : [];

        if ($pdfMode) {
?>
<style>
    body{margin:0;background:#fff;color:#0f172a;font-family:"Poppins","Segoe UI",Tahoma,Geneva,Verdana,sans-serif}
    .pdf-page{padding:0;background:#fff}
    .pdf-card{padding:0;background:#fff}
</style>
<div class="pdf-page"><div class="pdf-card">
<?php
            return;
        }
?>
<style>
    :root{--nav-bg:#f5f6f8;--panel:#fff;--panel-2:#fbfbfc;--line:#e5e7eb;--text:#0f172a;--muted:#94a3b8;--green:#389B5B;--green-2:#319755;--shadow:rgba(15,23,42,.12)}
    body[data-theme="dark"]{--nav-bg:#0f172a;--panel:#111827;--panel-2:#0b1220;--line:#263244;--text:#e5eefb;--muted:#94a3b8;--green:#49a96a;--green-2:#378b55;--shadow:rgba(0,0,0,.35)}
    *{box-sizing:border-box}
    body{margin:0;background:var(--nav-bg);color:var(--text);font-family:"Poppins","Segoe UI",Tahoma,Geneva,Verdana,sans-serif;transition:background .2s ease,color .2s ease}
    .app-shell{height:100vh;display:grid;grid-template-columns:228px 1fr;overflow:hidden}
    .app-shell.is-collapsed{grid-template-columns:84px 1fr}
    .app-shell.is-mobile-nav-open{overflow:hidden}
    .app-sidebar{height:100vh;overflow:hidden;background:var(--panel-2);border-right:1px solid var(--line);padding:12px 8px 12px 10px;display:flex;flex-direction:column;gap:8px}
    .app-sidebar-backdrop{display:none}
    .app-brand-row{display:flex;align-items:flex-start;gap:6px;padding:2px 4px 2px}
    .app-brand{display:flex;align-items:center;justify-content:center;gap:0;min-width:0;flex:1}
    .app-brand-logo{width:132px;height:52px;border-radius:12px;background:transparent;border:0;display:inline-flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0}
    .app-brand-logo img{width:100%;height:100%;object-fit:contain;padding:0}
    .app-brand-text{display:none}
    .app-clinic-panel{position:relative}
    .app-clinic-card{width:100%;border:1px solid var(--line);border-radius:18px;background:var(--panel);padding:10px;display:flex;align-items:center;gap:10px;cursor:pointer;appearance:none;text-align:left}
    .app-clinic-card:hover,.app-clinic-option:hover{background:rgba(148,163,184,.08)}
    .app-clinic-meta{display:grid;gap:3px;min-width:0;flex:1}
    .app-clinic-meta strong,.app-clinic-option-text strong{font-size:.95rem;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .app-clinic-meta span,.app-clinic-option-text span{font-size:.78rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .app-clinic-chevron{color:#64748b;font-size:1rem;line-height:1;flex-shrink:0}
    .app-clinic-menu{position:absolute;left:4px;right:4px;top:calc(100% + 8px);border:1px solid var(--line);border-radius:22px;background:var(--panel);padding:10px;box-shadow:0 18px 40px var(--shadow);display:none;z-index:55;max-height:min(70vh,460px);overflow:auto}
    .app-clinic-panel.is-open .app-clinic-menu{display:block}
    .app-clinic-menu-section + .app-clinic-menu-section{margin-top:10px;padding-top:10px;border-top:1px solid var(--line)}
    .app-clinic-menu-title{padding:2px 6px 8px;color:var(--muted);font-size:.76rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em}
    .app-clinic-option-form{margin:0}
    .app-clinic-option,.app-clinic-link{width:100%;border:0;background:transparent;border-radius:14px;padding:10px 8px;display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text);cursor:pointer}
    .app-clinic-option.is-active{background:#eef4f0}
    body[data-theme="dark"] .app-clinic-option.is-active{background:rgba(56,155,91,.16)}
    .app-clinic-option-icon{width:30px;height:30px;border-radius:999px;background:#eff6f0;color:#2f855a;display:inline-flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0}
    .app-clinic-option-text{display:grid;gap:2px;min-width:0;flex:1;text-align:left}
    body.admin-shell .app-brand-text{display:flex;flex-direction:column;align-items:flex-start;gap:2px;margin-left:8px}
    body.admin-shell .app-brand-text strong{font-size:.95rem;color:#0f172a}
    body.admin-shell .app-brand-text span{font-size:.82rem;color:#64748b;display:none}
    body.admin-shell .app-brand-row{margin-bottom:24px}
    body.admin-shell .app-toggle-row{display:none}
    body.admin-shell .app-sidebar-tools{display:none}
    body.admin-shell .app-user-panel{display:none}
    .app-toggle-row{display:flex;justify-content:center;padding:0 6px 4px}
    .app-toggle{width:24px;height:24px;border:0;border-radius:0;background:transparent;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;padding:0}
    .app-toggle svg,.app-nav-link svg,.app-top-action svg,.app-user-menu-link svg,.switch-btn svg,.app-mobile-menu-btn svg{width:18px;height:18px;stroke:#475569;fill:none;stroke-width:1.85;stroke-linecap:round;stroke-linejoin:round}
    .app-toggle .sidebar-toggle-icon{width:18px;height:18px}
    .app-toggle .toggle-chevron{transition:transform .18s ease;transform-origin:50% 50%}
    .app-shell.is-collapsed .app-toggle .toggle-chevron{transform:scaleX(-1)}
    body[data-theme="dark"] .app-toggle svg,body[data-theme="dark"] .app-nav-link svg,body[data-theme="dark"] .app-top-action svg,body[data-theme="dark"] .app-user-menu-link svg,body[data-theme="dark"] .switch-btn svg,body[data-theme="dark"] .app-mobile-menu-btn svg{stroke:#cbd5e1}
    .app-nav-group{display:grid;gap:4px}
    .app-nav-caption{padding:8px 8px 2px;color:var(--muted);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em}
    .app-nav-link{display:flex;align-items:center;gap:9px;padding:9px 10px;border:1px solid transparent;border-radius:14px;text-decoration:none;color:var(--text);font-size:.98rem;line-height:1.2;transition:.18s ease}
    .app-nav-link:hover{background:rgba(148,163,184,.08)}
    .app-nav-link.active{background:#eef4f0;border-color:#dbe8df;font-weight:700;color:#14321f}
    body[data-theme="dark"] .app-nav-link.active{background:rgba(56,155,91,.16);border-color:rgba(73,169,106,.26);color:#d8f4e1}
    .app-nav-link .icon{width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}
    .app-nav-link .icon svg{width:18px;height:18px}
    .app-nav-link .label{display:inline-flex;align-items:center}
    .app-sidebar-tools{display:grid;gap:10px;margin-top:8px}
    .switch-card{border:1px solid var(--line);border-radius:16px;background:var(--panel);padding:10px}
    .switch-card h4{margin:0 0 8px;font-size:.8rem;color:var(--text)}
    .switch-row{display:grid;grid-template-columns:1fr 1fr;gap:6px;background:rgba(148,163,184,.08);padding:4px;border-radius:12px}
    .switch-btn{border:0;background:transparent;color:#64748b;border-radius:10px;padding:7px 8px;font:inherit;font-size:.76rem;display:flex;align-items:center;justify-content:center;gap:5px;cursor:pointer}
    .switch-btn.is-active{background:var(--panel);color:var(--text);box-shadow:0 6px 16px var(--shadow)}
    body[data-theme="dark"] .switch-btn{color:#9fb0c8}
    body[data-theme="dark"] .switch-btn.is-active{background:#172033;color:#e5eefb}
    .app-user-panel{margin-top:auto;position:relative}
    .app-user-card{width:100%;border:1px solid var(--line);border-radius:18px;background:var(--panel);padding:8px 10px;display:flex;align-items:center;gap:8px;cursor:pointer;appearance:none}
    .app-avatar{width:34px;height:34px;border-radius:999px;background:#eff2f6;display:inline-flex;align-items:center;justify-content:center;font-weight:700;color:#334155;flex-shrink:0}
    body[data-theme="dark"] .app-avatar{background:#1e293b;color:#e2e8f0}
    .app-user-meta{display:grid;gap:3px;min-width:0;text-align:left}
    .app-user-meta strong{font-size:.96rem;color:var(--text)}
    .app-user-meta span{font-size:.82rem;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    body[data-theme="dark"] .app-user-meta span{color:#8aa0bf}
    .app-user-menu{position:absolute;left:0;bottom:calc(100% + 12px);width:260px;border:1px solid var(--line);border-radius:24px;background:var(--panel);padding:14px 16px;box-shadow:0 18px 40px var(--shadow);display:none;z-index:40}
    .app-user-panel.is-open .app-user-menu{display:block}
    .app-user-menu-header{display:flex;align-items:center;gap:12px;padding-bottom:14px;border-bottom:1px solid var(--line)}
    .app-user-menu-list{display:grid;gap:4px;padding:12px 0}
    .app-user-menu-link{display:flex;align-items:center;gap:10px;padding:10px 8px;border-radius:12px;text-decoration:none;color:var(--text);font-size:.94rem}
    .app-user-menu-link:hover{background:rgba(148,163,184,.08)}
    .app-user-menu-footer{padding-top:12px;border-top:1px solid var(--line)}
    .app-main{display:grid;grid-template-rows:auto 1fr;min-width:0;height:100vh;overflow:hidden}
    .app-topbar{background:var(--panel);border-bottom:1px solid var(--line);padding:18px 24px;display:flex;align-items:center;justify-content:space-between;gap:18px}
    .app-topbar-left{display:flex;align-items:center;gap:16px;min-width:0}
    .app-mobile-menu-btn{display:none;width:42px;height:42px;border:1px solid var(--line);border-radius:12px;background:var(--panel);align-items:center;justify-content:center;cursor:pointer;flex-shrink:0}
    .app-mobile-menu-btn svg{width:20px;height:20px}
    .app-heading h1{margin:0;font-size:1.34rem;font-weight:500;color:#64748b}.app-heading h1 strong{color:var(--text);font-weight:700}
    body[data-theme="dark"] .app-heading h1{color:#9fb0c8}
    .app-topbar-right{display:flex;align-items:center;gap:10px;flex-wrap:nowrap;justify-content:flex-end}
    .app-top-action{width:40px;height:40px;border-radius:12px;border:1px solid var(--line);background:var(--panel);display:inline-flex;align-items:center;justify-content:center;text-decoration:none;position:relative}
    .app-top-action.dot::after{content:"";position:absolute;top:10px;right:10px;width:6px;height:6px;border-radius:999px;background:#ef4444}
    .app-topbar .app-clinic-panel{padding:0;min-width:0;flex:0 1 250px}
    .app-topbar .app-clinic-card{width:100%;min-width:0;max-width:250px;padding:7px 10px;border-radius:16px;gap:8px}
    .app-topbar .app-clinic-card .app-avatar{width:30px;height:30px;font-size:.95rem}
    .app-topbar .app-clinic-meta{gap:1px}
    .app-topbar .app-clinic-meta strong{font-size:.86rem;line-height:1.15}
    .app-topbar .app-clinic-meta span{font-size:.74rem;line-height:1.1}
    .app-topbar .app-clinic-chevron{font-size:.85rem}
    .app-topbar .app-clinic-menu{left:auto;right:0;top:calc(100% + 10px);width:min(360px,calc(100vw - 32px))}
    .app-page{padding:22px;height:100%;overflow:auto;min-height:0;scrollbar-width:thin;scrollbar-color:#b9bec8 transparent}.app-page::-webkit-scrollbar,.app-sidebar-tools::-webkit-scrollbar,.app-user-menu::-webkit-scrollbar,.content::-webkit-scrollbar,.main::-webkit-scrollbar,.side::-webkit-scrollbar{width:10px;height:10px}.app-page::-webkit-scrollbar-track,.app-sidebar-tools::-webkit-scrollbar-track,.app-user-menu::-webkit-scrollbar-track,.content::-webkit-scrollbar-track,.main::-webkit-scrollbar-track,.side::-webkit-scrollbar-track{background:transparent;border-left:2px solid #2b6cb0}.app-page::-webkit-scrollbar-thumb,.app-sidebar-tools::-webkit-scrollbar-thumb,.app-user-menu::-webkit-scrollbar-thumb,.content::-webkit-scrollbar-thumb,.main::-webkit-scrollbar-thumb,.side::-webkit-scrollbar-thumb{background:#b9bec8;border-radius:999px;border:2px solid transparent;background-clip:padding-box}.app-page::-webkit-scrollbar-thumb:hover,.app-sidebar-tools::-webkit-scrollbar-thumb:hover,.app-user-menu::-webkit-scrollbar-thumb:hover,.content::-webkit-scrollbar-thumb:hover,.main::-webkit-scrollbar-thumb:hover,.side::-webkit-scrollbar-thumb:hover{background:#9ea6b3;border:2px solid transparent;background-clip:padding-box}
    .app-card{background:var(--panel);border:1px solid var(--line);border-radius:24px;padding:22px}
    .app-shell.is-collapsed .app-nav-link .label,.app-shell.is-collapsed .app-nav-caption,.app-shell.is-collapsed .app-user-meta,.app-shell.is-collapsed .app-sidebar-tools,.app-shell.is-collapsed .app-clinic-meta,.app-shell.is-collapsed .app-clinic-chevron{display:none}
    .app-shell.is-collapsed .app-sidebar{padding-inline:8px}
    .app-shell.is-collapsed .app-brand-row,.app-shell.is-collapsed .app-brand,.app-shell.is-collapsed .app-nav-link,.app-shell.is-collapsed .app-user-card,.app-shell.is-collapsed .app-clinic-card{justify-content:center}
    .app-shell.is-collapsed .app-nav-link{padding:8px}
    .app-shell.is-collapsed .app-user-card{padding:6px;border-radius:999px;width:40px;height:40px;margin-inline:auto}
    .app-shell.is-collapsed .app-avatar{width:26px;height:26px}
    .app-shell.is-collapsed .app-brand-logo{width:44px;height:44px;border-radius:14px}
    .app-shell.is-collapsed .app-user-menu{left:78px;bottom:0}
    .app-shell.is-collapsed .app-clinic-card{padding:6px;border-radius:999px;width:40px;height:40px;margin-inline:auto}
    .app-shell.is-collapsed .app-clinic-menu{left:78px;right:auto;top:0;width:280px}
    .app-shell.is-collapsed .app-toggle-row{justify-content:center;padding:2px 0 6px}
    @media (max-width:1100px){
        body{overflow-x:hidden}
        .app-shell,.app-shell.is-collapsed{grid-template-columns:1fr !important}
        .app-sidebar{display:flex;position:fixed;top:0;left:0;bottom:0;width:min(84vw,320px);max-width:320px;transform:translateX(-100%);transition:transform .22s ease;z-index:50;box-shadow:0 18px 40px var(--shadow)}
        .app-shell.is-mobile-nav-open .app-sidebar{transform:translateX(0)}
        .app-sidebar-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.4);z-index:45}
        .app-shell.is-mobile-nav-open .app-sidebar-backdrop{display:block}
        .app-main{width:100%;min-width:0}
        .app-mobile-menu-btn{display:inline-flex}
        .app-page{padding:16px}
        .app-topbar{padding:14px 16px}
        .app-sidebar,
        .app-shell.is-collapsed .app-sidebar{padding:12px 8px 12px 10px}
        .app-user-meta,
        .app-shell.is-collapsed .app-user-meta{display:grid}
        .app-sidebar-tools,
        .app-shell.is-collapsed .app-sidebar-tools{display:grid}
        .app-clinic-meta,
        .app-shell.is-collapsed .app-clinic-meta,
        .app-clinic-chevron,
        .app-shell.is-collapsed .app-clinic-chevron{display:grid}
        .app-nav-link .label,
        .app-shell.is-collapsed .app-nav-link .label{display:inline-flex}
        .app-nav-caption,
        .app-shell.is-collapsed .app-nav-caption{display:block}
        .app-brand-row,
        .app-brand,
        .app-nav-link,
        .app-user-card,
        .app-shell.is-collapsed .app-brand-row,
        .app-shell.is-collapsed .app-brand,
        .app-shell.is-collapsed .app-nav-link,
        .app-shell.is-collapsed .app-user-card{justify-content:flex-start}
        .app-nav-link,
        .app-shell.is-collapsed .app-nav-link{padding:9px 10px}
        .app-user-card,
        .app-shell.is-collapsed .app-user-card,
        .app-clinic-card,
        .app-shell.is-collapsed .app-clinic-card{padding:8px 10px;border-radius:18px;width:100%;height:auto;margin-inline:0}
        .app-avatar,
        .app-shell.is-collapsed .app-avatar,
        .app-clinic-option-icon{width:34px;height:34px}
        .app-brand-logo,
        .app-shell.is-collapsed .app-brand-logo{width:132px;height:52px;border-radius:12px}
        .app-user-menu,
        .app-shell.is-collapsed .app-user-menu{left:0;bottom:calc(100% + 12px)}
        .app-clinic-menu,
        .app-shell.is-collapsed .app-clinic-menu{left:4px;right:4px;top:calc(100% + 8px);width:auto}
        .app-topbar-right{flex-wrap:wrap}
        .app-topbar .app-clinic-panel{min-width:0;flex:1 1 100%}
        .app-topbar .app-clinic-card{min-width:220px;max-width:none;width:100%}
        .app-toggle-row{display:none}
    }
    @media (max-width:700px){
        .app-topbar-right{gap:8px}
        .app-top-action{width:36px;height:36px}
        .app-topbar{padding:12px}
        .app-topbar-left{width:100%;min-width:0}
        .app-topbar .app-clinic-card{min-width:0}
        .app-heading h1{font-size:1.1rem}
        .app-page{padding:12px}
    }
</style>
<div class="app-shell" id="appShell">
    <div class="app-sidebar-backdrop" id="appSidebarBackdrop"></div>
    <aside class="app-sidebar">
        <div class="app-brand-row">
            <div class="app-brand">
                <span class="app-brand-logo"><img id="appBrandLogoImg" src="<?php echo $esc($systemLogoUrl); ?>" data-expanded-logo="<?php echo $esc($expandedLogoUrl); ?>" data-collapsed-logo="<?php echo $esc($collapsedLogoUrl); ?>" alt="Medis Logo"></span>
                <div class="app-brand-text"><strong><?php echo $esc($clinicName); ?></strong><span data-i18n="clinic_management">Clinic Management</span></div>
            </div>
        </div>
        <div class="app-clinic-panel" id="appClinicPanelSidebar" style="display:none;">
            <button class="app-clinic-card" id="appClinicToggleSidebar" type="button" aria-expanded="false">
                <span class="app-avatar"><?php echo $esc($clinicSwitcherInitials); ?></span>
                <div class="app-clinic-meta">
                    <strong><?php echo $esc($clinicSwitcherLabel); ?></strong>
                    <span data-i18n="<?php echo $inAdminMode ? 'clinic_switch_admin' : 'clinic_switch_clinic'; ?>"><?php echo $esc($clinicSwitcherMeta); ?></span>
                </div>
                <span class="app-clinic-chevron">▾</span>
            </button>
            <div class="app-clinic-menu" id="appClinicMenuSidebar">
                <?php if (in_array($panelOriginalRole, ['admin', 'doctor'], true)): ?>
                    <div class="app-clinic-menu-section">
                        <div class="app-clinic-menu-title" data-i18n="clinic_navigation_mode">Navigation Mode</div>
                        <?php if ($panelOriginalRole === 'admin'): ?>
                            <form class="app-clinic-option-form" method="POST" action="<?php echo $esc($clinicSwitcherAdminUrl); ?>">
                                <input type="hidden" name="_token" value="<?php echo $esc($clinicSwitcherToken); ?>">
                                <button class="app-clinic-option<?php echo $inAdminMode ? ' is-active' : ''; ?>" type="submit">
                                    <span class="app-clinic-option-icon">AD</span>
                                    <span class="app-clinic-option-text">
                                        <strong>Admin Page</strong>
                                        <span data-i18n="clinic_admin_mode">Manage setup and clinic records</span>
                                    </span>
                                </button>
                            </form>
                        <?php else: ?>
                            <a class="app-clinic-link<?php echo $inAdminMode ? ' is-active' : ''; ?>" href="<?php echo $esc($clinicAdminPageUrl); ?>">
                                <span class="app-clinic-option-icon">AD</span>
                                <span class="app-clinic-option-text">
                                    <strong>Admin Page</strong>
                                    <span data-i18n="clinic_admin_mode">Manage setup and clinic records</span>
                                </span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="app-clinic-menu-section">
                    <div class="app-clinic-menu-title" data-i18n="clinic_switch_title">Switch Clinic</div>
                    <?php if (count($clinicRecords) === 0): ?>
                        <a class="app-clinic-link" href="<?php echo $esc($clinicSwitcherManageUrl); ?>">
                            <span class="app-clinic-option-icon">+</span>
                            <span class="app-clinic-option-text">
                                <strong data-i18n="clinic_no_records">No clinics yet</strong>
                                <span data-i18n="clinic_add_first">Create your first clinic setup</span>
                            </span>
                        </a>
                    <?php else: ?>
                        <?php foreach ($clinicRecords as $clinicRecord): ?>
                            <form class="app-clinic-option-form" method="POST" action="<?php echo $esc(route('panel.clinic.switch', ['clinic' => $clinicRecord['id']])); ?>">
                                <input type="hidden" name="_token" value="<?php echo $esc($clinicSwitcherToken); ?>">
                                <button class="app-clinic-option<?php echo (! $inAdminMode && $activeClinicId === $clinicRecord['id']) ? ' is-active' : ''; ?>" type="submit">
                                    <span class="app-clinic-option-icon"><?php echo $esc(medis_initials($clinicRecord['name'])); ?></span>
                                    <span class="app-clinic-option-text">
                                        <strong><?php echo $esc($clinicRecord['name']); ?></strong>
                                        <span><?php echo $esc($clinicRecord['email'] !== '' ? $clinicRecord['email'] : 'Open clinic navigation'); ?></span>
                                    </span>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($panelOriginalRole === 'admin'): ?>
                    <div class="app-clinic-menu-section">
                        <a class="app-clinic-link" href="<?php echo $esc($clinicSwitcherManageUrl); ?>">
                            <span class="app-clinic-option-icon">+</span>
                            <span class="app-clinic-option-text">
                                <strong data-i18n="clinic_manage_title">Manage Clinics</strong>
                                <span data-i18n="clinic_manage_subtitle">Add or update clinic setup</span>
                            </span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="app-toggle-row">
            <button class="app-toggle" id="appNavToggle" type="button" aria-label="Toggle navigation"><?php echo medis_nav_icon('sidebar_toggle'); ?></button>
        </div>

        <?php if (is_array($navGroups) && count($navGroups) > 0): ?>
            <?php foreach ($navGroups as $group): ?>
                <div class="app-nav-group">
                    <?php if (! empty($group['label'])): ?>
                        <div class="app-nav-caption"><?php echo $esc($group['label']); ?></div>
                    <?php endif; ?>

                    <?php foreach ($group['items'] ?? [] as $item): ?>
                        <a class="app-nav-link<?php echo $active === $item['key'] ? ' active' : ''; ?>" href="<?php echo $esc($item['href']); ?>">
                            <span class="icon"><?php echo medis_nav_icon($item['icon']); ?></span>
                            <span class="label"><?php echo $esc($item['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="app-nav-group">
                <div class="app-nav-caption" data-i18n="menu">Menu</div>
                <?php foreach ($navItems as $item): ?>
                    <a class="app-nav-link<?php echo $active === $item['key'] ? ' active' : ''; ?>" href="<?php echo $esc($item['href']); ?>">
                        <span class="icon"><?php echo medis_nav_icon($item['icon']); ?></span>
                        <span class="label" data-i18n="nav_<?php echo $esc(strtolower($item['key'])); ?>"><?php echo $esc($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="app-nav-group">
                <div class="app-nav-caption" data-i18n="general">General</div>
                <?php foreach ($generalItems as $item): ?>
                    <a class="app-nav-link<?php echo $active === $item['key'] ? ' active' : ''; ?>" href="<?php echo $esc($item['href']); ?>">
                        <span class="icon"><?php echo medis_nav_icon($item['icon']); ?></span>
                        <span class="label" data-i18n="nav_<?php echo $esc(strtolower($item['key'])); ?>"><?php echo $esc($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($showThemeControls || $showLanguageControls): ?>
        <div class="app-sidebar-tools">
            <?php if ($showThemeControls): ?>
            <div class="switch-card">
                <h4 data-i18n="theme">Theme</h4>
                <div class="switch-row">
                    <button class="switch-btn" id="themeDarkBtn" type="button">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"></path></svg>
                        <span data-i18n="dark">Dark</span>
                    </button>
                    <button class="switch-btn" id="themeLightBtn" type="button">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="M4.93 4.93l1.41 1.41"></path><path d="M17.66 17.66l1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="M4.93 19.07l1.41-1.41"></path><path d="M17.66 6.34l1.41-1.41"></path></svg>
                        <span data-i18n="light">Light</span>
                    </button>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($showLanguageControls): ?>
            <div class="switch-card">
                <h4 data-i18n="language">Language</h4>
                <div class="switch-row">
                    <button class="switch-btn" id="langBmBtn" type="button"><span>BM</span></button>
                    <button class="switch-btn" id="langEnBtn" type="button"><span>EN</span></button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="app-user-panel" id="appUserPanel">
            <button class="app-user-card" id="appUserToggle" type="button" aria-expanded="false">
                <span class="app-avatar"><?php echo $esc(strtoupper(substr($displayName, 0, 1))); ?></span>
                <div class="app-user-meta">
                    <strong><?php echo $esc($displayName); ?></strong>
                    <span><?php echo $esc($userEmail); ?></span>
                </div>
            </button>
            <div class="app-user-menu" id="appUserMenu">
                <div class="app-user-menu-header">
                    <span class="app-avatar"><?php echo $esc(strtoupper(substr($displayName, 0, 2))); ?></span>
                    <div class="app-user-meta">
                        <strong><?php echo $esc($displayName); ?></strong>
                        <span><?php echo $esc($userEmail); ?></span>
                    </div>
                </div>
                <div class="app-user-menu-list">
                    <a class="app-user-menu-link" href="<?php echo $esc($profileUrl); ?>"><span class="icon"><?php echo medis_nav_icon('profile'); ?></span><span data-i18n="view_profile">View Profile</span></a>
                    <a class="app-user-menu-link" href="<?php echo $esc($accountSettingsUrl); ?>"><span class="icon"><?php echo medis_nav_icon('settings'); ?></span><span data-i18n="account_settings">Account Settings</span></a>
                </div>
                <div class="app-user-menu-footer">
                    <a class="app-user-menu-link" href="<?php echo $esc($logoutUrl); ?>"><span class="icon"><?php echo medis_nav_icon('logout'); ?></span><span data-i18n="sign_out">Sign Out</span></a>
                </div>
            </div>
        </div>
    </aside>
    <div class="app-main">
        <header class="app-topbar">
            <div class="app-topbar-left">
                <button class="app-mobile-menu-btn" id="appMobileMenuBtn" type="button" aria-label="Open navigation"><?php echo medis_nav_icon('menu'); ?></button>
                <div class="app-heading">
                    <h1><span data-i18n="welcome_back">Welcome back,</span> <strong><?php echo $esc($displayName); ?></strong></h1>
                </div>
            </div>
            <div class="app-topbar-right">
                <div class="app-clinic-panel" id="appClinicPanel">
                    <button class="app-clinic-card" id="appClinicToggle" type="button" aria-expanded="false">
                        <span class="app-avatar"><?php echo $esc($clinicSwitcherInitials); ?></span>
                        <div class="app-clinic-meta">
                            <strong><?php echo $esc($clinicSwitcherLabel); ?></strong>
                            <span data-i18n="<?php echo $inAdminMode ? 'clinic_switch_admin' : 'clinic_switch_clinic'; ?>"><?php echo $esc($clinicSwitcherMeta); ?></span>
                        </div>
                        <span class="app-clinic-chevron">▾</span>
                    </button>
                    <div class="app-clinic-menu" id="appClinicMenu">
                        <?php if (in_array($panelOriginalRole, ['admin', 'doctor'], true)): ?>
                            <div class="app-clinic-menu-section">
                                <div class="app-clinic-menu-title" data-i18n="clinic_navigation_mode">Navigation Mode</div>
                                <?php if ($panelOriginalRole === 'admin'): ?>
                                    <form class="app-clinic-option-form" method="POST" action="<?php echo $esc($clinicSwitcherAdminUrl); ?>">
                                        <input type="hidden" name="_token" value="<?php echo $esc($clinicSwitcherToken); ?>">
                                        <button class="app-clinic-option<?php echo $inAdminMode ? ' is-active' : ''; ?>" type="submit">
                                            <span class="app-clinic-option-icon">AD</span>
                                            <span class="app-clinic-option-text">
                                                <strong>Admin Page</strong>
                                                <span data-i18n="clinic_admin_mode">Manage setup and clinic records</span>
                                            </span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a class="app-clinic-link<?php echo $inAdminMode ? ' is-active' : ''; ?>" href="<?php echo $esc($clinicAdminPageUrl); ?>">
                                        <span class="app-clinic-option-icon">AD</span>
                                        <span class="app-clinic-option-text">
                                            <strong>Admin Page</strong>
                                            <span data-i18n="clinic_admin_mode">Manage setup and clinic records</span>
                                        </span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="app-clinic-menu-section">
                            <div class="app-clinic-menu-title" data-i18n="clinic_switch_title">Switch Clinic</div>
                            <?php if (count($clinicRecords) === 0): ?>
                                <a class="app-clinic-link" href="<?php echo $esc($clinicSwitcherManageUrl); ?>">
                                    <span class="app-clinic-option-icon">+</span>
                                    <span class="app-clinic-option-text">
                                        <strong data-i18n="clinic_no_records">No clinics yet</strong>
                                        <span data-i18n="clinic_add_first">Create your first clinic setup</span>
                                    </span>
                                </a>
                            <?php else: ?>
                                <?php foreach ($clinicRecords as $clinicRecord): ?>
                                    <form class="app-clinic-option-form" method="POST" action="<?php echo $esc(route('panel.clinic.switch', ['clinic' => $clinicRecord['id']])); ?>">
                                        <input type="hidden" name="_token" value="<?php echo $esc($clinicSwitcherToken); ?>">
                                        <button class="app-clinic-option<?php echo (! $inAdminMode && $activeClinicId === $clinicRecord['id']) ? ' is-active' : ''; ?>" type="submit">
                                            <span class="app-clinic-option-icon"><?php echo $esc(medis_initials($clinicRecord['name'])); ?></span>
                                            <span class="app-clinic-option-text">
                                                <strong><?php echo $esc($clinicRecord['name']); ?></strong>
                                                <span><?php echo $esc($clinicRecord['email'] !== '' ? $clinicRecord['email'] : 'Open clinic navigation'); ?></span>
                                            </span>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($panelOriginalRole === 'admin'): ?>
                            <div class="app-clinic-menu-section">
                                <a class="app-clinic-link" href="<?php echo $esc($clinicSwitcherManageUrl); ?>">
                                    <span class="app-clinic-option-icon">+</span>
                                    <span class="app-clinic-option-text">
                                        <strong data-i18n="clinic_manage_title">Manage Clinics</strong>
                                        <span data-i18n="clinic_manage_subtitle">Add or update clinic setup</span>
                                    </span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <a class="app-top-action" href="#" aria-label="Messages"><?php echo medis_nav_icon('mail'); ?></a>
                <a class="app-top-action dot" href="#" aria-label="Notifications"><?php echo medis_nav_icon('bell'); ?></a>
                <span class="app-avatar"><?php echo $esc(strtoupper(substr($displayName, 0, 1))); ?></span>
            </div>
        </header>
        <div class="app-page"><div class="app-card">
<?php
    }
}

if (! function_exists('medis_render_navigation_end')) {
    function medis_render_navigation_end(): void
    {
?>
        </div></div>
    </div>
</div>
<script>
(
function () {
    var shell = document.getElementById('appShell');
    var toggle = document.getElementById('appNavToggle');
    var mobileMenuBtn = document.getElementById('appMobileMenuBtn');
    var sidebarBackdrop = document.getElementById('appSidebarBackdrop');
    var clinicPanel = document.getElementById('appClinicPanel');
    var clinicToggle = document.getElementById('appClinicToggle');
    var userPanel = document.getElementById('appUserPanel');
    var userToggle = document.getElementById('appUserToggle');
    var themeDarkBtn = document.getElementById('themeDarkBtn');
    var themeLightBtn = document.getElementById('themeLightBtn');
    var langBmBtn = document.getElementById('langBmBtn');
    var langEnBtn = document.getElementById('langEnBtn');
    var brandLogoImg = document.getElementById('appBrandLogoImg');
    var storageKey = 'medis-sidebar-collapsed';
    var themeKey = 'medis-theme';
    var langKey = 'medis-language';
    var translations = {
        en: {
            clinic_management: 'Clinic Management',
            menu: 'Menu',
            general: 'General',
            nav_dashboard: 'Dashboard',
            nav_surveillance: 'Surveillance',
            nav_audiometry: 'Audiometry',
            nav_calendar: 'Calendar',
            nav_examination: 'Examination',
            nav_report: 'Report',
            nav_settings: 'Settings',
            nav_help: 'Help',
            nav_logout: 'Logout',
            clinic_navigation_mode: 'Navigation Mode',
            clinic_switch_title: 'Switch Clinic',
            clinic_switch_admin: 'Choose a clinic to enter daily navigation',
            clinic_switch_clinic: 'Current clinic navigation',
            clinic_admin_mode: 'Manage setup and clinic records',
            clinic_manage_title: 'Manage Clinics',
            clinic_manage_subtitle: 'Add or update clinic setup',
            clinic_no_records: 'No clinics yet',
            clinic_add_first: 'Create your first clinic setup',
            theme: 'Theme',
            dark: 'Dark',
            light: 'Light',
            language: 'Language',
            view_profile: 'View Profile',
            account_settings: 'Account Settings',
            documentation: 'Documentation',
            keyboard_shortcuts: 'Keyboard Shortcuts',
            sign_out: 'Sign Out',
            welcome_back: 'Welcome back,'
        },
        bm: {
            clinic_management: 'Pengurusan Klinik',
            menu: 'Menu',
            general: 'Umum',
            nav_dashboard: 'Papan Pemuka',
            nav_surveillance: 'Saringan',
            nav_audiometry: 'Audiometri',
            nav_calendar: 'Kalendar',
            nav_examination: 'Pemeriksaan',
            nav_report: 'Laporan',
            nav_settings: 'Tetapan',
            nav_help: 'Bantuan',
            nav_logout: 'Log Keluar',
            clinic_navigation_mode: 'Mod Navigasi',
            clinic_switch_title: 'Tukar Klinik',
            clinic_switch_admin: 'Pilih klinik untuk masuk ke navigasi harian',
            clinic_switch_clinic: 'Navigasi klinik semasa',
            clinic_admin_mode: 'Urus tetapan dan rekod klinik',
            clinic_manage_title: 'Urus Klinik',
            clinic_manage_subtitle: 'Tambah atau kemas kini tetapan klinik',
            clinic_no_records: 'Belum ada klinik',
            clinic_add_first: 'Cipta tetapan klinik pertama',
            theme: 'Tema',
            dark: 'Gelap',
            light: 'Cerah',
            language: 'Bahasa',
            view_profile: 'Lihat Profil',
            account_settings: 'Tetapan Akaun',
            documentation: 'Dokumentasi',
            keyboard_shortcuts: 'Pintasan Papan Kekunci',
            sign_out: 'Log Keluar',
            welcome_back: 'Selamat kembali,'
        }
    };

    function applyTheme(theme) {
        document.body.setAttribute('data-theme', theme);
        if (themeDarkBtn && themeLightBtn) {
            themeDarkBtn.classList.toggle('is-active', theme === 'dark');
            themeLightBtn.classList.toggle('is-active', theme !== 'dark');
        }
    }

    function applyLanguage(lang) {
        document.documentElement.lang = lang === 'bm' ? 'ms' : 'en';
        document.querySelectorAll('[data-i18n]').forEach(function (node) {
            var key = node.getAttribute('data-i18n');
            if (translations[lang] && translations[lang][key]) {
                node.textContent = translations[lang][key];
            }
        });
        if (langBmBtn && langEnBtn) {
            langBmBtn.classList.toggle('is-active', lang === 'bm');
            langEnBtn.classList.toggle('is-active', lang !== 'bm');
        }
    }

    function isMobileLayout() {
        return window.matchMedia && window.matchMedia('(max-width: 1100px)').matches;
    }

    function getStoredCollapsed() {
        try {
            return !!(window.localStorage && window.localStorage.getItem(storageKey) === '1');
        } catch (error) {
            return false;
        }
    }

    function closeMobileNav() {
        if (!shell) {
            return;
        }

        shell.classList.remove('is-mobile-nav-open');
        syncBrandLogo();
    }

    function syncBrandLogo() {
        if (!brandLogoImg || !shell) {
            return;
        }

        var expandedLogo = brandLogoImg.getAttribute('data-expanded-logo') || brandLogoImg.getAttribute('src');
        var collapsedLogo = brandLogoImg.getAttribute('data-collapsed-logo') || expandedLogo;
        var useCollapsedLogo = !isMobileLayout() && shell.classList.contains('is-collapsed');
        brandLogoImg.setAttribute('src', useCollapsedLogo ? collapsedLogo : expandedLogo);
    }

    function syncSidebarMode() {
        if (!shell) {
            return;
        }

        if (isMobileLayout()) {
            shell.classList.remove('is-collapsed');
            closeMobileNav();
            return;
        }

        shell.classList.toggle('is-collapsed', getStoredCollapsed());
        syncBrandLogo();
    }

    function openMobileNav() {
        if (!shell) {
            return;
        }

        shell.classList.add('is-mobile-nav-open');
        if (clinicPanel && clinicToggle) {
            clinicPanel.classList.remove('is-open');
            clinicToggle.setAttribute('aria-expanded', 'false');
        }
        if (userPanel && userToggle) {
            userPanel.classList.remove('is-open');
            userToggle.setAttribute('aria-expanded', 'false');
        }
    }

    syncSidebarMode();
    syncBrandLogo();

    var savedTheme = 'light';
    var savedLang = 'en';
    try {
        if (window.localStorage) {
            savedTheme = window.localStorage.getItem(themeKey) || 'light';
            savedLang = window.localStorage.getItem(langKey) || 'en';
        }
    } catch (error) {}
    applyTheme(savedTheme);
    applyLanguage(savedLang);

    if (toggle && shell) {
        toggle.addEventListener('click', function () {
            if (isMobileLayout()) {
                closeMobileNav();
                return;
            }

            var collapsed = shell.classList.toggle('is-collapsed');
            try {
                if (window.localStorage) {
                    window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
                }
            } catch (error) {}
            syncSidebarMode();
            if (clinicPanel && clinicToggle) {
                clinicPanel.classList.remove('is-open');
                clinicToggle.setAttribute('aria-expanded', 'false');
            }
            if (userPanel && userToggle) {
                userPanel.classList.remove('is-open');
                userToggle.setAttribute('aria-expanded', 'false');
            }
            syncBrandLogo();
        });
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function () {
            if (shell && shell.classList.contains('is-mobile-nav-open')) {
                closeMobileNav();
                return;
            }

            openMobileNav();
            syncBrandLogo();
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', closeMobileNav);
    }

    if (themeDarkBtn && themeLightBtn) {
        themeDarkBtn.addEventListener('click', function () {
            applyTheme('dark');
            try { window.localStorage.setItem(themeKey, 'dark'); } catch (error) {}
        });
        themeLightBtn.addEventListener('click', function () {
            applyTheme('light');
            try { window.localStorage.setItem(themeKey, 'light'); } catch (error) {}
        });
    }

    if (langBmBtn && langEnBtn) {
        langBmBtn.addEventListener('click', function () {
            applyLanguage('bm');
            try { window.localStorage.setItem(langKey, 'bm'); } catch (error) {}
        });
        langEnBtn.addEventListener('click', function () {
            applyLanguage('en');
            try { window.localStorage.setItem(langKey, 'en'); } catch (error) {}
        });
    }

    if (userPanel && userToggle) {
        userToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            if (clinicPanel && clinicToggle) {
                clinicPanel.classList.remove('is-open');
                clinicToggle.setAttribute('aria-expanded', 'false');
            }
            var isOpen = userPanel.classList.toggle('is-open');
            userToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    if (clinicPanel && clinicToggle) {
        clinicToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            if (userPanel && userToggle) {
                userPanel.classList.remove('is-open');
                userToggle.setAttribute('aria-expanded', 'false');
            }
            var isOpen = clinicPanel.classList.toggle('is-open');
            clinicToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    document.addEventListener('click', function (event) {
        if (userPanel && !userPanel.contains(event.target)) {
            userPanel.classList.remove('is-open');
            userToggle.setAttribute('aria-expanded', 'false');
        }
        if (clinicPanel && !clinicPanel.contains(event.target)) {
            clinicPanel.classList.remove('is-open');
            clinicToggle.setAttribute('aria-expanded', 'false');
        }
    });

    document.querySelectorAll('.app-sidebar a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (isMobileLayout()) {
                closeMobileNav();
            }
        });
    });

    window.addEventListener('resize', function () {
        syncSidebarMode();
        syncBrandLogo();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMobileNav();
            if (clinicPanel && clinicToggle) {
                clinicPanel.classList.remove('is-open');
                clinicToggle.setAttribute('aria-expanded', 'false');
            }
            if (userPanel && userToggle) {
                userPanel.classList.remove('is-open');
                userToggle.setAttribute('aria-expanded', 'false');
            }
        }
    });

    document.querySelectorAll('input[type="text"]').forEach(function (input) {
        var placeholder = (input.getAttribute('placeholder') || '').toLowerCase();
        if (placeholder.indexOf('search') === -1) {
            return;
        }

        var scope = input.closest('.content') || input.closest('.table-wrap') || input.closest('.panel') || input.parentElement;
        if (!scope) {
            return;
        }

        var table = scope.querySelector('.table');
        if (!table || !table.tBodies.length) {
            return;
        }

        var tbody = table.tBodies[0];
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
        if (!rows.length) {
            return;
        }

        var pager = scope.querySelector('.pager') || scope.querySelector('.table-foot .pager');
        var emptyRow = document.createElement('tr');
        emptyRow.className = 'table-search-empty';
        emptyRow.style.display = 'none';
        var emptyCell = document.createElement('td');
        emptyCell.colSpan = table.querySelectorAll('thead th').length || rows[0].children.length || 1;
        emptyCell.style.padding = '18px 10px';
        emptyCell.style.color = '#6b7280';
        emptyCell.textContent = 'No matching records found.';
        emptyRow.appendChild(emptyCell);
        tbody.appendChild(emptyRow);

        var totalRows = rows.length;

        var renderPager = function (visibleCount) {
            if (!pager) {
                return;
            }
            if (visibleCount === 0) {
                pager.textContent = 'Showing 0 of ' + totalRows.toLocaleString() + ' records';
            } else {
                pager.textContent = 'Showing 1-' + visibleCount.toLocaleString() + ' of ' + totalRows.toLocaleString() + ' records';
            }
        };

        renderPager(totalRows);

        input.addEventListener('input', function () {
            var query = input.value.trim().toLowerCase();
            var visibleCount = 0;

            rows.forEach(function (row) {
                var text = (row.textContent || '').toLowerCase();
                var match = query === '' || text.indexOf(query) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) {
                    visibleCount += 1;
                }
            });

            emptyRow.style.display = visibleCount === 0 ? '' : 'none';
            renderPager(visibleCount);
        });
    });
})();
</script>
<?php
    }
}
