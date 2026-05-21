<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>General Examination</title>
</head>
<body>
<?php
require dirname(__DIR__) . '/panel/navigation.php';
$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
medis_render_navigation_start([
    'clinicName' => $clinicName ?? 'Medis SHAMS',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'User',
    'active' => 'examination',
    'pageTitle' => 'General Examination',
    'pageSubtitle' => 'Manage examination pages by module and step',
]);

$companyNames = [];
foreach (($companies ?? []) as $company) {
    $name = trim((string) ($company->company_name ?? ''));
    if ($name !== '') {
        $companyNames[] = $name;
    }
}
if (empty($companyNames)) {
    $companyNames = ['Alpha Engineering', 'Beta Manufacturing', 'Gamma Plantations'];
}

$pickCompany = static function (int $index) use ($companyNames): string {
    return $companyNames[$index % count($companyNames)];
};

$surveillanceRows = [];
if (\Illuminate\Support\Facades\Schema::hasTable('declaration')) {
    $declarationRows = \Illuminate\Support\Facades\DB::table('declaration')
        ->leftJoin('company', 'company.company_id', '=', 'declaration.company_id')
        ->leftJoin('employee', 'employee.employee_id', '=', 'declaration.employee_id')
        ->leftJoin('recommendation', 'recommendation.surveillance_id', '=', 'declaration.surveillance_id')
        ->select(
            'declaration.declaration_id',
            'declaration.surveillance_id',
            'declaration.company_id',
            'declaration.employee_id',
            'declaration.company_name',
            'declaration.employee_firstName',
            'declaration.employee_lastName',
            'declaration.employee_date',
            'declaration.doctor_date',
            'declaration.employee_signature',
            'declaration.doctor_signature',
            'company.company_name as linked_company_name',
            'employee.employee_firstName as linked_first_name',
            'employee.employee_lastName as linked_last_name',
            'recommendation.is_final as recommendation_is_final'
        )
        ->orderByDesc('declaration.declaration_id')
        ->get();

    foreach ($declarationRows as $row) {
        $employeeName = trim((string) (($row->linked_first_name ?? $row->employee_firstName ?? '') . ' ' . ($row->linked_last_name ?? $row->employee_lastName ?? '')));
        $companyName = trim((string) ($row->linked_company_name ?? $row->company_name ?? ''));
        $recordParams = array_filter([
            'company_id' => (int) ($row->company_id ?? 0) ?: null,
            'employee_id' => (int) ($row->employee_id ?? 0) ?: null,
            'declaration_id' => (int) ($row->declaration_id ?? 0) ?: null,
            'record_mode' => 1,
        ]);
        $hasDeclarationSignatures = !empty($row->employee_signature) && !empty($row->doctor_signature) && !empty($row->employee_date) && !empty($row->doctor_date);
        $surveillanceRows[] = [
            'module' => 'surveillance',
            'filter' => 'declaration',
            'employee_name' => $employeeName !== '' ? $employeeName : 'Not set',
            'company' => $companyName !== '' ? $companyName : $pickCompany(0),
            'stage' => 'Declaration',
            'status' => $hasDeclarationSignatures ? 'Completed' : 'Incomplete',
            'status_key' => $hasDeclarationSignatures ? 'completed' : 'incomplete',
            'date_examined' => (string) ($row->employee_date ?: $row->doctor_date ?: date('Y-m-d')),
            'href' => function_exists('route') ? route('surveillance.declaration', $recordParams) : '#',
        ];

        if (!empty($row->surveillance_id)) {
            $isExamCompleted = !empty($row->recommendation_is_final);
            $surveillanceRows[] = [
                'module' => 'surveillance',
                'filter' => 'examination',
                'employee_name' => $employeeName !== '' ? $employeeName : 'Not set',
                'company' => $companyName !== '' ? $companyName : $pickCompany(0),
                'stage' => 'Examination',
                'status' => $isExamCompleted ? 'Completed' : 'Incomplete',
                'status_key' => $isExamCompleted ? 'completed' : 'incomplete',
                'date_examined' => (string) ($row->employee_date ?: $row->doctor_date ?: date('Y-m-d')),
                'href' => function_exists('route') ? route('surveillance.record.edit', ['declaration' => (int) $row->declaration_id]) : '#',
            ];
        }
    }
}

