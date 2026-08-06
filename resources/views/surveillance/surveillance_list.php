<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Surveillance - List</title></head>
<body>
<?php
require dirname(__DIR__) . '/panel/navigation.php';
$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$selectedCompanyId = $selectedCompany->company_id ?? request()->query('company_id') ?? '';
$selectedEmployeeId = $selectedEmployee->employee_id ?? request()->query('employee_id') ?? '';
$backUrl = function_exists('route') ? route('surveillance.patient', ['company_id' => $selectedCompanyId]) : '#';
$addRecordUrl = function_exists('route') ? route('surveillance.declaration', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId]) : '#';
$nextUrl = function_exists('route') ? route('surveillance.declaration', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId]) : '#';
$steps = [
    ['label' => 'Company', 'url' => function_exists('route') ? route('surveillance.company') : '#'],
    ['label' => 'Patient', 'url' => function_exists('route') ? route('surveillance.patient', ['company_id' => $selectedCompanyId]) : '#'],
    ['label' => 'Surveillance List', 'url' => function_exists('route') ? route('surveillance.list', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId]) : '#', 'active' => true],
    ['label' => 'Declaration', 'url' => function_exists('route') ? route('surveillance.declaration', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId]) : '#'],
    ['label' => 'Examination', 'url' => function_exists('route') ? route('surveillance.examination', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId]) : '#'],
    ['label' => 'Report', 'url' => function_exists('route') ? route('surveillance.report', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId]) : '#'],
];
$records = isset($records) ? $records : collect();
$totalRecords = isset($recordTotal) ? (int) $recordTotal : (is_countable($records) ? count($records) : 0);
medis_render_navigation_start(['clinicName'=>$clinicName ?? 'Medis SHAMS','clinicLogoUrl'=>$clinicLogoUrl ?? null,'username'=>$username ?? 'User','active'=>'surveillance','showSurveillanceSubnav' => true,'surveillanceSubActive' => 'list','pageSubtitle'=>'Review and continue surveillance records']);
?>
<style>.flow{height:calc(100dvh - 204px);min-height:calc(100dvh - 204px);display:flex}.content{padding:4px 6px;height:100%;width:100%;margin-top:0;border:0;background:transparent;border-radius:0;display:flex;flex-direction:column;overflow:hidden}.head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.head h2{margin:0 0 12px;font-size:1.8rem}.top-actions{display:flex;gap:10px;flex-wrap:wrap}.btn,.next,.filter-btn,.page-btn{display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;background:#fff;color:#374151}.next{background:#389B5B;border-color:#389B5B;color:#fff}.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px}.toolbar-left,.toolbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.toolbar input{border:1px solid #d1d5db;border-radius:12px;padding:10px 12px;min-width:280px}.filter-btn{font-size:.9rem;cursor:pointer}.filter-btn.is-active{background:#389B5B;border-color:#389B5B;color:#fff}.table-wrap{margin-top:14px;flex:1;min-height:0;display:flex;align-items:flex-start;overflow:visible}.table{width:100%;border-collapse:collapse}.table th,.table td{padding:14px 10px;text-align:left;border-top:0}.table thead tr{border-top:1px solid #edf0f2;border-bottom:1px solid #edf0f2}.table th{font-size:.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;background:#fff}.empty{padding:22px 10px;color:#6b7280;text-align:center}.action-icons{display:flex;gap:10px}.icon-btn svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.8}.icon-btn{color:#111827}.icon-btn.delete{color:#ef4444}.icon-btn.is-disabled{color:#9ca3af;border-color:#e5e7eb;opacity:.55;pointer-events:none;cursor:not-allowed}.tag{display:inline-flex;padding:5px 10px;border-radius:999px;font-weight:600;font-size:.76rem}.ok{background:#dcfce7;color:#166534}.warn{background:#fef3c7;color:#92400e}.bottom{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:auto;padding-top:18px}.pager{color:#6b7280;font-size:.84rem}.pager-group{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.page-btn{align-items:center;justify-content:center;min-width:44px;height:44px;cursor:pointer;padding:0 14px;border-color:#d6e4d9;border-radius:14px;background:#f7fbf8;color:#2f4b36;font-weight:600;box-shadow:0 10px 24px rgba(56,155,91,.08)}.page-btn[disabled]{opacity:.45;cursor:not-allowed}.page-btn.is-active{background:#389B5B;border-color:#389B5B;color:#fff;box-shadow:0 14px 28px rgba(56,155,91,.22)}.page-numbers{display:flex;gap:10px;flex-wrap:wrap}@media(max-width:1180px){.flow{height:auto;min-height:auto}.content{height:auto;min-height:auto}}@media(max-width:760px){.content{padding:0}.toolbar input{min-width:100%}.bottom{align-items:flex-start;flex-direction:column}}</style><div class="flow"><section class="content"><div class="head"><div><h2>Surveillance List</h2></div><div class="top-actions"><a class="next" href="<?php echo $esc($addRecordUrl); ?>">+ Add Record</a></div></div><div class="toolbar"><div class="toolbar-left"><input id="surveillanceSearch" type="text" placeholder="Search record"></div><div class="toolbar-right"><button type="button" class="filter-btn" data-status-filter="incomplete">Incomplete</button><button type="button" class="filter-btn" data-status-filter="completed">Completed</button></div></div><div class="table-wrap"><table class="table"><thead><tr><th>Patient Name</th><th>NRIC/Passport No</th><th>Telephone No</th><th>Examination Date</th><th>Status</th><th>Action</th></tr></thead><tbody id="surveillanceTableBody"><?php if($totalRecords > 0): ?><?php foreach($records as $record): ?><?php $isCompleted = !empty($record->is_completed); $status = $isCompleted ? 'completed' : 'incomplete'; $employeeName = trim(((string) ($record->employee_firstName ?? '')) . ' ' . ((string) ($record->employee_lastName ?? ''))); $identityNo = trim((string) (($record->employee_NRIC ?? '') !== '' ? ($record->employee_NRIC ?? '') : ($record->employee_passportNo ?? ''))); $telephoneNo = trim((string) ($record->employee_telephone ?? '')); $examDate = trim((string) ($record->employee_date ?: $record->doctor_date ?: '')); ?><tr data-record-row="1" data-status="<?php echo $esc($status); ?>"><td><?php echo $esc($employeeName !== '' ? $employeeName : 'Not set'); ?></td><td><?php echo $esc($identityNo !== '' ? $identityNo : '-'); ?></td><td><?php echo $esc($telephoneNo !== '' ? $telephoneNo : '-'); ?></td><td><?php echo $esc($examDate !== '' ? $examDate : '-'); ?></td><td><span class="tag <?php echo $status === 'completed' ? 'ok' : 'warn'; ?>"><?php echo $status === 'completed' ? 'Completed' : 'Incomplete'; ?></span></td><td><div class="action-icons"><a class="icon-btn" href="<?php echo $esc(route('surveillance.record.view', ['declaration' => $record->declaration_id])); ?>" title="View"><svg viewBox="0 0 24 24"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"></path><circle cx="12" cy="12" r="3"></circle></svg></a><?php if ($isCompleted): ?><span class="icon-btn is-disabled" title="Completed records cannot be edited" aria-disabled="true"><svg viewBox="0 0 24 24"><path d="M4 20h4l10-10-4-4L4 16v4z"></path><path d="M13 7l4 4"></path></svg></span><?php else: ?><a class="icon-btn" href="<?php echo $esc(route('surveillance.record.edit', ['declaration' => $record->declaration_id])); ?>" title="Edit"><svg viewBox="0 0 24 24"><path d="M4 20h4l10-10-4-4L4 16v4z"></path><path d="M13 7l4 4"></path></svg></a><?php endif; ?><a class="icon-btn delete" href="<?php echo $esc(route('surveillance.record.delete', ['declaration' => $record->declaration_id])); ?>" title="Delete"><svg viewBox="0 0 24 24"><path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 13h10l1-13"></path><path d="M9 7V4h6v3"></path></svg></a></div></td></tr><?php endforeach; ?><?php else: ?><tr id="surveillanceEmptyRow"><td class="empty" colspan="6">No surveillance records found in the current database.</td></tr><?php endif; ?></tbody></table></div><div class="bottom"><span class="pager" id="surveillancePager"><?php echo $totalRecords > 0 ? 'Showing 1-' . number_format($totalRecords) . ' of ' . number_format($totalRecords) . ' records' : 'Showing 0 of 0 records'; ?></span><div class="pager-group"><?php if($totalRecords > 0): ?><button class="page-btn" id="surveillancePrevBtn" type="button">Previous</button><div class="page-numbers" id="surveillancePageNumbers"></div><button class="page-btn" id="surveillanceNextBtn" type="button">Next</button><?php endif; ?><a class="btn" href="<?php echo $esc($backUrl); ?>">Back</a> <a class="next" href="<?php echo $esc($nextUrl); ?>">Next</a></div></div></section></div>
<script>
(function () {
    var searchInput = document.getElementById('surveillanceSearch');
    var section = searchInput ? searchInput.closest('.content') : null;
    if (!section) return;
    var rows = Array.prototype.slice.call(section.querySelectorAll('.table tbody tr[data-status]'));
    var pager = document.getElementById('surveillancePager');
    var prevBtn = document.getElementById('surveillancePrevBtn');
    var nextBtn = document.getElementById('surveillanceNextBtn');
    var pageNumbers = document.getElementById('surveillancePageNumbers');
    var emptyRow = document.getElementById('surveillanceEmptyRow');
    var filterButtons = Array.prototype.slice.call(section.querySelectorAll('[data-status-filter]'));
    var activeStatus = '';
    var currentPage = 1;
    var perPage = 5;
    function getFilteredRows() {
        var query = (searchInput.value || '').trim().toLowerCase();
        return rows.filter(function (row) {
            var matchesSearch = query === '' || (row.textContent || '').toLowerCase().indexOf(query) !== -1;
            var matchesStatus = activeStatus === '' || row.getAttribute('data-status') === activeStatus;
            return matchesSearch && matchesStatus;
        });
    }
    function updateRows() {
        var filtered = getFilteredRows();
        var total = filtered.length;
        var totalPages = Math.max(1, Math.ceil(total / perPage));
        if (currentPage > totalPages) { currentPage = totalPages; }
        if (currentPage < 1) { currentPage = 1; }
        rows.forEach(function (row) { row.style.display = 'none'; });
        var start = (currentPage - 1) * perPage;
        var end = Math.min(start + perPage, total);
        filtered.slice(start, end).forEach(function (row) { row.style.display = ''; });
        if (emptyRow) { emptyRow.style.display = total ? 'none' : ''; }
        if (pager) {
            pager.textContent = total === 0 ? 'Showing 0 of 0 records' : 'Showing ' + (start + 1) + '-' + end + ' of ' + total.toLocaleString() + ' records';
        }
        if (prevBtn) { prevBtn.disabled = currentPage === 1 || total === 0; }
        if (nextBtn) { nextBtn.disabled = currentPage === totalPages || total === 0; }
        if (pageNumbers) {
            pageNumbers.innerHTML = '';
            for (var page = 1; page <= totalPages; page += 1) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'page-btn' + (page === currentPage ? ' is-active' : '');
                button.textContent = String(page);
                button.addEventListener('click', (function (targetPage) {
                    return function () {
                        currentPage = targetPage;
                        updateRows();
                    };
                }(page)));
                pageNumbers.appendChild(button);
            }
        }
    }
    filterButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var status = button.getAttribute('data-status-filter') || '';
            activeStatus = activeStatus === status ? '' : status;
            filterButtons.forEach(function (item) { item.classList.toggle('is-active', item === button && activeStatus !== ''); });
            currentPage = 1;
            updateRows();
        });
    });
    searchInput.addEventListener('input', function () {
        currentPage = 1;
        updateRows();
    });
    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage -= 1;
                updateRows();
            }
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            currentPage += 1;
            updateRows();
        });
    }
    updateRows();
})();
</script>
<?php medis_render_navigation_end(); ?>
</body></html>





