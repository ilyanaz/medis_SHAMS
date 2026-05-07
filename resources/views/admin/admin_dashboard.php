<?php

declare(strict_types=1);

require dirname(__DIR__) . '/admin/admin_navigation.php';

$pageTitle = 'Admin Dashboard';
$adminEmail = $adminEmail ?? '';
$adminRole = 'Admin';
$displayName = 'Admin';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        .admin-content{width:100%;max-width:1100px}
        .welcome{margin:0;font-size:clamp(2rem,2.6vw,3rem);line-height:1.12;font-weight:700;color:#0f172a}
        .meta{margin-top:18px;color:#64748b;font-size:1rem}
    </style>
</head>
<body class="admin-shell">
<?php medis_render_admin_navigation_start([
    'clinicName' => 'Admin',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? $displayName,
    'active' => 'admin_dashboard',
]); ?>

<div class="admin-content">
    <h1 class="welcome">Welcome Admin to Admin's page.</h1>
    <p class="meta"><?php echo htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8'); ?><?php echo $adminEmail !== '' ? ' | ' . htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8') : ''; ?></p>
</div>

<?php medis_render_navigation_end(); ?>
</body>
</html>
