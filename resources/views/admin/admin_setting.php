<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
</head>
<body class="admin-shell">
<?php
require dirname(__DIR__) . '/admin/admin_navigation.php';
$esc = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
medis_render_admin_navigation_start([
    'clinicName' => 'Admin',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'Admin',
    'active' => 'settings',
]);
$statusMessage = session('status');
$errorBag = $errors ?? null;
$accountUser = $accountUser ?? null;
$doctorRecord = $doctorRecord ?? null;
$currentUsername = old('username', (string) ($accountUser->username ?? ''));
$doctorEmail = (string) ($doctorRecord->doctor_email ?? ($accountUser->email ?? ''));
?>
<style>
.settings-page{display:grid;gap:18px}
.settings-head h1{margin:0;font-size:1.9rem}
.settings-head p{margin:6px 0 0;color:#6b7280}
.notice{padding:12px 14px;border-radius:14px;border:1px solid #a7f3d0;background:#ecfdf3;color:#065f46}
.error-box{padding:12px 14px;border-radius:14px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b}
.settings-card{border:1px solid #e5e7eb;border-radius:22px;background:#fff;padding:18px}
.settings-card h2{margin:0 0 12px;font-size:1.45rem;color:#0f172a}
.settings-stack{display:grid;gap:14px}
.field{display:grid;gap:6px}
.field span{font-size:.92rem;color:#475569}
.field input{border:1px solid #d1d5db;border-radius:14px;padding:11px 14px;background:#fff;width:100%;font:inherit}
.password-wrap{position:relative}
.password-wrap input{padding-right:72px}
.toggle-password{position:absolute;right:12px;top:50%;transform:translateY(-50%);border:none;background:transparent;color:#6b7280;font-size:.88rem;font-weight:600;cursor:pointer;padding:4px 6px}
.toggle-password:hover{color:#319755}
.actions{display:flex;gap:10px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;background:#fff;color:#374151;text-decoration:none;cursor:pointer;font:inherit}
.btn.primary{background:#389B5B;border-color:#389B5B;color:#fff}
.tips{margin-top:10px;padding:14px;border-radius:16px;background:#f8fafc;border:1px solid #e5e7eb;color:#475569}
.tips ul{margin:0;padding-left:18px;display:grid;gap:6px}
</style>
<div class="settings-page">
    <div class="settings-head">
        <h1>Settings</h1>
    </div>

    <?php if (! empty($statusMessage)): ?>
        <div class="notice"><?php echo $esc($statusMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorBag && $errorBag->any()): ?>
        <div class="error-box"><?php echo $esc($errorBag->first()); ?></div>
    <?php endif; ?>

    <section class="settings-card">
        <h2>Change Username</h2>
        <form class="settings-stack" method="POST" action="<?php echo $esc(route('admin.username.update')); ?>">
            <input type="hidden" name="_token" value="<?php echo $esc(csrf_token()); ?>">
            <label class="field">
                <span>Username</span>
                <input type="text" name="username" value="<?php echo $esc($currentUsername); ?>" required>
            </label>
            <label class="field">
                <span>Email</span>
                <input type="text" value="<?php echo $esc($doctorEmail); ?>" readonly>
            </label>
            <div class="actions">
                <button class="btn primary" type="submit">Update Username</button>
            </div>
        </form>
    </section>

    <section class="settings-card">
        <h2>Change Password</h2>
        <form class="settings-stack" method="POST" action="<?php echo $esc(route('admin.password.update')); ?>">
            <input type="hidden" name="_token" value="<?php echo $esc(csrf_token()); ?>">
            <label class="field">
                <span>Current Password</span>
                <div class="password-wrap">
                    <input id="current_password" type="password" name="current_password" required>
                    <button type="button" class="toggle-password" data-target="current_password">Show</button>
                </div>
            </label>
            <label class="field">
                <span>New Password</span>
                <div class="password-wrap">
                    <input id="new_password" type="password" name="new_password" minlength="6" required>
                    <button type="button" class="toggle-password" data-target="new_password">Show</button>
                </div>
            </label>
            <label class="field">
                <span>Confirm New Password</span>
                <div class="password-wrap">
                    <input id="new_password_confirmation" type="password" name="new_password_confirmation" minlength="6" required>
                    <button type="button" class="toggle-password" data-target="new_password_confirmation">Show</button>
                </div>
            </label>
            <div class="actions">
                <button class="btn primary" type="submit">Update Password</button>
            </div>
        </form>
        <div class="tips">
            <ul>
                <li>Use at least 6 characters</li>
                <li>After changing password, use the new password on your next login</li>
            </ul>
        </div>
    </section>
</div>
<?php medis_render_navigation_end(); ?>
<script>
document.querySelectorAll('.toggle-password').forEach(function (button) {
    button.addEventListener('click', function () {
        var target = document.getElementById(button.getAttribute('data-target'));
        if (!target) {
            return;
        }
        var isPassword = target.getAttribute('type') === 'password';
        target.setAttribute('type', isPassword ? 'text' : 'password');
        button.textContent = isPassword ? 'Hide' : 'Show';
    });
});
</script>
</body>
</html>
