<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/panel/navigation.php';

if (! function_exists('medis_render_admin_navigation_start')) {
    function medis_render_admin_navigation_start(array $config = []): void
    {
        $loginLogoUrl = function_exists('asset')
            ? asset('images/logos/medis-logo-left-right.png')
            : '/images/logos/medis-logo-left-right.png';

        $config['bodyClass'] = trim((string) ($config['bodyClass'] ?? '') . ' admin-shell');
        $config['clinicName'] = $config['clinicName'] ?? 'Admin';
        $config['brandText'] = $config['brandText'] ?? 'Administrator';
        $config['expandedLogoUrl'] = $config['expandedLogoUrl'] ?? $loginLogoUrl;
        $config['collapsedLogoUrl'] = $config['collapsedLogoUrl'] ?? $loginLogoUrl;
        $config['systemLogoUrl'] = $config['systemLogoUrl'] ?? $loginLogoUrl;
        $config['navGroups'] = $config['navGroups'] ?? [
            [
                'items' => [
                    [
                        'key' => 'admin_dashboard',
                        'label' => 'Dashboard',
                        'href' => medis_named_route(['admin.dashboard', 'panel.admin_dashboard', 'panel.dashboard']),
                        'icon' => 'dashboard',
                    ],
                ],
            ],
            [
                'label' => 'Manage',
                'items' => [
                    [
                        'key' => 'doctor',
                        'label' => 'Doctor',
                        'href' => medis_named_route(['admin.doctor_list', 'panel.doctor_list', 'admin.doctor_setup', 'panel.doctor_setup']),
                        'icon' => 'profile',
                    ],
                    [
                        'key' => 'clinic',
                        'label' => 'Clinics',
                        'href' => medis_named_route(['admin.clinic_list', 'panel.clinic_list']),
                        'icon' => 'calendar',
                    ],
                ],
            ],
            [
                'label' => 'Account',
                'items' => [
                    [
                        'key' => 'settings',
                        'label' => 'Settings',
                        'href' => medis_named_route(['admin.settings', 'panel.settings']),
                        'icon' => 'settings',
                    ],
                    [
                        'key' => 'logout',
                        'label' => 'Logout',
                        'href' => medis_named_route(['panel.logout']),
                        'icon' => 'logout',
                    ],
                ],
            ],
        ];

        $config['generalItems'] = $config['generalItems'] ?? [];

        if (! isset($config['active'])) {
            $config['active'] = 'admin_dashboard';
        }

        medis_render_navigation_start($config);
    }
}
