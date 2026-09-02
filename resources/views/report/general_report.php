<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>General Report</title>
</head>
<body>
<?php
require dirname(__DIR__) . '/panel/navigation.php';
$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
medis_render_navigation_start([
    'clinicName' => $clinicName ?? 'Medis SHAMS',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'User',
    'active' => 'report',
    'pageTitle' => 'General Report',
    'pageSubtitle' => 'Manage report pages by module and form type',
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

$audioRows = $audiometryReportRows ?? [];
if (empty($audioRows)) {
    $audioRows = [
        [
            'module' => 'audiometry',
            'filter' => 'questionnaire',
            'employee_name' => 'No audiometry record yet',
            'company' => $pickCompany(0),
            'chemical_name' => 'Noise exposure',
            'status' => 'Pending',
            'status_key' => 'pending',
            'date_examined' => '',
            'href' => function_exists('route') ? route('audiometry.questionnaire') : '#',
            'pdf_href' => function_exists('route') ? route('pdf.questionnaire') : '#',
        ],
    ];
}

$defaultSurveillanceRows = [
    [
        'module' => 'surveillance',
        'filter' => 'usechh 1',
        'employee_name' => 'Nur Aisyah',
        'company' => $pickCompany(0),
        'phone_no' => '012-889 2104',
        'identity_no' => '980101-10-5522',
        'chemical_name' => 'Toluene',
        'status' => 'Completed',
        'status_key' => 'completed',
        'date_examined' => '2026-03-05',
        'href' => function_exists('route') ? route('surveillance.report.usechh1') : '#',
        'pdf_href' => function_exists('route') ? route('pdf.usechh1') : '#',
    ],
    [
        'module' => 'surveillance',
        'filter' => 'usechh 2',
        'employee_name' => 'Hafiz Rahman',
        'company' => $pickCompany(1),
        'phone_no' => '013-420 1148',
        'identity_no' => 'A8819342',
        'chemical_name' => 'Xylene',
        'status' => 'Incomplete',
        'status_key' => 'incomplete',
        'date_examined' => '2026-03-07',
        'href' => function_exists('route') ? route('surveillance.report') : '#',
        'pdf_href' => function_exists('route') ? route('pdf.usechh2') : '#',
    ],
    [
        'module' => 'surveillance',
        'filter' => 'usechh 3',
        'employee_name' => 'Siti Mariam',
        'company' => $pickCompany(2),
        'phone_no' => '014-612 5520',
        'identity_no' => '900712-08-4431',
        'chemical_name' => 'Benzene',
        'status' => 'Completed',
        'status_key' => 'completed',
        'date_examined' => '2026-03-11',
        'href' => function_exists('route') ? route('surveillance.report') : '#',
        'pdf_href' => function_exists('route') ? route('pdf.usechh3') : '#',
    ],
    [
        'module' => 'surveillance',
        'filter' => 'usechh 4',
        'employee_name' => 'Daniel Lim',
        'company' => $pickCompany(0),
        'phone_no' => '011-722 9001',
        'identity_no' => '901212-14-2243',
        'chemical_name' => 'Lead',
        'status' => 'Pending',
        'status_key' => 'pending',
        'date_examined' => '2026-03-14',
        'href' => function_exists('route') ? route('surveillance.report') : '#',
        'pdf_href' => function_exists('route') ? route('pdf.usechh4') : '#',
    ],
    [
        'module' => 'surveillance',
        'filter' => 'usechh 5i',
        'employee_name' => 'Farid Hakim',
        'company' => $pickCompany(1),
        'phone_no' => '017-384 6631',
        'identity_no' => '870923-05-5521',
        'chemical_name' => 'Mercury',
        'status' => 'Completed',
        'status_key' => 'completed',
        'date_examined' => '2026-03-15',
        'href' => function_exists('route') ? route('surveillance.report') : '#',
        'pdf_href' => function_exists('route') ? route('pdf.usechh5i') : '#',
    ],
    [
        'module' => 'surveillance',
        'filter' => 'usechh 5ii',
        'employee_name' => 'Liyana Sofea',
        'company' => $pickCompany(2),
        'phone_no' => '019-202 7008',
        'identity_no' => '940818-11-8732',
        'chemical_name' => 'Chromium',
        'status' => 'Completed',
        'status_key' => 'completed',
        'date_examined' => '2026-03-18',
        'href' => function_exists('route') ? route('surveillance.report') : '#',
        'pdf_href' => function_exists('route') ? route('pdf.usechh5ii') : '#',
    ],
];

$rows = array_merge($surveillanceReportRows ?? $defaultSurveillanceRows, $audioRows);
?>
<style>
.flow{height:calc(100dvh - 204px);min-height:calc(100dvh - 204px);display:flex}
.content{padding:4px 6px;height:100%;width:100%;margin-top:0;border:0;background:transparent;border-radius:0;display:flex;flex-direction:column;overflow:hidden}
.report-shell{display:grid;gap:10px;height:100%;min-height:0}
.report-head h2{margin:0;font-size:1.9rem}
.report-head p{margin:8px 0 0;color:#6b7280}
.manage-card{border:0;border-radius:0;background:transparent;padding:0;overflow:hidden;display:flex;flex-direction:column;min-height:0;flex:1}
.module-bar{display:flex;gap:12px;padding:10px 18px;border-bottom:1px solid #edf0f2;flex-wrap:wrap}
.module-btn{appearance:none;border:1px solid #d1d5db;background:#fff;border-radius:12px;padding:10px 20px;font:inherit;font-weight:700;color:#374151;cursor:pointer;min-width:150px}
.module-btn.active{background:#eef7f0;border-color:#b8d8c4;color:#166534}
.subfilter-bar{display:none}
.subfilter-btn{appearance:none;border:0;background:transparent;padding:10px 0 9px;font:inherit;font-weight:600;color:#4b5563;cursor:pointer;position:relative;text-transform:uppercase;font-size:.82rem}
.subfilter-btn.active{color:#166534}
.subfilter-btn.active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:#389B5B;border-radius:999px}
.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 18px;border-bottom:1px solid #edf0f2;flex-wrap:wrap}
.toolbar-left,.toolbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.toolbar-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#374151;padding:8px 12px;text-decoration:none;font:inherit;cursor:pointer}
.toolbar-btn.is-active{background:#eef7f0;border-color:#b8d8c4;color:#166534}
.search{width:min(360px,100%);border:1px solid #d1d5db;border-radius:10px;padding:9px 12px;font:inherit}
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
.manage-card-body{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}
.table-shell{flex:1;min-height:0;overflow:auto}
.report-table{width:100%;border-collapse:collapse}
.report-table th,.report-table td{padding:10px 18px;text-align:left;border-top:1px solid #edf0f2;vertical-align:middle}
.report-table th{font-size:.78rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;background:#fafafa}
.summary-link{color:#166534;font-weight:700;text-decoration:none}
.summary-link:hover{text-decoration:underline}
.summary-date{color:#475569;font-weight:600}
.filler-row td{height:42px;color:transparent;user-select:none}
.table-foot{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:8px 18px;border-top:1px solid #edf0f2;flex-wrap:wrap;margin-top:auto;flex-shrink:0;background:#fff}
.pager{color:#6b7280;font-size:.78rem}
.pager-group{display:flex;align-items:center;gap:5px;flex-wrap:wrap}
.page-btn{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;text-decoration:none;border:1px solid #d6e4d9;border-radius:10px;padding:0 10px;background:#f7fbf8;color:#2f4b36;font-weight:600;font-size:.82rem;cursor:pointer;box-shadow:none}
.page-btn[disabled]{opacity:.45;cursor:not-allowed}
.page-btn.is-active{background:#389B5B;border-color:#389B5B;color:#fff;box-shadow:none}
.page-numbers{display:flex;gap:6px;flex-wrap:wrap}
.page-ellipsis{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:32px;color:#94a3b8;font-weight:700}
.empty-row td{text-align:center;color:#6b7280}
@media(max-width:1180px){.flow{height:auto;min-height:auto}.content{height:auto;min-height:auto}}
@media(max-width:980px){.toolbar{align-items:stretch}.toolbar-left,.toolbar-right{width:100%}.toolbar-right{justify-content:flex-start}.search{width:100%}.filter-panel{top:96px;right:16px}}
@media(max-width:640px){.filter-grid{grid-template-columns:1fr}.filter-panel{top:88px;width:calc(100vw - 24px);right:12px}}
</style>
<div class="flow"><div class="content"><div class="report-shell">
    <section class="report-head">
        <h2>Manage Reports</h2>
    </section>

    <section class="manage-card">
        <div class="module-bar">
            <button class="module-btn active" type="button" data-module="surveillance">Surveillance</button>
            <button class="module-btn" type="button" data-module="audiometry">Audiometry</button>
        </div>

        <div class="subfilter-bar" id="subfilterBar"></div>

        <div class="toolbar">
            <div class="toolbar-left">
                <input class="search" id="reportSearch" type="text" placeholder="Search Company Name">
            </div>
        </div>

        <div class="filter-backdrop" id="filterBackdrop"></div>
        <div class="filter-panel" id="filterPanel">
            <div class="filter-panel-head">
                <h3>Filter reports</h3>
                <button class="filter-close" id="filterCloseBtn" type="button" aria-label="Close filter">&times;</button>
            </div>
            <div class="filter-grid">
                <div class="field full">
                    <label for="filterSearch">Search</label>
                    <input id="filterSearch" type="text" placeholder="Search employee, company, or chemical">
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
            <div class="table-shell">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Company Name</th>
                            <th>Date Examined</th>
                        </tr>
                    </thead>
                    <tbody id="reportTableBody">
                        <tr class="empty-row" id="reportEmptyRow" style="display:none;">
                            <td colspan="2">No report records match the selected filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="table-foot">
                <span class="pager" id="reportPager">Showing 0 records</span>
                <div class="pager-group">
                    <button class="page-btn" id="reportPrevBtn" type="button">Previous</button>
                    <div class="page-numbers" id="reportPageNumbers"></div>
                    <button class="page-btn" id="reportNextBtn" type="button">Next</button>
                </div>
            </div>
        </div>
    </section>
</div></div></div>
<script>
(function(){
    const moduleButtons = Array.prototype.slice.call(document.querySelectorAll('.module-btn'));
    const subfilterBar = document.getElementById('subfilterBar');
    const search = document.getElementById('reportSearch');
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
    const pager = document.getElementById('reportPager');
    const emptyRow = document.getElementById('reportEmptyRow');
    const prevBtn = document.getElementById('reportPrevBtn');
    const nextBtn = document.getElementById('reportNextBtn');
    const pageNumbers = document.getElementById('reportPageNumbers');
    const body = document.getElementById('reportTableBody');
    const rows = <?php echo json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const filtersByModule = {
        surveillance: ['all','usechh 1','usechh 2','usechh 3','usechh 4','usechh 5i','usechh 5ii'],
        audiometry: ['all','questionnaire','report']
    };
    let activeModule = 'surveillance';
    let activeFilter = 'all';
    let currentPage = 1;
    const perPage = 5;
    const fillerClass = 'filler-row';
    const folderRouteBase = <?php echo json_encode(function_exists('route') ? route('general.report.folder') : '#'); ?>;

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
            const rowModule = String(row.module || '');
            const rowFilter = String(row.filter || '');
            const rowCompanyLabel = String(row.company || '').trim();
            const rowCompany = rowCompanyLabel.toLowerCase();
            const rowStatus = String(row.status_key || '');
            const rowDate = String(row.date_examined || '').trim();
            const text = [row.employee_name, row.company, row.chemical_name, row.filter, row.status].join(' ').toLowerCase();
            const matchModule = rowModule === activeModule;
            const matchFilter = activeFilter === 'all' || rowFilter === activeFilter;
            const matchSearch = query === '' || text.indexOf(query) !== -1;
            const matchCompany = companyValue === '' || rowCompany === companyValue;
            const matchStatus = statusValue === '' || rowStatus === statusValue;
            const matchDate = dateValue === '' || rowDate === dateValue;
            return matchModule && matchFilter && matchSearch && matchCompany && matchStatus && matchDate;
        }).sort(function(left, right){
            const leftCompany = String(left.company || '').toLowerCase();
            const rightCompany = String(right.company || '').toLowerCase();
            if (leftCompany !== rightCompany) {
                return leftCompany.localeCompare(rightCompany);
            }
            const leftDate = String(left.date_examined || '').trim();
            const rightDate = String(right.date_examined || '').trim();
            if (leftDate !== rightDate) {
                return rightDate.localeCompare(leftDate);
            }
            return String(left.employee_name || '').localeCompare(String(right.employee_name || ''));
        });
    };

    const clearFillers = function(){
        Array.prototype.slice.call(body.querySelectorAll('.' + fillerClass + ', .summary-row')).forEach(function(row){ row.remove(); });
    };

    const buildFolderKey = function(companyLabel, dateValue){
        return companyLabel.toLowerCase() + '|' + dateValue;
    };

    const formatGroupDate = function(value){
        if (!value) { return 'No examination date'; }
        const parts = String(value).split('-');
        if (parts.length !== 3) { return value; }
        return parts[2] + ' ' + ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][Number(parts[1]) - 1] + ' ' + parts[0];
    };

    const appendSummaryRow = function(companyLabel, dateValue){
        const row = document.createElement('tr');
        row.className = 'summary-row';

        const companyCell = document.createElement('td');
        const companyLink = document.createElement('a');
        companyLink.className = 'summary-link';
        companyLink.href = folderRouteBase + '?module=' + encodeURIComponent(activeModule) + '&company=' + encodeURIComponent(companyLabel) + '&date=' + encodeURIComponent(dateValue);
        companyLink.textContent = companyLabel;
        companyCell.appendChild(companyLink);

        const dateCell = document.createElement('td');
        dateCell.className = 'summary-date';
        dateCell.textContent = formatGroupDate(dateValue);

        row.appendChild(companyCell);
        row.appendChild(dateCell);
        body.appendChild(row);
    };

    const getGroupedRows = function(filteredRows){
        const groupedRows = [];
        const groups = {};
        filteredRows.forEach(function(row){
            const companyLabel = String(row.company || 'No company').trim() || 'No company';
            const dateValue = String(row.date_examined || '').trim();
            const groupKey = buildFolderKey(companyLabel, dateValue);
            if (!groups[groupKey]) {
                groups[groupKey] = {
                    key: groupKey,
                    companyLabel: companyLabel,
                    dateValue: dateValue,
                    rows: []
                };
                groupedRows.push(groups[groupKey]);
            }
            groups[groupKey].rows.push(row);
        });

        return groupedRows;
    };

    const appendFillers = function(count){
        for (let i = 0; i < count; i += 1) {
            const filler = document.createElement('tr');
            filler.className = fillerClass;
            for (let col = 0; col < 2; col += 1) {
                const cell = document.createElement('td');
                cell.innerHTML = '&nbsp;';
                filler.appendChild(cell);
            }
            body.appendChild(filler);
        }
    };

    const renderRows = function(){
        clearFillers();
        const filteredRows = getFilteredRows();
        const groupedRows = getGroupedRows(filteredRows);
        const total = groupedRows.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        if (currentPage > totalPages) { currentPage = totalPages; }
        if (currentPage < 1) { currentPage = 1; }
        const start = (currentPage - 1) * perPage;
        const end = Math.min(start + perPage, total);

        const visibleGroups = groupedRows.slice(start, end);
        visibleGroups.forEach(function(group){
            appendSummaryRow(group.companyLabel, group.dateValue);
        });

        if (total > 0 && visibleGroups.length < perPage) {
            appendFillers(perPage - visibleGroups.length);
        }

        if (emptyRow) {
            emptyRow.style.display = total === 0 ? '' : 'none';
        }
        pager.textContent = total === 0 ? 'Showing 0 records' : 'Showing ' + (start + 1) + '-' + end + ' of ' + total + ' records';
        if (prevBtn) { prevBtn.disabled = currentPage === 1 || total === 0; }
        if (nextBtn) { nextBtn.disabled = currentPage === totalPages || total === 0; }
        if (pageNumbers) {
            pageNumbers.innerHTML = '';
            const visiblePages = [];
            const pushPage = function(page){
                if (page >= 1 && page <= totalPages && visiblePages.indexOf(page) === -1) {
                    visiblePages.push(page);
                }
            };
            pushPage(1);
            for (let page = currentPage - 1; page <= currentPage + 1; page += 1) {
                pushPage(page);
            }
            pushPage(totalPages);
            visiblePages.sort(function(a, b){ return a - b; });

            let previousVisible = 0;
            visiblePages.forEach(function(page){
                if (previousVisible && page - previousVisible > 1) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'page-ellipsis';
                    ellipsis.textContent = '...';
                    pageNumbers.appendChild(ellipsis);
                }
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'page-btn' + (page === currentPage ? ' is-active' : '');
                button.textContent = String(page);
                button.addEventListener('click', function(){
                    currentPage = page;
                    renderRows();
                });
                pageNumbers.appendChild(button);
                previousVisible = page;
            });
        }
    };

    moduleButtons.forEach(function(btn){
        btn.addEventListener('click', function(){
            activeModule = btn.getAttribute('data-module');
            activeFilter = 'all';
            moduleButtons.forEach(function(item){ item.classList.toggle('active', item === btn); });
            renderSubfilters();
            currentPage = 1;
            renderRows();
        });
    });

    if (filterToggleBtn) {
        filterToggleBtn.addEventListener('click', function(){
            setFilterOpen(!filterPanel.classList.contains('is-open'));
        });
    }
    if (filterCloseBtn) {
        filterCloseBtn.addEventListener('click', function(){ setFilterOpen(false); });
    }
    if (filterBackdrop) {
        filterBackdrop.addEventListener('click', function(){ setFilterOpen(false); });
    }
    document.addEventListener('keydown', function(event){
        if (event.key === 'Escape') {
            setFilterOpen(false);
        }
    });
    if (search) { search.addEventListener('input', function(){ currentPage = 1; renderRows(); }); }
    if (filterApplyBtn) { filterApplyBtn.addEventListener('click', function(){ currentPage = 1; renderRows(); setFilterOpen(false); }); }
    if (filterCompany) { filterCompany.addEventListener('change', function(){ currentPage = 1; renderRows(); }); }
    if (filterStatus) { filterStatus.addEventListener('change', function(){ currentPage = 1; renderRows(); }); }
    if (filterDate) { filterDate.addEventListener('change', function(){ currentPage = 1; renderRows(); }); }
    if (filterSearch) { filterSearch.addEventListener('input', function(){ currentPage = 1; renderRows(); }); }
    if (prevBtn) { prevBtn.addEventListener('click', function(){ if (currentPage > 1) { currentPage -= 1; renderRows(); } }); }
    if (nextBtn) { nextBtn.addEventListener('click', function(){ if (currentPage < Math.max(1, Math.ceil(getFilteredRows().length / perPage))) { currentPage += 1; renderRows(); } }); }
    if (filterClearBtn) {
        filterClearBtn.addEventListener('click', function(){
            if (filterSearch) { filterSearch.value = ''; }
            if (filterCompany) { filterCompany.value = ''; }
            if (filterStatus) { filterStatus.value = ''; }
            if (filterDate) { filterDate.value = ''; }
            currentPage = 1;
            renderRows();
        });
    }

    renderSubfilters();
    renderRows();
}());
</script>
<?php medis_render_navigation_end(); ?>
</body>
</html>
