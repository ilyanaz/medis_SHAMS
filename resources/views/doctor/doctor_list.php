<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor List</title>
</head>
<body class="admin-shell">
<?php
require dirname(__DIR__) . '/admin/admin_navigation.php';

$esc = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$csrfToken = function_exists('csrf_token') ? (string) csrf_token() : '';
$statusMessage = function_exists('session') ? (string) session('status', '') : '';
$doctors = $doctors ?? collect();
$totalDoctors = is_countable($doctors) ? count($doctors) : 0;
$doctorSetupRoute = route(\Illuminate\Support\Facades\Route::has('admin.doctor_setup') ? 'admin.doctor_setup' : 'panel.doctor_setup');
$dashboardRoute = route(\Illuminate\Support\Facades\Route::has('admin.dashboard') ? 'admin.dashboard' : 'panel.admin_dashboard');

medis_render_admin_navigation_start([
    'clinicName' => 'Admin',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'Admin',
    'active' => 'doctor',
]);
?>
<style>
    .content{border:1px solid #e5e7eb;border-radius:20px;background:#fff;padding:18px;min-height:clamp(500px,calc(100dvh - 314px),780px);display:flex;flex-direction:column}
    .head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
    .head h2{margin:0 0 12px;font-size:1.8rem}
    .head p{margin:6px 0 0;color:#6b7280}
    .top-actions{display:flex;gap:10px;flex-wrap:wrap}
    .btn,.next,.danger{display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;background:#fff;color:#374151;font:inherit;cursor:pointer}
    .next{background:#389B5B;border-color:#389B5B;color:#fff}
    .danger{border-color:#fecaca;color:#b91c1c;background:#fff5f5}
    .toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px}
    .toolbar input{border:1px solid #d1d5db;border-radius:12px;padding:10px 12px;min-width:280px}
    .table{width:100%;border-collapse:collapse;margin-top:14px}
    .table th,.table td{padding:14px 10px;text-align:left;border-top:1px solid #edf0f2;vertical-align:top}
    .table th{font-size:.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em}
    .table-name{color:#0f172a;font-weight:600}
    .empty{padding:22px 10px;color:#6b7280;text-align:center}
    .action-icons{display:flex;gap:14px;flex-wrap:wrap;align-items:center}.action-icons form{margin:0}
    .icon-btn{display:inline-flex;align-items:center;justify-content:center;background:transparent;color:#475569;cursor:pointer;text-decoration:none;border:none;padding:0}
    .icon-btn svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}
    .icon-btn:hover{color:#0f172a}.icon-btn.danger{color:#dc2626}.icon-btn.danger:hover{color:#991b1b}
    .status-form{position:relative;display:inline-flex;align-items:center}.status-form::after{content:"";position:absolute;right:14px;top:50%;width:8px;height:8px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:translateY(-65%) rotate(45deg);pointer-events:none;color:#64748b}.status-form::before{content:"";position:absolute;left:14px;top:50%;width:10px;height:10px;border-radius:999px;transform:translateY(-50%);background:#16a34a;box-shadow:0 0 0 4px rgba(22,163,74,.12);pointer-events:none}.status-form.is-inactive::before{background:#e11d48;box-shadow:0 0 0 4px rgba(225,29,72,.12)}.status-form select{appearance:none;-webkit-appearance:none;-moz-appearance:none;border:1px solid #dbe4ea;border-radius:999px;padding:9px 38px 9px 34px;background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);color:#0f172a;font-size:.88rem;font-weight:600;min-width:132px;cursor:pointer;box-shadow:0 8px 20px rgba(15,23,42,.05)}.status-form.is-active select{color:#166534;background:linear-gradient(180deg,#f4fdf7 0%,#ecfdf3 100%);border-color:#bbf7d0}.status-form.is-inactive select{color:#be123c;background:linear-gradient(180deg,#fff8fa 0%,#fff1f2 100%);border-color:#fecdd3}
    .notice{margin-top:18px;padding:12px 14px;border-radius:14px;border:1px solid #a7f3d0;background:#ecfdf3;color:#065f46}
    .bottom{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px}
    .pager{color:#6b7280;font-size:.84rem}
    .stack{display:grid;gap:4px}
    .muted{color:#6b7280;font-size:.92rem;line-height:1.45}
    @media(max-width:760px){.content{padding:16px}.toolbar input{min-width:100%}}
</style>

<section class="content">
    <div class="head">
        <div>
            <h2>Doctor List</h2>
            <p>Review doctors added to the system and manage their setup records.</p>
        </div>
        <div class="top-actions">
            <a class="btn" href="#">Import</a>
            <a class="next" href="<?php echo $esc($doctorSetupRoute); ?>">+ Add Doctor</a>
        </div>
    </div>

    <?php if ($statusMessage !== ''): ?>
        <div class="notice"><?php echo $esc($statusMessage); ?></div>
    <?php endif; ?>

    <div class="toolbar">
        <input type="text" placeholder="Search doctor">
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Doctor ID</th>
                <th>Name</th>
                <th>OHD Registration Number</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($totalDoctors > 0): ?>
                <?php foreach ($doctors as $doctor): ?>
                    <?php
                    $fullName = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
                    $contactText = (string) (($doctor->doctor_telephone ?? '') !== '' ? $doctor->doctor_telephone : (($doctor->doctor_email ?? '') !== '' ? $doctor->doctor_email : '-'));
                    $statusValue = strtolower(trim((string) ($doctor->doctor_status ?? 'active')));
                    $isActive = $statusValue === 'active';
                    ?>
                    <tr>
                        <td>#DOC<?php echo $esc($doctor->doctor_id); ?></td>
                        <td>
                            <div class="stack">
                                <span class="table-name"><?php echo $esc($fullName !== '' ? $fullName : 'Doctor'); ?></span>
                                <span class="muted"><?php echo $esc((string) (($doctor->doctor_email ?? '') !== '' ? $doctor->doctor_email : 'No email provided')); ?></span>
                            </div>
                        </td>
                        <td><?php echo $esc((string) (($doctor->OHD_registrationNo ?? '') !== '' ? $doctor->OHD_registrationNo : '-')); ?></td>
                        <td>
                            <div class="stack">
                                <span><?php echo $esc($contactText); ?></span>
                                <span class="muted"><?php echo $esc((string) (($doctor->doctor_fax ?? '') !== '' ? 'Fax: ' . $doctor->doctor_fax : (($doctor->doctor_email ?? '') !== '' ? $doctor->doctor_email : ''))); ?></span>
                            </div>
                        </td>
                        <td>
                            <form class="status-form <?php echo $isActive ? 'is-active' : 'is-inactive'; ?>" method="POST" action="<?php echo $esc(route(\Illuminate\Support\Facades\Route::has('admin.doctor.status') ? 'admin.doctor.status' : 'panel.doctor.status', ['doctor' => $doctor->doctor_id])); ?>">
                                <input type="hidden" name="_token" value="<?php echo $esc($csrfToken); ?>">
                                <input type="hidden" name="_method" value="PATCH">
                                <select name="doctor_status" onchange="this.form.submit()">
                                    <option value="active" <?php echo $statusValue === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="not active" <?php echo $statusValue === 'not active' ? 'selected' : ''; ?>>Not Active</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <div class="action-icons">
                                <a class="icon-btn" href="<?php echo $esc(route(\Illuminate\Support\Facades\Route::has('admin.doctor.show') ? 'admin.doctor.show' : 'panel.doctor.show', ['doctor' => $doctor->doctor_id])); ?>" title="View doctor" aria-label="View doctor">
                                    <svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </a>
                                <a class="icon-btn" href="<?php echo $esc(route(\Illuminate\Support\Facades\Route::has('admin.doctor.edit') ? 'admin.doctor.edit' : 'panel.doctor.edit', ['doctor' => $doctor->doctor_id])); ?>" title="Edit doctor" aria-label="Edit doctor">
                                    <svg viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                                </a>
                                <form method="POST" action="<?php echo $esc(route(\Illuminate\Support\Facades\Route::has('admin.doctor.destroy') ? 'admin.doctor.destroy' : 'panel.doctor.destroy', ['doctor' => $doctor->doctor_id])); ?>" onsubmit="return confirm('Delete this doctor?');">
                                    <input type="hidden" name="_token" value="<?php echo $esc($csrfToken); ?>">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button class="icon-btn danger" type="submit" title="Delete doctor" aria-label="Delete doctor">
                                        <svg viewBox="0 0 24 24"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td class="empty" colspan="6">No doctor records found in the current database.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="bottom">
        <span class="pager"><?php echo $totalDoctors > 0 ? 'Showing 1-' . number_format($totalDoctors) . ' of ' . number_format($totalDoctors) . ' records' : 'Showing 0 of 0 records'; ?></span>
        <div>
            <a class="btn" href="<?php echo $esc($dashboardRoute); ?>">Back</a>
            <a class="next" href="<?php echo $esc($doctorSetupRoute); ?>">Next</a>
        </div>
    </div>
</section>

<?php medis_render_navigation_end(); ?>
</body>
</html>
