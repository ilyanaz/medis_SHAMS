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
<style>.flow{min-height:calc(100dvh - 204px)}.content{padding:4px 6px;overflow:auto;min-height:clamp(500px,calc(100dvh - 314px),780px);margin-top:0;border:0;background:transparent;border-radius:0;display:flex;flex-direction:column}.head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.head h2{margin:0 0 12px;font-size:1.8rem}.top-actions{display:flex;gap:10px;flex-wrap:wrap}.btn,.next,.filter-btn{display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;background:#fff;color:#374151}.next{background:#389B5B;border-color:#389B5B;color:#fff}.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px}.toolbar-left,.toolbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.toolbar input{border:1px solid #d1d5db;border-radius:12px;padding:10px 12px;min-width:280px}.filter-btn{font-size:.9rem;cursor:pointer}.filter-btn.is-active{background:#389B5B;border-color:#389B5B;color:#fff}.table{width:100%;border-collapse:collapse;margin-top:14px}.table th,.table td{padding:14px 10px;text-align:left;border-top:1px solid #edf0f2}.table th{font-size:.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em}.empty{padding:22px 10px;color:#6b7280;text-align:center}.action-icons{display:flex;gap:10px}.icon-btn svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.8}.icon-btn{color:#111827}.icon-btn.delete{color:#ef4444}.tag{display:inline-flex;padding:5px 10px;border-radius:999px;font-weight:600;font-size:.76rem}.ok{background:#dcfce7;color:#166534}.warn{background:#fef3c7;color:#92400e}.bottom{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:auto;padding-top:18px}.pager{color:#6b7280;font-size:.84rem}@media(max-width:1180px){.flow{min-height:auto}.content{min-height:auto}}@media(max-width:760px){.content{padding:0}.toolbar input{min-width:100%}}</style><div class="flow"><section class="content"><div class="head"><div><h2>Surveillance List</h2></div><div class="top-actions"><a class="next" href="<?php echo $esc($addRecordUrl); ?>">+ Add Record</a></div></div><div class="toolbar"><div class="toolbar-left"><input id="surveillanceSearch" type="text" placeholder="Search record"></div><div class="toolbar-right"><button type="button" class="filter-btn" data-status-filter="incomplete">Incomplete</button><button type="button" class="filter-btn" data-status-filter="completed">Completed</button></div></div><table class="table"><thead><tr><th>Employee Name</th><th>NRIC/Passport No</th><th>Telephone No</th><th>Examination Date</th><th>Status</th><th>Action</th></tr></thead><tbody><?php if($totalRecords > 0): ?><?php foreach($records as $record): ?><?php $isCompleted = !empty($record->is_completed); $status = $isCompleted ? 'completed' : 'incomplete'; $employeeName = trim(((string) ($record->employee_firstName ?? '')) . ' ' . ((string) ($record->employee_lastName ?? ''))); $identityNo = trim((string) (($record->employee_NRIC ?? '') !== '' ? ($record->employee_NRIC ?? '') : ($record->employee_passportNo ?? ''))); $telephoneNo = trim((string) ($record->employee_telephone ?? '')); $examDate = trim((string) ($record->employee_date ?: $record->doctor_date ?: '')); ?><tr data-status="<?php echo $esc($status); ?>"><td><?php echo $esc($employeeName !== '' ? $employeeName : 'Not set'); ?></td><td><?php echo $esc($identityNo !== '' ? $identityNo : '-'); ?></td><td><?php echo $esc($telephoneNo !== '' ? $telephoneNo : '-'); ?></td><td><?php echo $esc($examDate !== '' ? $examDate : '-'); ?></td><td><span class="tag <?php echo $status === 'completed' ? 'ok' : 'warn'; ?>"><?php echo $status === 'completed' ? 'Completed' : 'Incomplete'; ?></span></td><td><div class="action-icons"><a class="icon-btn" href="<?php echo $esc(route('surveillance.record.view', ['declaration' => $record->declaration_id, 'section' => 'chemical'])); ?>" title="View"><svg viewBox="0 0 24 24"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"></path><circle cx="12" cy="12" r="3"></circle></svg></a><a class="icon-btn" href="<?php echo $esc(route('surveillance.record.edit', ['declaration' => $record->declaration_id, 'section' => 'chemical'])); ?>" title="Edit"><svg viewBox="0 0 24 24"><path d="M4 20h4l10-10-4-4L4 16v4z"></path><path d="M13 7l4 4"></path></svg></a><a class="icon-btn delete" href="<?php echo $esc(route('surveillance.record.delete', ['declaration' => $record->declaration_id])); ?>" title="Delete"><svg viewBox="0 0 24 24"><path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 13h10l1-13"></path><path d="M9 7V4h6v3"></path></svg></a></div></td></tr><?php endforeach; ?><?php else: ?><tr><td class="empty" colspan="6">No surveillance records found in the current database.</td></tr><?php endif; ?></tbody></table><div class="bottom"><span class="pager"><?php echo $totalRecords > 0 ? 'Showing 1-' . number_format($totalRecords) . ' of ' . number_format($totalRecords) . ' records' : 'Showing 0 of 0 records'; ?></span><div><a class="btn" href="<?php echo $esc($backUrl); ?>">Back</a> <a class="next" href="<?php echo $esc($nextUrl); ?>">Next</a></div></div></section></div>
<script>
(function () {
    var searchInput = document.getElementById('surveillanceSearch');
    var section = searchInput ? searchInput.closest('.content') : null;
    if (!section) return;
    var rows = Array.prototype.slice.call(section.querySelectorAll('.table tbody tr[data-status]'));
    var pager = section.querySelector('.pager');
    var filterButtons = Array.prototype.slice.call(section.querySelectorAll('[data-status-filter]'));
    var activeStatus = '';
    var totalRows = rows.length;
    function updateRows() {
        var query = (searchInput.value || '').trim().toLowerCase();
        var visibleCount = 0;
        rows.forEach(function (row) {
            var matchesSearch = query === '' || (row.textContent || '').toLowerCase().indexOf(query) !== -1;
            var matchesStatus = activeStatus === '' || row.getAttribute('data-status') === activeStatus;
            var show = matchesSearch && matchesStatus;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount += 1;
        });
        if (pager) {
            pager.textContent = visibleCount === 0 ? 'Showing 0 of ' + totalRows.toLocaleString() + ' records' : 'Showing 1-' + visibleCount.toLocaleString() + ' of ' + totalRows.toLocaleString() + ' records';
        }
    }
    filterButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var status = button.getAttribute('data-status-filter') || '';
            activeStatus = activeStatus === status ? '' : status;
            filterButtons.forEach(function (item) { item.classList.toggle('is-active', item === button && activeStatus !== ''); });
            updateRows();
        });
    });
    searchInput.addEventListener('input', updateRows);
})();
</script>
<?php medis_render_navigation_end(); ?>
</body></html>









