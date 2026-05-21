<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surveillance - Company</title>
</head>
<body>
<?php
require dirname(__DIR__) . '/panel/navigation.php';

$pdfMode = !empty($pdfMode);
$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$newCompanyUrl = function_exists('route') ? route('panel.company_new', ['company_module' => 'surveillance']) : '#';
$returnTo = function_exists('url') ? url()->full() : '#';
$steps = [
    ['label' => 'Company', 'url' => function_exists('route') ? route('surveillance.company') : '#', 'active' => true],
    ['label' => 'Patient', 'url' => function_exists('route') ? route('surveillance.patient') : '#'],
    ['label' => 'Surveillance List', 'url' => function_exists('route') ? route('surveillance.list') : '#'],
    ['label' => 'Declaration', 'url' => function_exists('route') ? route('surveillance.declaration') : '#'],
    ['label' => 'Examination', 'url' => function_exists('route') ? route('surveillance.examination') : '#'],
    ['label' => 'Report', 'url' => function_exists('route') ? route('surveillance.report') : '#'],
];
$companies = isset($companies) ? $companies : collect();
$totalCompanies = isset($companyTotal) ? (int) $companyTotal : (is_countable($companies) ? count($companies) : 0);

medis_render_navigation_start([
    'clinicName' => $clinicName ?? 'Medis SHAMS',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'User',
    'active' => 'surveillance',
    'showSurveillanceSubnav' => true,
    'surveillanceSubActive' => 'company',
    'pdfMode' => $pdfMode,
]);
?>
<style>
    .content{padding:4px 6px;overflow:auto;min-height:0;margin-top:0;border:0;background:transparent;border-radius:0}
    .head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
    .head h2{margin:0 0 12px;font-size:1.8rem}
    .head p{margin:6px 0 0;color:#6b7280}
    .top-actions{display:flex;gap:10px;flex-wrap:wrap}
    .btn,.next{display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;background:#fff;color:#374151}
    .next{background:#389B5B;border-color:#389B5B;color:#fff}
    .toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px}
    .toolbar input{border:1px solid #d1d5db;border-radius:12px;padding:10px 12px;min-width:280px}
    .table{width:100%;border-collapse:collapse;margin-top:14px}
    .table th,.table td{padding:14px 10px;text-align:left;border-top:1px solid #edf0f2}
    .table th{font-size:.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em}
    .table-name-link{color:#0f172a;text-decoration:none;font-weight:600}
    .table-name-link:hover{color:#389B5B;text-decoration:underline}
    .empty{padding:22px 10px;color:#6b7280;text-align:center}
    .action-icons{display:flex;gap:10px}
    .icon-btn svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.8}
    .icon-btn{color:#111827}
    .icon-btn.delete{color:#ef4444}
    .bottom{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px}
    .pager{color:#6b7280;font-size:.84rem}
    @media(max-width:1100px){.stepper{padding:14px}.step-list{grid-template-columns:repeat(3,minmax(0,1fr))}.step-label{max-width:none}}
</style>
<style>
    .flow{height:calc(100dvh - 204px);min-height:calc(100dvh - 204px);display:flex}
    .content{margin-top:0;overflow:hidden;height:100%;width:100%;display:flex;flex-direction:column}
    .bottom{margin-top:auto;padding-top:18px}
    @media(max-width:1180px){.flow{height:auto;min-height:auto}.content{height:auto;min-height:auto}}
    @media(max-width:760px){.content{padding:0}}
</style>

<div class="flow">
    <section class="content">
        <div class="head">
            <div>
                <h2>Company List</h2>
                
            </div>
            <div class="top-actions">
            <a class="next" href="<?php echo $esc($newCompanyUrl); ?>">+ Add Company</a>
            </div>
        </div>

        <div class="toolbar">
            <input type="text" placeholder="Search company">
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Company Name</th>
                    <th>MYKPP Registration No</th>
                    <th>Total Workers</th>
                    <th>Telephone No</th>
                    <th>Email</th>
                    <!--<th>Action</th>-->
                </tr>
            </thead>
            <tbody>
                <?php if($totalCompanies > 0): ?>
                    <?php foreach($companies as $company): ?>
                        <tr>
                            <td>
                                <a class="table-name-link" href="<?php echo $esc(route('surveillance.patient', ['company_id' => $company->company_id])); ?>">
                                    <?php echo $esc($company->company_name); ?>
                                </a>
                            </td>
                            <td><?php echo $esc($company->mykpp_registration_no ?: '-'); ?></td>
                            <td><?php echo $esc(number_format((int) ($company->total_workers ?? 0))); ?></td>
                            <td><?php echo $esc($company->company_telephone ?: '-'); ?></td>
                            <td><?php echo $esc($company->company_email ?: '-'); ?></td>
                            <!--<td>
                                <div class="action-icons">
                                    <a class="icon-btn" href="<?php echo $esc(route('surveillance.company.edit', ['id' => $company->company_id])); ?>" title="View Company">
                                        <svg viewBox="0 0 24 24"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </a>
                                    <a class="icon-btn" href="<?php echo $esc(route('surveillance.company.edit', ['id' => $company->company_id])); ?>" title="Edit Company">
                                        <svg viewBox="0 0 24 24"><path d="M4 20h4l10-10-4-4L4 16v4z"></path><path d="M13 7l4 4"></path></svg>
                                    </a>
                                    <a class="icon-btn delete" href="<?php echo $esc(route('surveillance.company.delete', ['id' => $company->company_id, 'return_to' => $returnTo])); ?>" title="Delete Company" onclick="return confirm('Are you sure you want to delete this company? This action cannot be undone.');">
                                        <svg viewBox="0 0 24 24"><path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 13h10l1-13"></path><path d="M9 7V4h6v3"></path></svg>
                                    </a>
                                </div>
                            </td>-->
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td class="empty" colspan="7">No company records found in the current database.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="bottom">
            <span class="pager"><?php echo $totalCompanies > 0 ? 'Showing 1-' . number_format($totalCompanies) . ' of ' . number_format($totalCompanies) . ' records' : 'Showing 0 of 0 records'; ?></span>
            <div></div>
        </div>
    </section>
</div>

<?php medis_render_navigation_end(); ?>
</body>
</html>
