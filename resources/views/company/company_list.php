<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company List</title>
</head>
<body>
<?php
require dirname(__DIR__) . '/panel/navigation.php';

$esc = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$csrfToken = function_exists('csrf_token') ? (string) csrf_token() : '';
$statusMessage = function_exists('session') ? (string) session('status', '') : '';
$companies = isset($companies) ? $companies : collect();
$totalCompanies = isset($companyTotal) ? (int) $companyTotal : (is_countable($companies) ? count($companies) : 0);
$setupUrl = function_exists('route') ? route('panel.company_new') : '#';
$returnTo = function_exists('url') ? url()->full() : '#';
$dashboardUrl = function_exists('route') ? route('panel.dashboard') : '#';

medis_render_navigation_start([
    'clinicName' => $clinicName ?? 'Medis SHAMS',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'User',
    'active' => 'company',
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
    .toolbar-left,.toolbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .toolbar input{border:1px solid #d1d5db;border-radius:12px;padding:10px 12px;min-width:280px}
    .filter-btn{display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:1px solid #d1d5db;border-radius:10px;padding:9px 12px;background:#fff;color:#374151;font:inherit;cursor:pointer}
    .filter-btn.is-active{background:#eef7f0;border-color:#b8d8c4;color:#166534}
    .table{width:100%;border-collapse:collapse;margin-top:14px}
    .table th,.table td{padding:14px 10px;text-align:left;border-top:1px solid #edf0f2;vertical-align:top}
    .table th{font-size:.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em}
    .table-name{color:#0f172a;font-weight:600}
    .empty{padding:22px 10px;color:#6b7280;text-align:center}
    .action-icons{display:flex;gap:14px;flex-wrap:wrap;align-items:center}.action-icons form{margin:0}
    .icon-btn{display:inline-flex;align-items:center;justify-content:center;background:transparent;color:#475569;cursor:pointer;text-decoration:none;border:none;padding:0}
    .icon-btn svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}
    .icon-btn:hover{color:#0f172a}.icon-btn.danger{color:#dc2626}.icon-btn.danger:hover{color:#991b1b}
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
            <h2>Company List</h2>
            <p></p>
        </div>
        <div class="top-actions">
            <a class="next" href="<?php echo $esc($setupUrl); ?>">+ Add Company</a>
        </div>
    </div>

    <?php if ($statusMessage !== ''): ?>
        <div class="notice"><?php echo $esc($statusMessage); ?></div>
    <?php endif; ?>

    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" placeholder="Search record">
        </div>
        <div class="toolbar-right">
            <button type="button" class="filter-btn" data-company-filter="surveillance">Surveillance</button>
            <button type="button" class="filter-btn" data-company-filter="audiometry">Audiometry</button>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Company Name</th>
                <th>MYKPP Registration No</th>
                <th>Contact</th>
                <th>Total Workers</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($totalCompanies > 0): ?>
                <?php foreach ($companies as $company): ?>
                    <?php $companyModule = (string) ($company->company_module ?? ''); ?>
                    <tr data-company-module="<?php echo $esc($companyModule !== '' ? $companyModule : 'shared'); ?>">
                        
                        <td>
                            <div class="stack">
                                <span class="table-name"><?php echo $esc($company->company_name ?: 'Company'); ?></span>
                                <span class="muted"><?php echo $esc($company->company_email ?: 'No email provided'); ?></span>
                            </div>
                        </td>
                        <td><?php echo $esc($company->mykpp_registration_no ?: '-'); ?></td>
                        <td>
                            <div class="stack">
                                <span><?php echo $esc($company->company_telephone ?: '-'); ?></span>
                                <span class="muted"><?php echo $esc($company->company_fax ? 'Fax: ' . $company->company_fax : ($company->company_state ?: '')); ?></span>
                            </div>
                        </td>
                        <td><?php echo $esc(number_format((int) ($company->total_workers ?? 0))); ?></td>
                        <td>
                            <div class="action-icons">
                                <a class="icon-btn" href="<?php echo $esc(route('panel.company.show', ['company' => $company->company_id])); ?>" title="View company" aria-label="View company">
                                    <svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </a>
                                <a class="icon-btn" href="<?php echo $esc(route('panel.company.edit', ['company' => $company->company_id])); ?>" title="Edit company" aria-label="Edit company">
                                    <svg viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                                </a>
                                <a class="icon-btn danger" href="<?php echo $esc(route('panel.company.delete', ['company' => $company->company_id, 'return_to' => $returnTo])); ?>" title="Delete company" aria-label="Delete company">
                                    <svg viewBox="0 0 24 24"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td class="empty" colspan="6">No company records found for the currently selected clinic.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="bottom">
        <span class="pager"><?php echo $totalCompanies > 0 ? 'Showing 1-' . number_format($totalCompanies) . ' of ' . number_format($totalCompanies) . ' records' : 'Showing 0 of 0 records'; ?></span>
        <div>
           
            <a class="next" href="<?php echo $esc($setupUrl); ?>">Next</a>
        </div>
    </div>
</section>

<script>
(function () {
    var filterButtons = Array.prototype.slice.call(document.querySelectorAll('[data-company-filter]'));
    var rows = Array.prototype.slice.call(document.querySelectorAll('tbody tr[data-company-module]'));
    var activeFilter = '';

    filterButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var filter = button.getAttribute('data-company-filter') || '';
            activeFilter = activeFilter === filter ? '' : filter;
            filterButtons.forEach(function (item) {
                item.classList.toggle('is-active', item === button && activeFilter !== '');
            });
            rows.forEach(function (row) {
                var module = row.getAttribute('data-company-module') || '';
                var shouldShow = activeFilter === '' || module === activeFilter || module === 'shared';
                row.style.display = shouldShow ? '' : 'none';
            });
        });
    });
})();
</script>

<?php medis_render_navigation_end(); ?>
</body>
</html>
