<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report Folder</title>
</head>
<body>
<?php
require dirname(__DIR__) . '/panel/navigation.php';
$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$folderModule = strtolower(trim((string) ($folderModule ?? 'surveillance')));
$folderCompany = trim((string) ($folderCompany ?? ''));
$folderDate = trim((string) ($folderDate ?? ''));
$initialTab = strtolower(trim((string) request()->query('tab', 'all')));
if (! in_array($initialTab, ['all', 'usechh 4', 'usechh 5i', 'usechh 5ii'], true)) {
    $initialTab = 'all';
}
$sourceRows = $folderModule === 'audiometry'
    ? (array) ($audiometryReportRows ?? [])
    : (array) ($surveillanceReportRows ?? []);

$formatDate = static function (string $value): string {
    if ($value === '') {
        return 'No examination date';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y', $timestamp) : $value;
};

$folderRows = array_values(array_filter($sourceRows, static function (array $row) use ($folderCompany, $folderDate): bool {
    return trim((string) ($row['company'] ?? '')) === $folderCompany
        && trim((string) ($row['date_examined'] ?? '')) === $folderDate;
}));
$usechh5iCandidates = array_values(array_filter($folderRows, static function (array $row): bool {
    return strtolower(trim((string) ($row['filter'] ?? ''))) === 'usechh 5i';
}));

$emailStatuses = is_array($reportEmailStatuses ?? null) ? $reportEmailStatuses : [];
$reportEmailKey = static function (string $reportKey, array $row): string {
    return implode('|', [
        'surveillance',
        $reportKey,
        (string) ($row['declaration_id'] ?? ''),
        (string) ($row['employee_id'] ?? ''),
        (string) ($row['company_id'] ?? ''),
        (string) ($row['surveillance_id'] ?? ''),
    ]);
};

usort($folderRows, static function (array $left, array $right): int {
    $leftFilter = strtolower(trim((string) ($left['filter'] ?? '')));
    $rightFilter = strtolower(trim((string) ($right['filter'] ?? '')));
    if ($leftFilter !== $rightFilter) {
        return $leftFilter <=> $rightFilter;
    }

    $leftName = strtolower(trim((string) ($left['employee_name'] ?? '')));
    $rightName = strtolower(trim((string) ($right['employee_name'] ?? '')));
    if ($leftName !== $rightName) {
        return $leftName <=> $rightName;
    }

    return strtolower(trim((string) ($left['chemical_name'] ?? '')))
        <=> strtolower(trim((string) ($right['chemical_name'] ?? '')));
});

$allRows = [];
$allSeen = [];
foreach ($folderRows as $row) {
    $allKey = implode('|', [
        (string) ($row['declaration_id'] ?? ''),
        (string) ($row['employee_id'] ?? ''),
        (string) ($row['company_id'] ?? ''),
        (string) ($row['surveillance_id'] ?? ''),
    ]);
    if (isset($allSeen[$allKey])) {
        continue;
    }
    $allSeen[$allKey] = true;
    $routeParams = array_filter([
        'declaration_id' => $row['declaration_id'] ?? null,
        'employee_id' => $row['employee_id'] ?? null,
        'company_id' => $row['company_id'] ?? null,
        'surveillance_id' => $row['surveillance_id'] ?? null,
    ], static fn ($value) => (int) $value > 0);
    $row['tab_key'] = 'all';
    $row['combined_pdf_href'] = function_exists('route') ? route('pdf.usechh-all', $routeParams) : '#';
    $allRows[] = $row;
}

$tabbedRows = array_merge(
    $allRows,
    array_values(array_map(static function (array $row): array {
        $row['tab_key'] = strtolower(trim((string) ($row['filter'] ?? '')));
        return $row;
    }, array_values(array_filter($folderRows, static function (array $row): bool {
        $filter = strtolower(trim((string) ($row['filter'] ?? '')));
        if (! in_array($filter, ['usechh 4', 'usechh 5i', 'usechh 5ii'], true)) {
            return false;
        }

        if ($filter === 'usechh 5i') {
            return ! empty($row['has_saved_removal']);
        }

        return true;
    }))))
);

medis_render_navigation_start([
    'clinicName' => $clinicName ?? 'Medis SHAMS',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'User',
    'active' => 'report',
    'pageTitle' => 'Report Folder',
    'pageSubtitle' => 'Grouped records by company and examination date',
]);
?>
<style>
.flow{height:calc(100dvh - 204px);min-height:calc(100dvh - 204px);display:flex}
.content{padding:4px 6px;height:100%;width:100%;margin-top:0;border:0;background:transparent;border-radius:0;display:flex;flex-direction:column;overflow:hidden}
.report-shell{display:grid;gap:10px;height:100%;min-height:0}
.report-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap}
.report-head h2{margin:0;font-size:1.9rem}
.report-head p{margin:8px 0 0;color:#6b7280}
.head-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.head-btn{display:inline-flex;align-items:center;justify-content:center;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#374151;padding:8px 14px;text-decoration:none;font:inherit;cursor:pointer}
.manage-card{border:0;border-radius:0;background:#fff;overflow:hidden;display:flex;flex-direction:column;min-height:0;flex:1}
.subfilter-bar{display:flex;gap:18px;align-items:center;padding:0 18px;border-bottom:1px solid #edf0f2;flex-wrap:wrap}
.subfilter-btn{appearance:none;border:0;background:transparent;padding:12px 0 11px;font:inherit;font-weight:600;color:#4b5563;cursor:pointer;position:relative;text-transform:uppercase;font-size:.82rem}
.subfilter-btn.active{color:#166534}
.subfilter-btn.active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:#389B5B;border-radius:999px}
.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid #edf0f2;flex-wrap:wrap}
.toolbar-left,.toolbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.select-all{display:inline-flex;align-items:center;justify-content:center}
.select-all input,.row-select{appearance:none;-webkit-appearance:none;width:18px;height:18px;margin:0;border:1px solid #8b95a7;border-radius:4px;background:#fff;display:inline-grid;place-items:center;cursor:pointer;transition:border-color .16s ease,background-color .16s ease}
.select-all input::after,.row-select::after{content:"";width:9px;height:5px;border:2px solid #fff;border-top:0;border-right:0;transform:rotate(-45deg) scale(0);transition:transform .16s ease;margin-top:-2px}
.select-all input:hover,.row-select:hover{border-color:#6b7280}
.select-all input:checked,.row-select:checked{background:#389B5B;border-color:#389B5B}
.select-all input:checked::after,.row-select:checked::after{transform:rotate(-45deg) scale(1)}
.select-all input:indeterminate{background:#389B5B;border-color:#389B5B}
.select-all input:indeterminate::after{width:10px;height:2px;border:0;background:#fff;transform:none;margin-top:0}
.search{width:min(320px,100%);border:1px solid #d1d5db;border-radius:10px;padding:9px 12px;font:inherit}
.toolbar-action{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border:1px solid #d6e4d9;border-radius:10px;background:#fff;color:#166534;cursor:pointer;text-decoration:none}
.toolbar-action svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:1.8}
.toolbar-action.email-pending{color:#b08968}
.toolbar-action.email-sent{color:#166534}
.toolbar-action.is-disabled{color:#9ca3af;border-color:#e5e7eb;cursor:not-allowed}
.toolbar-generate{display:inline-flex;align-items:center;justify-content:center;height:36px;border:1px solid #d6e4d9;border-radius:10px;background:#fff;color:#166534;cursor:pointer;text-decoration:none;padding:0 14px;font-weight:600;font-size:.82rem;white-space:nowrap}
.toolbar-generate.is-disabled{color:#9ca3af;border-color:#e5e7eb;cursor:not-allowed}
.toolbar-generate.is-hidden{display:none}
.table-shell{flex:1;min-height:0;overflow:auto}
.report-table{width:100%;border-collapse:collapse}
.report-table th,.report-table td{padding:12px 18px;text-align:left;vertical-align:middle}
.report-table thead th{font-size:.78rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;background:#fafafa;border-top:1px solid #edf0f2;border-bottom:1px solid #edf0f2}
.report-table tbody td{border-top:0}
.patient-head{display:flex;align-items:center;gap:14px}
.patient-cell{display:flex;align-items:flex-start;gap:14px}
.patient-cell .row-select{flex:0 0 auto;margin-top:4px}
.patient-cell-text{min-width:0}
.patient-link{color:#0f172a;text-decoration:none;font-weight:500}
.patient-link:hover{color:#166534;text-decoration:underline}
.action-icons{display:flex;align-items:center;gap:10px}
.action-icon{display:inline-flex;align-items:center;justify-content:center;color:#374151;text-decoration:none}
.action-icon svg{width:17px;height:17px;stroke:currentColor;fill:none;stroke-width:1.8}
.empty-row td{text-align:center;color:#6b7280}
.filler-row td{height:42px;color:transparent;user-select:none}
.table-foot{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:8px 18px;border-top:1px solid #edf0f2;flex-wrap:wrap;background:#fff}
.pager{color:#6b7280;font-size:.78rem}
.pager-group{display:flex;align-items:center;gap:5px;flex-wrap:wrap}
.page-btn{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;text-decoration:none;border:1px solid #d6e4d9;border-radius:10px;padding:0 10px;background:#f7fbf8;color:#2f4b36;font-weight:600;font-size:.82rem;cursor:pointer}
.page-btn[disabled]{opacity:.45;cursor:not-allowed}
.page-btn.is-active{background:#389B5B;border-color:#389B5B;color:#fff}
.page-numbers{display:flex;gap:6px;flex-wrap:wrap}
@media(max-width:980px){.flow{height:auto;min-height:auto}.content{height:auto;min-height:auto}.toolbar-left,.toolbar-right{width:100%}.search{width:100%}}
</style>
<div class="flow"><div class="content"><div class="report-shell">
    <section class="report-head">
        <div>
            <h2>Report Folder</h2>
            <p><?php echo $esc(($folderCompany !== '' ? $folderCompany : 'No company') . ' • ' . $formatDate($folderDate)); ?></p>
        </div>
        <div class="head-actions">
            <a class="head-btn" href="<?php echo $esc(function_exists('route') ? route('general.report') : '#'); ?>">Back</a>
        </div>
    </section>

    <section class="manage-card">
        <div class="subfilter-bar" id="folderTabs">
            <button class="subfilter-btn<?php echo $initialTab === 'all' ? ' active' : ''; ?>" type="button" data-tab="all">All</button>
            <button class="subfilter-btn<?php echo $initialTab === 'usechh 4' ? ' active' : ''; ?>" type="button" data-tab="usechh 4">USECHH 4</button>
            <button class="subfilter-btn<?php echo $initialTab === 'usechh 5i' ? ' active' : ''; ?>" type="button" data-tab="usechh 5i">USECHH 5I</button>
            <button class="subfilter-btn<?php echo $initialTab === 'usechh 5ii' ? ' active' : ''; ?>" type="button" data-tab="usechh 5ii">USECHH 5II</button>
        </div>

        <div class="toolbar">
            <div class="toolbar-left">
                <input class="search" id="folderSearch" type="text" placeholder="Search patient name">
            </div>
            <div class="toolbar-right">
                <button class="toolbar-action" id="folderPrintBtn" type="button" title="Print selected">
                    <svg viewBox="0 0 24 24"><path d="M7 9V4h10v5"></path><path d="M7 18h10v2H7z"></path><path d="M6 18H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-1"></path></svg>
                </button>
                <form id="folderEmailForm" method="post" action="<?php echo $esc(function_exists('route') ? route('report.email.send') : '#'); ?>" style="margin:0;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="module" value="surveillance">
                    <div id="folderEmailSelections"></div>
                    <button class="toolbar-action email-pending" id="folderEmailBtn" type="submit" title="Send selected by email">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.75A1.75 1.75 0 0 1 4.75 5h14.5A1.75 1.75 0 0 1 21 6.75v10.5A1.75 1.75 0 0 1 19.25 19H4.75A1.75 1.75 0 0 1 3 17.25V6.75Z"></path><path d="m4 7 8 6 8-6"></path></svg>
                    </button>
                </form>
                <button class="toolbar-generate is-hidden is-disabled" id="folderGenerateUsechh5iBtn" type="button" title="Open the USECHH 5i form creator">
                    Create USECHH 5I Report
                </button>
            </div>
        </div>

        <div class="table-shell">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>
                            <div class="patient-head">
                                <label class="select-all" title="Select all">
                                    <input id="folderSelectAll" type="checkbox" aria-label="Select all">
                                </label>
                                <span>Patient Name</span>
                            </div>
                        </th>
                        <th>Chemical Name</th>
                        <th>Date Examined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="folderTableBody">
                    <?php foreach ($tabbedRows as $row): ?>
                        <?php
                        $rowReportKey = (string) (($row['tab_key'] ?? '') === 'all' ? 'all' : strtolower(trim((string) ($row['filter'] ?? ''))));
                        $hasRecipientEmail = trim((string) ($row['employee_email'] ?? '')) !== '';
                        $isEmailAllowed = ($row['tab_key'] ?? '') === 'all';
                        $wasEmailed = $isEmailAllowed && isset($emailStatuses[$reportEmailKey($rowReportKey, $row)]);
                        ?>
                        <tr
                            data-tab="<?php echo $esc($row['tab_key'] ?? 'all'); ?>"
                            data-name="<?php echo $esc(strtolower((string) ($row['employee_name'] ?? ''))); ?>"
                            data-date-examined="<?php echo $esc((string) ($row['date_examined'] ?? '')); ?>"
                            data-print-href="<?php echo $esc(($row['tab_key'] ?? '') === 'all' ? ($row['combined_pdf_href'] ?? '#') : ($row['pdf_href'] ?? '#')); ?>"
                            data-edit-href="<?php echo $esc(($row['tab_key'] ?? '') === 'all' ? '' : ($row['href'] ?? '#')); ?>"
                            data-view-href="<?php echo $esc(($row['tab_key'] ?? '') === 'usechh 5i' ? (($row['href'] ?? '#') . (str_contains((string) ($row['href'] ?? ''), '?') ? '&' : '?') . 'view=1') : ''); ?>"
                            data-report-key="<?php echo $esc($rowReportKey); ?>"
                            data-declaration-id="<?php echo $esc((string) ($row['declaration_id'] ?? '')); ?>"
                            data-employee-id="<?php echo $esc((string) ($row['employee_id'] ?? '')); ?>"
                            data-company-id="<?php echo $esc((string) ($row['company_id'] ?? '')); ?>"
                            data-surveillance-id="<?php echo $esc((string) ($row['surveillance_id'] ?? '')); ?>"
                            data-has-email="<?php echo $hasRecipientEmail ? '1' : '0'; ?>"
                            data-email-allowed="<?php echo $isEmailAllowed ? '1' : '0'; ?>"
                            data-was-emailed="<?php echo $wasEmailed ? '1' : '0'; ?>"
                        >
                            <td>
                                <div class="patient-cell">
                                    <input class="row-select" type="checkbox" data-row-select="1">
                                    <div class="patient-cell-text">
                                        <?php if (($row['tab_key'] ?? '') === 'all' || ($row['tab_key'] ?? '') === 'usechh 5i'): ?>
                                            <?php echo $esc($row['employee_name'] ?? ''); ?>
                                        <?php else: ?>
                                            <a class="patient-link" href="<?php echo $esc($row['href'] ?? '#'); ?>">
                                                <?php echo $esc($row['employee_name'] ?? ''); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $esc($row['chemical_name'] ?? ''); ?></td>
                            <td><?php echo $esc($formatDate((string) ($row['date_examined'] ?? ''))); ?></td>
                            <td>
                                <?php if (($row['tab_key'] ?? '') === 'usechh 5i'): ?>
                                    <div class="action-icons">
                                        <a class="action-icon" href="<?php echo $esc(($row['href'] ?? '#') . (str_contains((string) ($row['href'] ?? ''), '?') ? '&' : '?') . 'view=1'); ?>" title="View">
                                            <svg viewBox="0 0 24 24"><path d="M1.5 12s3.8-6.5 10.5-6.5S22.5 12 22.5 12 18.7 18.5 12 18.5 1.5 12 1.5 12Z"></path><circle cx="12" cy="12" r="3.25"></circle></svg>
                                        </a>
                                        <a class="action-icon" href="<?php echo $esc($row['href'] ?? '#'); ?>" title="Edit">
                                            <svg viewBox="0 0 24 24"><path d="M4 20h4l10-10-4-4L4 16v4Z"></path><path d="m12 6 4 4"></path></svg>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="empty-row" id="folderEmptyRow" style="display:none;">
                        <td colspan="4">No report records match the selected tab or search.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-foot">
            <span class="pager" id="folderPager">Showing 0 records</span>
            <div class="pager-group">
                <button class="page-btn" id="folderPrevBtn" type="button">Previous</button>
                <div class="page-numbers" id="folderPageNumbers"></div>
                <button class="page-btn" id="folderNextBtn" type="button">Next</button>
            </div>
        </div>
    </section>
</div></div></div>
<script>
(function(){
    const tabs = Array.prototype.slice.call(document.querySelectorAll('#folderTabs .subfilter-btn'));
    const search = document.getElementById('folderSearch');
    const rows = Array.prototype.slice.call(document.querySelectorAll('#folderTableBody tr[data-tab]'));
    const emptyRow = document.getElementById('folderEmptyRow');
    const pager = document.getElementById('folderPager');
    const prevBtn = document.getElementById('folderPrevBtn');
    const nextBtn = document.getElementById('folderNextBtn');
    const pageNumbers = document.getElementById('folderPageNumbers');
    const selectAll = document.getElementById('folderSelectAll');
    const printBtn = document.getElementById('folderPrintBtn');
    const emailBtn = document.getElementById('folderEmailBtn');
    const emailForm = document.getElementById('folderEmailForm');
    const emailSelections = document.getElementById('folderEmailSelections');
    const generateUsechh5iBtn = document.getElementById('folderGenerateUsechh5iBtn');
    const usechh5iCandidates = <?php echo json_encode(array_values(array_map(static function (array $row): array {
        return [
            'company_id' => (string) ($row['company_id'] ?? ''),
            'date_examined' => (string) ($row['date_examined'] ?? ''),
        ];
    }, $usechh5iCandidates)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const removalReportBaseHref = <?php echo json_encode(function_exists('route') ? route('surveillance.report.removal') : '#', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const fillerClass = 'filler-row';
    const perPage = 5;
    let activeTab = <?php echo json_encode($initialTab, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    let currentPage = 1;

    const clearFillers = function(){
        Array.prototype.slice.call(document.querySelectorAll('#folderTableBody .' + fillerClass)).forEach(function(row){
            row.remove();
        });
    };

    const appendFillers = function(count){
        for (let i = 0; i < count; i += 1) {
            const filler = document.createElement('tr');
            filler.className = fillerClass;
            for (let col = 0; col < 4; col += 1) {
                const cell = document.createElement('td');
                cell.innerHTML = '&nbsp;';
                filler.appendChild(cell);
            }
            document.getElementById('folderTableBody').appendChild(filler);
        }
    };

    const getFilteredRows = function(){
        const query = (search.value || '').trim().toLowerCase();
        return rows.filter(function(row){
            const rowTab = row.getAttribute('data-tab') || '';
            const rowName = row.getAttribute('data-name') || '';
            return rowTab === activeTab && (query === '' || rowName.indexOf(query) !== -1);
        });
    };

    const getVisibleRows = function(){
        return rows.filter(function(row){
            return row.style.display !== 'none';
        });
    };

    const getSelectedRows = function(){
        return rows.filter(function(row){
            const checkbox = row.querySelector('[data-row-select]');
            return checkbox && checkbox.checked;
        });
    };

    const syncToolbarActions = function(){
        const selectedRows = getSelectedRows();
        const hasSelectedRows = selectedRows.length > 0;
        const emailEligibleRows = selectedRows.filter(function(row){
            return row.getAttribute('data-email-allowed') === '1' && row.getAttribute('data-has-email') === '1';
        });
        const allSelectedWereEmailed = emailEligibleRows.length > 0 && emailEligibleRows.every(function(row){
            return row.getAttribute('data-was-emailed') === '1';
        });

        if (printBtn) {
            printBtn.disabled = !hasSelectedRows;
            printBtn.classList.toggle('is-disabled', !hasSelectedRows);
            printBtn.title = hasSelectedRows ? 'Print selected' : 'Select at least one record to print';
        }

        if (emailBtn) {
            const emailDisabled = activeTab !== 'all' || emailEligibleRows.length === 0;
            emailBtn.disabled = emailDisabled;
            emailBtn.classList.toggle('is-disabled', emailDisabled);
            emailBtn.classList.toggle('email-sent', !emailDisabled && allSelectedWereEmailed);
            emailBtn.classList.toggle('email-pending', !emailDisabled && !allSelectedWereEmailed);
            emailBtn.title = activeTab !== 'all'
                ? 'Email is only available for the ALL combined PDF'
                : (emailEligibleRows.length === 0
                    ? 'Select at least one patient with an email address'
                    : 'Send selected by email');
        }

        if (generateUsechh5iBtn) {
            generateUsechh5iBtn.classList.toggle('is-hidden', activeTab !== 'usechh 5i');
            const generateDisabled = activeTab !== 'usechh 5i' || usechh5iCandidates.length === 0;
            generateUsechh5iBtn.disabled = generateDisabled;
            generateUsechh5iBtn.classList.toggle('is-disabled', generateDisabled);
            generateUsechh5iBtn.title = activeTab !== 'usechh 5i'
                ? 'Create USECHH 5i report is only available in the USECHH 5i tab'
                : (usechh5iCandidates.length === 0
                    ? 'No USECHH 5i patient records are available for this folder'
                    : 'Open the USECHH 5i form creator');
        }
    };

    const syncSelectAll = function(){
        if (!selectAll) {
            return;
        }
        const visibleRows = getVisibleRows();
        const checkboxes = visibleRows.map(function(row){
            return row.querySelector('[data-row-select]');
        }).filter(Boolean);
        if (!checkboxes.length) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
            syncToolbarActions();
            return;
        }
        const checkedCount = checkboxes.filter(function(box){ return box.checked; }).length;
        selectAll.checked = checkedCount > 0 && checkedCount === checkboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
        syncToolbarActions();
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

        filteredRows.slice(start, end).forEach(function(row){
            row.style.display = '';
            document.getElementById('folderTableBody').appendChild(row);
        });

        if (total > 0 && (end - start) < perPage) {
            appendFillers(perPage - (end - start));
        }

        if (emptyRow) {
            emptyRow.style.display = total === 0 ? '' : 'none';
        }
        pager.textContent = total === 0 ? 'Showing 0 records' : 'Showing ' + (start + 1) + '-' + end + ' of ' + total + ' records';
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
        syncSelectAll();
    };

    tabs.forEach(function(tab){
        tab.addEventListener('click', function(){
            activeTab = tab.getAttribute('data-tab') || 'all';
            currentPage = 1;
            tabs.forEach(function(item){ item.classList.toggle('active', item === tab); });
            renderRows();
        });
    });

    if (search) {
        search.addEventListener('input', function(){
            currentPage = 1;
            renderRows();
        });
    }

    rows.forEach(function(row){
        const checkbox = row.querySelector('[data-row-select]');
        if (checkbox) {
            checkbox.addEventListener('change', syncSelectAll);
        }
    });

    if (selectAll) {
        selectAll.addEventListener('change', function(){
            getVisibleRows().forEach(function(row){
                const checkbox = row.querySelector('[data-row-select]');
                if (checkbox) {
                    checkbox.checked = selectAll.checked;
                }
            });
            syncSelectAll();
        });
    }

    if (printBtn) {
        printBtn.addEventListener('click', function(){
            const selectedRows = getSelectedRows();
            if (!selectedRows.length) {
                return;
            }
            selectedRows.forEach(function(row){
                const href = row.getAttribute('data-print-href') || '';
                if (href && href !== '#') {
                    window.open(href, '_blank', 'noopener');
                }
            });
        });
    }

    if (emailForm) {
        emailForm.addEventListener('submit', function(event){
            const selectedRows = getSelectedRows().filter(function(row){
                return row.getAttribute('data-email-allowed') === '1' && row.getAttribute('data-has-email') === '1';
            });
            if (activeTab !== 'all' || !selectedRows.length) {
                event.preventDefault();
                syncToolbarActions();
                return;
            }
            emailSelections.innerHTML = '';
            selectedRows.forEach(function(row, index){
                ['report-key', 'declaration-id', 'employee-id', 'company-id', 'surveillance-id'].forEach(function(key){
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_reports[' + index + '][' + key.replace(/-/g, '_') + ']';
                    input.value = row.getAttribute('data-' + key) || '';
                    emailSelections.appendChild(input);
                });
            });
        });
    }

    if (generateUsechh5iBtn) {
        generateUsechh5iBtn.addEventListener('click', function(){
            if (activeTab !== 'usechh 5i' || !usechh5iCandidates.length || !removalReportBaseHref || removalReportBaseHref === '#') {
                syncToolbarActions();
                return;
            }
            const firstCandidate = usechh5iCandidates[0];
            const companyId = firstCandidate.company_id || '';
            const dateExamined = firstCandidate.date_examined || '';
            const href = removalReportBaseHref + '?'
                + 'create_mode=1'
                + '&company_id=' + encodeURIComponent(companyId)
                + '&folder_date=' + encodeURIComponent(dateExamined);
            window.open(href, '_blank', 'noopener');
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function(){
            if (currentPage > 1) {
                currentPage -= 1;
                renderRows();
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function(){
            const totalPages = Math.max(1, Math.ceil(getFilteredRows().length / perPage));
            if (currentPage < totalPages) {
                currentPage += 1;
                renderRows();
            }
        });
    }

    renderRows();
}());
</script>
<?php medis_render_navigation_end(); ?>
</body>
</html>
