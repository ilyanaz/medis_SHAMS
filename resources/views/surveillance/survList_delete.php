<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Surveillance Record</title>
</head>
<body>
<?php
require dirname(__DIR__) . '/panel/navigation.php';
$esc = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$employeeName = trim(((string) ($selectedEmployee->employee_firstName ?? '')) . ' ' . ((string) ($selectedEmployee->employee_lastName ?? '')));
$companyId = (int) ($selectedCompany->company_id ?? $record->company_id ?? 0);
$employeeId = (int) ($selectedEmployee->employee_id ?? $record->employee_id ?? 0);
$declarationId = (int) ($record->declaration_id ?? 0);
$surveillanceId = (int) ($surveillanceId ?? ($record->surveillance_id ?? 0));
$declarationTabUrl = function_exists('route') ? route('surveillance.declaration', array_filter([
    'company_id' => $companyId ?: null,
    'employee_id' => $employeeId ?: null,
    'declaration_id' => $declarationId ?: null,
    'record_mode' => 1,
])) : '#';
$examinationTabUrl = function_exists('route') ? route('surveillance.record.edit', ['declaration' => $declarationId]) : '#';
$deleteAction = function_exists('route') ? route('surveillance.record.destroy', ['declaration' => $declarationId]) : '#';
$backUrl = function_exists('route') ? route('surveillance.list', array_filter(['company_id' => $companyId ?: null, 'employee_id' => $employeeId ?: null])) : '#';
medis_render_navigation_start([
    'clinicName' => $clinicName ?? 'Medis SHAMS',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'User',
    'active' => 'surveillance',
    'showSurveillanceSubnav' => true,
    'surveillanceSubActive' => 'list',
    'pageSubtitle' => 'Review declaration and examination record',
]);
?>
<style>
.flow{min-height:calc(100dvh - 204px)}
.content{padding:4px 6px;overflow:auto;min-height:0;margin-top:0;border:0;background:transparent;border-radius:0;display:flex;flex-direction:column;gap:18px}
.record-tabs{display:flex;gap:18px;align-items:center;padding:0 6px;border-bottom:1px solid #edf0f2;flex-wrap:wrap}
.record-tab{appearance:none;border:0;background:transparent;padding:14px 0 12px;font:inherit;font-weight:600;color:#4b5563;cursor:pointer;position:relative;text-decoration:none}
.record-tab.is-active{color:#166534}
.record-tab.is-active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:#389B5B;border-radius:999px}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:22px;padding:22px;display:grid;gap:18px}
.card h2{margin:0;font-size:1.9rem}
.copy{margin:0;color:#6b7280}
.summary-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.summary-item{border:1px solid #e5e7eb;border-radius:16px;padding:14px;background:#fcfcfd}
.summary-item strong{display:block;margin-bottom:6px;color:#0f172a}
.actions{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.btn,.danger{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;border-radius:12px;padding:10px 14px;font:inherit;cursor:pointer}
.btn{border:1px solid #d1d5db;background:#fff;color:#374151}
.danger{border:1px solid #ef4444;background:#ef4444;color:#fff}
@media(max-width:760px){.summary-grid{grid-template-columns:1fr}.content{padding:0}}
</style>
<div class="flow">
    <section class="content">
        <div class="record-tabs">
            <a class="record-tab" href="<?php echo $esc($declarationTabUrl); ?>">Declaration</a>
            <a class="record-tab" href="<?php echo $esc($examinationTabUrl); ?>">Examination</a>
        </div>

        <section class="card">
            <div>
                <h2>Delete Surveillance Record</h2>
                <p class="copy">This will remove the saved surveillance record for the selected declaration and examination flow.</p>
            </div>

            <div class="summary-grid">
                <div class="summary-item"><strong>Record ID</strong>#SUR<?php echo $esc($declarationId); ?></div>
                <div class="summary-item"><strong>Surveillance ID</strong><?php echo $esc($surveillanceId > 0 ? '#SV' . $surveillanceId : 'NA'); ?></div>
                <div class="summary-item"><strong>Patient</strong><?php echo $esc($employeeName !== '' ? $employeeName : 'NA'); ?></div>
                <div class="summary-item"><strong>Company</strong><?php echo $esc((string) ($selectedCompany->company_name ?? $record->company_name ?? 'NA')); ?></div>
            </div>

            <form method="POST" action="<?php echo $esc($deleteAction); ?>" class="actions">
                <input type="hidden" name="_token" value="<?php echo $esc(csrf_token()); ?>">
                <input type="hidden" name="_method" value="DELETE">
                <a class="btn" href="<?php echo $esc($backUrl); ?>">Cancel</a>
                <button class="danger" type="submit">Delete Record</button>
            </form>
        </section>
    </section>
</div>
<?php medis_render_navigation_end(); ?>
</body>
</html>
