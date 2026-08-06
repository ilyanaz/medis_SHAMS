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
    .btn,.next,.page-btn{display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;background:#fff;color:#374151}
    .next{background:#389B5B;border-color:#389B5B;color:#fff}
    .toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px}
    .toolbar input{border:1px solid #d1d5db;border-radius:12px;padding:10px 12px;min-width:280px}
    .table-wrap{margin-top:14px;flex:1;min-height:0;display:flex;align-items:flex-start}
    .table{width:100%;border-collapse:collapse}
    .table th,.table td{padding:14px 10px;text-align:left;border-top:0}
    .table th{font-size:.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em}
    .table thead tr{border-top:1px solid #edf0f2;border-bottom:1px solid #edf0f2}
    .table-name-link{color:#0f172a;text-decoration:none;font-weight:600}
    .table-name-link:hover{color:#389B5B;text-decoration:underline}
    .filler-row td{height:56px;color:transparent;user-select:none}
    .empty{padding:22px 10px;color:#6b7280;text-align:center}
    .action-icons{display:flex;gap:10px}
    .icon-btn svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.8}
    .icon-btn{color:#111827}
    .icon-btn.delete{color:#ef4444}
    .bottom{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px}
    .pager{color:#6b7280;font-size:.84rem}
    .pager-group{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .page-btn{cursor:pointer;padding:8px 12px}
    .page-btn[disabled]{opacity:.45;cursor:not-allowed}
    .page-btn.is-active{background:#389B5B;border-color:#389B5B;color:#fff}
    .page-numbers{display:flex;gap:8px;flex-wrap:wrap}
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

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>MYKPP Registration No</th>
                        <th>Total Workers</th>
                        <th>Telephone No</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody id="companyTableBody">
                    <?php if($totalCompanies > 0): ?>
                        <?php foreach($companies as $company): ?>
                            <tr data-company-row="1">
                                <td>
                                    <a class="table-name-link" href="<?php echo $esc(route('surveillance.patient', ['company_id' => $company->company_id])); ?>">
                                        <?php echo $esc($company->company_name); ?>
                                    </a>
                                </td>
                                <td><?php echo $esc($company->mykpp_registration_no ?: '-'); ?></td>
                                <td><?php echo $esc(number_format((int) ($company->total_workers ?? 0))); ?></td>
                                <td><?php echo $esc($company->company_telephone ?: '-'); ?></td>
                                <td><?php echo $esc($company->company_email ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="companyEmptyRow"><td class="empty" colspan="5">No company records found in the current database.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="bottom">
            <span class="pager" id="companyPager"><?php echo $totalCompanies > 0 ? 'Showing 1-' . number_format($totalCompanies) . ' of ' . number_format($totalCompanies) . ' records' : 'Showing 0 of 0 records'; ?></span>
            <div class="pager-group">
                <?php if($totalCompanies > 0): ?>
                    <button class="page-btn" id="companyPrevBtn" type="button">Previous</button>
                    <div class="page-numbers" id="companyPageNumbers"></div>
                    <button class="page-btn" id="companyNextBtn" type="button">Next</button>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<script>
(function(){
    const search = document.querySelector('.toolbar input');
    const body = document.getElementById('companyTableBody');
    const rows = Array.prototype.slice.call(document.querySelectorAll('tr[data-company-row]'));
    const emptyRow = document.getElementById('companyEmptyRow');
    const pager = document.getElementById('companyPager');
    const prevBtn = document.getElementById('companyPrevBtn');
    const nextBtn = document.getElementById('companyNextBtn');
    const pageNumbers = document.getElementById('companyPageNumbers');
    if (!body || !pager || !rows.length) { return; }
    const perPage = 5;
    let currentPage = 1;
    const fillerClass = 'filler-row';

    const clearFillers = function(){
        Array.prototype.slice.call(body.querySelectorAll('.' + fillerClass)).forEach(function(row){ row.remove(); });
    };

    const appendFillers = function(count){
        for (let i = 0; i < count; i += 1) {
            const filler = document.createElement('tr');
            filler.className = fillerClass;
            for (let col = 0; col < 5; col += 1) {
                const cell = document.createElement('td');
                cell.innerHTML = '&nbsp;';
                filler.appendChild(cell);
            }
            body.appendChild(filler);
        }
    };

    const getFilteredRows = function(){
        const term = (search && search.value ? search.value : '').trim().toLowerCase();
        if (!term) { return rows; }
        return rows.filter(function(row){
            return (row.textContent || '').toLowerCase().indexOf(term) !== -1;
        });
    };

    const render = function(){
        clearFillers();
        const filtered = getFilteredRows();
        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        if (currentPage > totalPages) { currentPage = totalPages; }
        if (currentPage < 1) { currentPage = 1; }
        rows.forEach(function(row){ row.style.display = 'none'; });
        const start = (currentPage - 1) * perPage;
        const end = Math.min(start + perPage, total);
        const visibleRows = filtered.slice(start, end);
        visibleRows.forEach(function(row){ row.style.display = ''; });
        if (emptyRow) { emptyRow.style.display = total ? 'none' : ''; }
        if (total > 0 && visibleRows.length < perPage) {
            appendFillers(perPage - visibleRows.length);
        }
        pager.textContent = total ? ('Showing ' + (start + 1) + '-' + end + ' of ' + total + ' records') : 'Showing 0 of 0 records';
        if (prevBtn) { prevBtn.disabled = currentPage === 1 || total === 0; }
        if (nextBtn) { nextBtn.disabled = currentPage === totalPages || total === 0; }
        if (pageNumbers) {
            pageNumbers.innerHTML = '';
            for (let page = 1; page <= totalPages; page += 1) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'page-btn' + (page === currentPage ? ' is-active' : '');
                button.textContent = String(page);
                button.addEventListener('click', function(){
                    currentPage = page;
                    render();
                });
                pageNumbers.appendChild(button);
            }
        }
    };

    if (search) {
        search.addEventListener('input', function(){
            currentPage = 1;
            render();
        });
    }
    if (prevBtn) {
        prevBtn.addEventListener('click', function(){
            if (currentPage > 1) {
                currentPage -= 1;
                render();
            }
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function(){
            if (currentPage < Math.max(1, Math.ceil(getFilteredRows().length / perPage))) {
                currentPage += 1;
                render();
            }
        });
    }

    render();
}());
</script>

<?php medis_render_navigation_end(); ?>
</body>
</html>