$audioRows = [
    ['module' => 'audiometry', 'filter' => 'questionnaire', 'employee_name' => 'Zul Hilmi', 'company' => $pickCompany(0), 'stage' => 'Questionnaire', 'status' => 'Completed', 'status_key' => 'completed', 'date_examined' => '2026-03-09', 'href' => function_exists('route') ? route('audiometry.questionnaire') : '#'],
    ['module' => 'audiometry', 'filter' => 'examination', 'employee_name' => 'Farah Nadia', 'company' => $pickCompany(1), 'stage' => 'Examination', 'status' => 'Incomplete', 'status_key' => 'incomplete', 'date_examined' => '2026-03-12', 'href' => function_exists('route') ? route('audiometry.examination') : '#'],
    ['module' => 'audiometry', 'filter' => 'report', 'employee_name' => 'Hakim Roslan', 'company' => $pickCompany(2), 'stage' => 'Report', 'status' => 'Incomplete', 'status_key' => 'incomplete', 'date_examined' => '2026-03-14', 'href' => function_exists('route') ? route('audiometry.report') : '#'],
];
$rows = array_merge(!empty($surveillanceRows) ? $surveillanceRows : ($surveillanceExamRows ?? []), $audioRows);
?>
<style>
.exam-shell{display:grid;gap:18px}
.exam-head h2{margin:0;font-size:1.9rem}
.exam-head p{margin:8px 0 0;color:#6b7280}
.manage-card{border:1px solid #e5e7eb;border-radius:20px;background:#fff;padding:0;overflow:hidden;display:flex;flex-direction:column}
.module-bar{display:flex;gap:12px;padding:18px;border-bottom:1px solid #edf0f2;flex-wrap:wrap}
.module-btn{appearance:none;border:1px solid #d1d5db;background:#fff;border-radius:12px;padding:12px 20px;font:inherit;font-weight:700;color:#374151;cursor:pointer;min-width:150px}
.module-btn.active{background:#eef7f0;border-color:#b8d8c4;color:#166534}
.subfilter-bar{display:flex;gap:18px;align-items:center;padding:0 18px;border-bottom:1px solid #edf0f2;flex-wrap:wrap}
.subfilter-btn{appearance:none;border:0;background:transparent;padding:14px 0 12px;font:inherit;font-weight:600;color:#4b5563;cursor:pointer;position:relative;text-transform:uppercase;font-size:.82rem}
.subfilter-btn.active{color:#166534}
.subfilter-btn.active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:#389B5B;border-radius:999px}
.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid #edf0f2;flex-wrap:wrap}
.toolbar-left,.toolbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.toolbar-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#374151;padding:9px 12px;text-decoration:none;font:inherit;cursor:pointer}
.toolbar-btn.is-active{background:#eef7f0;border-color:#b8d8c4;color:#166534}
.search{width:min(420px,100%);border:1px solid #d1d5db;border-radius:10px;padding:10px 12px;font:inherit}
.filter-backdrop{display:none;position:fixed;inset:0;background:rgba(15,23,42,.18);z-index:120}
.filter-backdrop.is-open{display:block}
.filter-panel{display:none;position:fixed;top:110px;right:36px;width:min(520px,calc(100vw - 32px));padding:18px;border:1px solid #dbe3ea;border-radius:18px;background:#fff;box-shadow:0 26px 60px rgba(15,23,42,.16);z-index:121}
.filter-panel.is-open{display:block}
.filter-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
.filter-panel-head h3{margin:0;font-size:1rem}
.filter-close{border:0;background:transparent;color:#6b7280;font-size:1.35rem;line-height:1;cursor:pointer;padding:0 4px}
.filter-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;align-items:end}
.field{display:grid;gap:8px}
.field label{font-size:.86rem;font-weight:600;color:#374151}
.field input,.field select{width:100%;border:1px solid #d1d5db;border-radius:12px;padding:10px 12px;font:inherit;background:#fff}
.field.full{grid-column:1/-1}
.field-actions{display:flex;gap:10px;align-items:center;justify-content:flex-end;grid-column:1/-1}
.clear-btn,.apply-btn{display:inline-flex;align-items:center;justify-content:center;border-radius:12px;padding:10px 14px;font:inherit;cursor:pointer;text-decoration:none}
.clear-btn{border:1px solid #d1d5db;background:#fff;color:#374151}
.apply-btn{border:1px solid #389B5B;background:#389B5B;color:#fff}
.manage-card-body{display:flex;flex-direction:column;flex:1}
.exam-table{width:100%;border-collapse:collapse}
.exam-table th,.exam-table td{padding:16px 18px;text-align:left;border-top:1px solid #edf0f2;vertical-align:top}
.exam-table th{font-size:.78rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;background:#fafafa}
.filler-row td{height:56px;color:transparent;user-select:none}
.status{display:inline-flex;align-items:center;border-radius:999px;padding:5px 10px;font-weight:700;font-size:.76rem}
.status.completed{background:#dcfce7;color:#166534}
.status.pending,.status.incomplete{background:#fef3c7;color:#92400e}
.action-icons{display:flex;gap:10px;align-items:center}
.icon-btn{display:inline-flex;align-items:center;justify-content:center;background:transparent;border:0;padding:0;color:#111827;cursor:pointer;text-decoration:none}
.icon-btn svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.8}
.icon-btn.delete{color:#ef4444}
.table-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 18px;border-top:1px solid #edf0f2;flex-wrap:wrap;margin-top:auto}
.pager{color:#6b7280;font-size:.84rem}
.pager-group{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.page-btn{display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:1px solid #d1d5db;border-radius:12px;padding:8px 12px;background:#fff;color:#374151;cursor:pointer}
.page-btn[disabled]{opacity:.45;cursor:not-allowed}
.page-btn.is-active{background:#389B5B;border-color:#389B5B;color:#fff}
.page-numbers{display:flex;gap:8px;flex-wrap:wrap}
.empty-row td{text-align:center;color:#6b7280}
@media(max-width:980px){.toolbar{align-items:stretch}.toolbar-left,.toolbar-right{width:100%}.toolbar-right{justify-content:flex-start}.search{width:100%}.subfilter-bar{gap:14px}.filter-panel{top:96px;right:16px}}
@media(max-width:640px){.filter-grid{grid-template-columns:1fr}.filter-panel{top:88px;width:calc(100vw - 24px);right:12px}}
</style>
<div class="exam-shell">
    <section class="exam-head">
        <h2>Manage Examinations</h2>
    </section>

    <section class="manage-card">
        <div class="module-bar">
            <button class="module-btn active" type="button" data-module="surveillance">Surveillance</button>
            <button class="module-btn" type="button" data-module="audiometry">Audiometry</button>
        </div>

        <div class="subfilter-bar" id="subfilterBar"></div>

        <div class="toolbar">
            <div class="toolbar-left">
                <input class="search" id="examSearch" type="text" placeholder="Search employee, company, or step">
            </div>
            <div class="toolbar-right">
                <button class="toolbar-btn" id="filterToggleBtn" type="button">Filter</button>
                <button class="toolbar-btn" type="button">Sort by</button>
            </div>
        </div>

        <div class="filter-backdrop" id="filterBackdrop"></div>
        <div class="filter-panel" id="filterPanel">
            <div class="filter-panel-head">
                <h3>Filter examinations</h3>
                <button class="filter-close" id="filterCloseBtn" type="button" aria-label="Close filter">&times;</button>
            </div>
            <div class="filter-grid">
                <div class="field full">
                    <label for="filterSearch">Search</label>
                    <input id="filterSearch" type="text" placeholder="Search employee, company, or step">
                </div>
                <div class="field">
                    <label for="filterCompany">Company Name</label>
                    <select id="filterCompany">
                        <option value="">All companies</option>
                        <?php foreach ($companyNames as $companyName): ?>
                            <option value="<?php echo $esc($companyName); ?>"><?php echo $esc($companyName); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="filterStatus">Status</label>
                    <select id="filterStatus">
                        <option value="">All status</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="incomplete">Incomplete</option>
                    </select>
                </div>
                <div class="field full">
                    <label for="filterDate">Date Examined</label>
                    <input id="filterDate" type="date">
                </div>
                <div class="field-actions">
                    <button class="clear-btn" id="filterClearBtn" type="button">Clear filters</button>
                    <button class="apply-btn" id="filterApplyBtn" type="button">Apply</button>
                </div>
            </div>
        </div>

        <div class="manage-card-body">
            <table class="exam-table">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Company Name</th>
                        <th>Step</th>
                        <th>Date Examined</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="examTableBody">
                    <?php foreach ($rows as $row): ?>
                        <tr data-module="<?php echo $esc($row['module']); ?>" data-filter="<?php echo $esc($row['filter']); ?>" data-company="<?php echo $esc(strtolower($row['company'])); ?>" data-status="<?php echo $esc($row['status_key']); ?>" data-date="<?php echo $esc($row['date_examined']); ?>">
                            <td><?php echo $esc($row['employee_name']); ?></td>
                            <td><?php echo $esc($row['company']); ?></td>
                            <td><?php echo $esc($row['stage']); ?></td>
                            <td><?php echo $esc(date('d M Y', strtotime($row['date_examined']))); ?></td>
                            <td><span class="status <?php echo $esc($row['status_key']); ?>"><?php echo $esc($row['status']); ?></span></td>
                            <td>
                                <div class="action-icons"><a class="icon-btn" href="<?php echo $esc($row['href']); ?>" title="View"><svg viewBox="0 0 24 24"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"></path><circle cx="12" cy="12" r="3"></circle></svg></a><a class="icon-btn" href="<?php echo $esc($row['href']); ?>" title="Edit"><svg viewBox="0 0 24 24"><path d="M4 20h4l10-10-4-4L4 16v4z"></path><path d="M13 7l4 4"></path></svg></a><button class="icon-btn delete" type="button" data-name="<?php echo $esc($row['employee_name']); ?>" title="Delete"><svg viewBox="0 0 24 24"><path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 13h10l1-13"></path><path d="M9 7V4h6v3"></path></svg></button></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="empty-row" id="examEmptyRow" style="display:none;">
                        <td colspan="6">No examination records match the selected filter.</td>
                    </tr>
                </tbody>
            </table>

            <div class="table-foot">
                <span class="pager" id="examPager">Showing 0 records</span>
                <div class="pager-group">
                    <button class="page-btn" id="examPrevBtn" type="button">Previous</button>
                    <div class="page-numbers" id="examPageNumbers"></div>
                    <button class="page-btn" id="examNextBtn" type="button">Next</button>
                </div>
            </div>
        </div>
    </section>
</div>
<script>
(function(){
    const moduleButtons = Array.prototype.slice.call(document.querySelectorAll('.module-btn'));
    const subfilterBar = document.getElementById('subfilterBar');
    const search = document.getElementById('examSearch');
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterPanel = document.getElementById('filterPanel');
    const filterBackdrop = document.getElementById('filterBackdrop');
    const filterCloseBtn = document.getElementById('filterCloseBtn');
    const filterSearch = document.getElementById('filterSearch');
    const filterCompany = document.getElementById('filterCompany');
    const filterStatus = document.getElementById('filterStatus');
    const filterDate = document.getElementById('filterDate');
    const filterApplyBtn = document.getElementById('filterApplyBtn');
    const filterClearBtn = document.getElementById('filterClearBtn');
    const rows = Array.prototype.slice.call(document.querySelectorAll('#examTableBody tr[data-module]'));
    const pager = document.getElementById('examPager');
    const emptyRow = document.getElementById('examEmptyRow');
    const prevBtn = document.getElementById('examPrevBtn');
    const nextBtn = document.getElementById('examNextBtn');
    const pageNumbers = document.getElementById('examPageNumbers');
    const deleteButtons = Array.prototype.slice.call(document.querySelectorAll('.icon-btn.delete'));
    const filtersByModule = {
        surveillance: ['all','declaration','examination'],
        audiometry: ['all','questionnaire','examination','report']
    };
    let activeModule = 'surveillance';
    let activeFilter = 'all';
    let currentPage = 1;
    const perPage = 5;
    const fillerClass = 'filler-row';

    const titleCase = function(value){
        return value.split(' ').map(function(part){
            return part.length ? part.charAt(0).toUpperCase() + part.slice(1) : part;
        }).join(' ');
    };

    const getMergedSearch = function(){
        const main = (search.value || '').trim();
        const panel = (filterSearch.value || '').trim();
        return [main, panel].filter(Boolean).join(' ').toLowerCase();
    };

    const setFilterOpen = function(open){
        if (!filterPanel || !filterToggleBtn) { return; }
        filterPanel.classList.toggle('is-open', open);
        filterToggleBtn.classList.toggle('is-active', open);
        if (filterBackdrop) { filterBackdrop.classList.toggle('is-open', open); }
    };

    const renderSubfilters = function(){
        const filters = filtersByModule[activeModule] || ['all'];
        subfilterBar.innerHTML = '';
        filters.forEach(function(filter){
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'subfilter-btn' + (filter === activeFilter ? ' active' : '');
            btn.setAttribute('data-filter', filter);
            btn.textContent = titleCase(filter);
            btn.addEventListener('click', function(){
                activeFilter = filter;
                renderSubfilters();
                renderRows();
            });
            subfilterBar.appendChild(btn);
        });
    };

    const getFilteredRows = function(){
        const query = getMergedSearch();
        const companyValue = (filterCompany.value || '').trim().toLowerCase();
        const statusValue = (filterStatus.value || '').trim().toLowerCase();
        const dateValue = (filterDate.value || '').trim();
        return rows.filter(function(row){
            const moduleMatch = row.getAttribute('data-module') === activeModule;
            const filterMatch = activeFilter === 'all' || row.getAttribute('data-filter') === activeFilter;
            const companyMatch = !companyValue || row.getAttribute('data-company') === companyValue;
            const statusMatch = !statusValue || row.getAttribute('data-status') === statusValue;
            const dateMatch = !dateValue || row.getAttribute('data-date') === dateValue;
            const text = row.textContent.toLowerCase();
            const searchMatch = !query || text.indexOf(query) !== -1;
            return moduleMatch && filterMatch && companyMatch && statusMatch && dateMatch && searchMatch;
        });
    };

    const clearFillers = function(){
        Array.prototype.slice.call(document.querySelectorAll('#examTableBody .' + fillerClass)).forEach(function(row){
            row.remove();
        });
    };

    const appendFillers = function(count){
        for (let i = 0; i < count; i += 1) {
            const filler = document.createElement('tr');
            filler.className = fillerClass;
            for (let col = 0; col < 6; col += 1) {
                const cell = document.createElement('td');
                cell.innerHTML = '&nbsp;';
                filler.appendChild(cell);
            }
            document.getElementById('examTableBody').appendChild(filler);
        }
    };

    const renderRows = function(){
        clearFillers();
        const filteredRows = getFilteredRows();
        const total = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        if (currentPage > totalPages) { currentPage = totalPages; }
        if (currentPage < 1) { currentPage = 1; }
        const start = (currentPage - 1) * perPage;
        const end = Math.min(start + perPage, total);

        rows.forEach(function(row){
            row.style.display = 'none';
        });
        const visibleRows = filteredRows.slice(start, end);
        visibleRows.forEach(function(row){
            row.style.display = '';
        });

        if (total > 0 && visibleRows.length < perPage) {
            appendFillers(perPage - visibleRows.length);
        }

        if (pager) {
            pager.textContent = total === 0 ? 'Showing 0 records' : 'Showing ' + (start + 1) + '-' + end + ' of ' + total + ' records';
        }
        if (emptyRow) {
            emptyRow.style.display = total ? 'none' : '';
        }
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
                    renderRows();
                });
                pageNumbers.appendChild(button);
            }
        }
    };

    moduleButtons.forEach(function(button){
        button.addEventListener('click', function(){
            moduleButtons.forEach(function(btn){ btn.classList.remove('active'); });
            button.classList.add('active');
            activeModule = button.getAttribute('data-module') || 'surveillance';
            activeFilter = 'all';
            renderSubfilters();
            currentPage = 1;
            renderRows();
        });
    });

    if (search) { search.addEventListener('input', function(){ currentPage = 1; renderRows(); }); }
    if (filterSearch) { filterSearch.addEventListener('input', function(){ currentPage = 1; renderRows(); }); }
    if (filterCompany) { filterCompany.addEventListener('change', function(){ currentPage = 1; renderRows(); }); }
    if (filterStatus) { filterStatus.addEventListener('change', function(){ currentPage = 1; renderRows(); }); }
    if (filterDate) { filterDate.addEventListener('change', function(){ currentPage = 1; renderRows(); }); }
    if (filterToggleBtn) { filterToggleBtn.addEventListener('click', function(){ setFilterOpen(!filterPanel.classList.contains('is-open')); }); }
    if (filterCloseBtn) { filterCloseBtn.addEventListener('click', function(){ setFilterOpen(false); }); }
    if (filterBackdrop) { filterBackdrop.addEventListener('click', function(){ setFilterOpen(false); }); }
    if (filterApplyBtn) { filterApplyBtn.addEventListener('click', function(){ currentPage = 1; renderRows(); setFilterOpen(false); }); }
    if (prevBtn) { prevBtn.addEventListener('click', function(){ if (currentPage > 1) { currentPage -= 1; renderRows(); } }); }
    if (nextBtn) { nextBtn.addEventListener('click', function(){ if (currentPage < Math.max(1, Math.ceil(getFilteredRows().length / perPage))) { currentPage += 1; renderRows(); } }); }
    if (filterClearBtn) {
        filterClearBtn.addEventListener('click', function(){
            if (search) { search.value = ''; }
            if (filterSearch) { filterSearch.value = ''; }
            if (filterCompany) { filterCompany.value = ''; }
            if (filterStatus) { filterStatus.value = ''; }
            if (filterDate) { filterDate.value = ''; }
            currentPage = 1;
            renderRows();
        });
    }
    deleteButtons.forEach(function(button){
        button.addEventListener('click', function(){
            const name = button.getAttribute('data-name') || 'this record';
            window.alert('Delete action for ' + name + ' is not connected yet.');
        });
    });

    renderSubfilters();
    renderRows();
})();
</script>
<?php medis_render_navigation_end(); ?>
</body>
</html>
