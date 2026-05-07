<?php

declare(strict_types=1);

$pageTitle = 'Developer Dashboard';
$developerEmail = $developerEmail ?? '';
$developerRole = $developerRole ?? 'Developer';
$csrfToken = csrf_token();
$displayName = 'Mohd Hairul Bin Alhadi';
$initials = 'MA';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        *,
        ::before,
        ::after {
            box-sizing: border-box;
            border-width: 0;
            border-style: solid;
            border-color: #e5e7eb;
            --tw-border-spacing-x: 0;
            --tw-border-spacing-y: 0;
            --tw-translate-x: 0;
            --tw-translate-y: 0;
            --tw-rotate: 0;
            --tw-skew-x: 0;
            --tw-skew-y: 0;
            --tw-scale-x: 1;
            --tw-scale-y: 1;
            --tw-ring-inset: ;
            --tw-ring-offset-width: 0px;
            --tw-ring-offset-color: #fff;
            --tw-ring-color: rgb(59 130 246 / .5);
            --tw-ring-offset-shadow: 0 0 #0000;
            --tw-ring-shadow: 0 0 #0000;
            --tw-shadow: 0 0 #0000;
            --tw-shadow-colored: 0 0 #0000;
        }

        ::before,
        ::after {
            --tw-content: "";
        }

        ::backdrop {
            --tw-border-spacing-x: 0;
            --tw-border-spacing-y: 0;
            --tw-translate-x: 0;
            --tw-translate-y: 0;
            --tw-rotate: 0;
            --tw-skew-x: 0;
            --tw-skew-y: 0;
            --tw-scale-x: 1;
            --tw-scale-y: 1;
            --tw-ring-inset: ;
            --tw-ring-offset-width: 0px;
            --tw-ring-offset-color: #fff;
            --tw-ring-color: rgb(59 130 246 / .5);
            --tw-ring-offset-shadow: 0 0 #0000;
            --tw-ring-shadow: 0 0 #0000;
            --tw-shadow: 0 0 #0000;
            --tw-shadow-colored: 0 0 #0000;
        }

        :root {
            --sidebar-width: 360px;
            --header-height: 78px;
        }

        html {
            line-height: 1.5;
            -webkit-text-size-adjust: 100%;
            font-family: Figtree, ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            height: 100%;
            background-color: rgb(255 255 255 / 1);
        }

        body {
            margin: 0;
            line-height: inherit;
            min-height: 100%;
            font-family: Figtree, ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background: #f9fafb;
            color: #0f172a;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input {
            font: inherit;
        }

        .dashboard {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 270px 1fr;
        }

        .sidebar {
            background: #ffffff;
            border-right-width: 1px;
            border-right-color: #d9e0ea;
            padding: 18px 24px 22px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 4px;
        }

        .brand img {
            width: 128px;
            height: auto;
            display: block;
        }

        .brand-role {
            font-size: 0.95rem;
            font-weight: 500;
            color: #111827;
        }

        .nav-group {
            margin-top: 34px;
        }

        .nav-group:first-of-type {
            margin-top: 42px;
        }

        .nav-title {
            margin: 0 0 14px;
            color: #94a3b8;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .nav-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 7px 0;
            font-size: 0.95rem;
            font-weight: 500;
            color: #1f2f4a;
        }

        .nav-item svg {
            width: 24px;
            height: 24px;
            color: #98a2b3;
            flex: 0 0 auto;
        }

        .nav-item.active {
            color: #15396a;
            font-weight: 600;
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 20px;
        }

        .main {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .header {
            height: 68px;
            background: #ffffff;
            border-bottom-width: 1px;
            border-bottom-color: #d9e0ea;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 18px;
            padding: 0 20px;
        }

        .bell {
            width: 20px;
            height: 20px;
            color: #98a2b3;
        }

        .header-divider {
            width: 1px;
            height: 28px;
            background: #e5e7eb;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: #e5e7eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #3b2c1f;
            flex: 0 0 auto;
        }

        .profile-name {
            font-size: 0.95rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .chevron {
            width: 16px;
            height: 16px;
            color: #98a2b3;
            flex: 0 0 auto;
        }

        .content {
            padding: 34px 34px;
        }

        .welcome {
            margin: 0;
            font-size: clamp(1.9rem, 2.6vw, 2.6rem);
            line-height: 1.12;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #11284d;
            max-width: 1100px;
        }

        .subtext {
            margin-top: 16px;
            color: #64748b;
            font-size: 0.95rem;
        }

        .logout-form {
            margin-top: 18px;
        }

        .logout-button {
            border-width: 1px;
            border-color: #d1d5db;
            background: #ffffff;
            color: #334155;
            border-radius: 10px;
            padding: 9px 14px;
            cursor: pointer;
            font-size: 0.95rem;
        }

        @media (max-width: 1100px) {
            .dashboard {
                grid-template-columns: 240px 1fr;
            }

            .brand img {
                width: 116px;
            }

            .content {
                padding: 28px 24px;
            }
        }

        @media (max-width: 900px) {
            .dashboard {
                grid-template-columns: 1fr;
            }

            .sidebar {
                min-height: auto;
                border-right-width: 0;
                border-bottom-width: 1px;
                padding: 16px 18px 18px;
            }

            .nav-group:first-of-type {
                margin-top: 20px;
            }
        }

        @media (max-width: 640px) {
            .sidebar {
                padding: 14px 16px;
            }

            .brand {
                gap: 8px;
                flex-wrap: wrap;
            }

            .brand img {
                width: 108px;
            }

            .header {
                height: auto;
                padding: 14px 16px;
                justify-content: space-between;
                gap: 10px;
                flex-wrap: wrap;
            }

            .profile {
                gap: 10px;
                width: 100%;
                justify-content: flex-end;
            }

            .content {
                padding: 24px 16px;
            }

            .welcome {
                font-size: 1.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="brand">
                <img src="/images/logos/medis-logo-left-right.png" alt="Medis SHAMS logo">
                <span class="brand-role"><?php echo htmlspecialchars($developerRole, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="nav-group">
                <div class="nav-list">
                    <a class="nav-item active" href="<?php echo route('developer.dashboard'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-4.5v-6h-5v6H5a1 1 0 0 1-1-1z"/></svg>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>

            <div class="nav-group">
                <p class="nav-title">Manage</p>
                <div class="nav-list">
                    <a class="nav-item" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 6V4h10v2"/></svg>
                        <span>Workplaces</span>
                    </a>
                    <a class="nav-item" href="<?php echo route('developer.users'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="3.5"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a3.5 3.5 0 0 1 0 6.74"/></svg>
                        <span>Users</span>
                    </a>
                    <a class="nav-item" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7.5h18"/><path d="M6 7.5V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1.5"/><path d="M5 7.5l1 11a2 2 0 0 0 2 1.5h8a2 2 0 0 0 2-1.5l1-11"/></svg>
                        <span>Inquiries</span>
                    </a>
                </div>
            </div>

            <div class="nav-group">
                <p class="nav-title">Landing Page</p>
                <div class="nav-list">
                    <a class="nav-item" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16l-2 4 2 4H4z"/><path d="M4 6v14"/></svg>
                        <span>Banners</span>
                    </a>
                    <a class="nav-item" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 11h8M8 15h5"/><path d="M7 7h.01M7 11h.01M7 15h.01"/></svg>
                        <span>Blogs</span>
                    </a>
                </div>
            </div>

            <div class="sidebar-footer">
                <a class="nav-item" href="<?php echo route('developer.panel'); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3.5"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01A1.65 1.65 0 0 0 10 3.09V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.01a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01A1.65 1.65 0 0 0 20.91 10H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Settings</span>
                </a>
            </div>
        </aside>

        <main class="main">
            <header class="header">
                <svg class="bell" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"/><path d="M10 17a2 2 0 0 0 4 0"/></svg>
                <div class="header-divider"></div>
                <div class="profile">
                    <span class="avatar"><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="profile-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
                    <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            </header>

            <section class="content">
                <h1 class="welcome">Welcome <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?> to Developer's page.</h1>
                <p class="subtext">Signed in as <?php echo htmlspecialchars($developerEmail, ENT_QUOTES, 'UTF-8'); ?>.</p>

                <form class="logout-form" method="POST" action="<?php echo route('developer.logout'); ?>">
                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="logout-button" type="submit">Log out</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
