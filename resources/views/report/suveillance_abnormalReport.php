<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;

$esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$query = request();
$declarationId = (int) $query->query('declaration_id', 0);
$employeeId = (int) $query->query('employee_id', 0);
$companyId = (int) $query->query('company_id', 0);
$surveillanceId = (int) $query->query('surveillance_id', 0);

$declaration = $declarationId > 0 && DB::getSchemaBuilder()->hasTable('declaration')
    ? DB::table('declaration')->where('declaration_id', $declarationId)->first()
    : null;
if (! $declaration && DB::getSchemaBuilder()->hasTable('declaration')) {
    $declaration = DB::table('declaration')
        ->when($employeeId > 0, fn ($q) => $q->where('employee_id', $employeeId))
        ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
        ->when($surveillanceId > 0, fn ($q) => $q->where('surveillance_id', $surveillanceId))
        ->orderByDesc('declaration_id')
        ->first();
}
$surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);
$employeeId = (int) ($declaration->employee_id ?? $employeeId);
$companyId = (int) ($declaration->company_id ?? $companyId);

$employee = $employeeId > 0 ? DB::table('employee')->where('employee_id', $employeeId)->first() : null;
$company = $companyId > 0 ? DB::table('company')->where('company_id', $companyId)->first() : null;
$chemical = $surveillanceId > 0 ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first() : null;
$findings = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('ms_findings')
    ? DB::table('ms_findings')->where('surveillance_id', $surveillanceId)->first()
    : null;
$targetOrgan = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('target_organ')
    ? DB::table('target_organ')->where('surveillance_id', $surveillanceId)->first()
    : null;
$biological = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('biological_monitoring')
    ? DB::table('biological_monitoring')->where('surveillance_id', $surveillanceId)->first()
    : null;
$removal = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('removal_report')
    ? DB::table('removal_report')->where('surveillance_id', $surveillanceId)->first()
    : null;

$rows = [[
    'worker' => trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? ''))),
    'chemical' => (string) ($chemical->chemicals ?? '-'),
    'job' => (string) ($chemical->examination_type ?? 'Medical surveillance'),
    'history' => (string) ($findings->history_of_health ?? 'No'),
    'clinical' => (string) ($findings->clinical_findings ?? 'No'),
    'target' => implode(', ', array_filter([
        ! empty($targetOrgan->blood_count) ? 'Blood' : null,
        ! empty($targetOrgan->renal_function) ? 'Renal' : null,
        ! empty($targetOrgan->liver_function) ? 'Liver' : null,
        ! empty($targetOrgan->chest_xray) ? 'Chest' : null,
        ! empty($targetOrgan->spirometry_FEV_FVC) ? 'Spirometry' : null,
    ])),
    'bei' => (string) ($biological->baseline_annual ?? $biological->baseline_results ?? '-'),
    'work' => trim(implode(' / ', array_filter([
        isset($findings->CF_work_related) ? 'CF: ' . $findings->CF_work_related : null,
        isset($findings->TO_work_related) ? 'TO: ' . $findings->TO_work_related : null,
        isset($findings->BM_work_related) ? 'BM: ' . $findings->BM_work_related : null,
    ]))),
    'protection' => (string) ($removal->removal_type ?? 'Monitoring'),
    'department' => (string) ($company->company_name ?? '-'),
]];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>USECHH 5ii Abnormal Examination Results</title>
</head>
<body>
<style>
@page{size:A4 landscape;margin:10mm}
body{margin:0;padding:16px;background:#fff;color:#0f172a;font-family:"Poppins","Segoe UI",Tahoma,Geneva,Verdana,sans-serif}
.sheet{display:grid;gap:16px}
.report-card{border:1px solid #d9e6dd;border-radius:22px;background:#fff;overflow:hidden}
.report-head{padding:18px 22px;border-bottom:1px solid #e5efe7}
.report-head-top{position:relative;display:block;text-align:center}
.report-code{position:absolute;right:0;top:0;font-size:14px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#0f172a}
.report-head-act{font-size:14px;font-weight:700;line-height:1.35}
.report-head-regulation{margin-top:4px;font-size:15px;font-weight:700;line-height:1.35}
.report-title{margin:12px 0 0;text-align:center;font-size:18px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.table-wrap{padding:20px 22px}
.abnormal-table{width:100%;border-collapse:collapse;table-layout:fixed}
.abnormal-table th,.abnormal-table td{border:1px solid #c6ddd0;padding:10px 8px;vertical-align:top;font-size:.88rem;line-height:1.45}
.abnormal-table th{background:#dcefe1;color:#123524;font-weight:700;text-align:left}
.abnormal-table td{word-break:break-word}
.foot-note{padding:0 22px 20px;color:#5f6f65;font-size:.88rem}
@media print{body{padding:0}.report-card{break-inside:avoid}}
</style>
<div class="sheet">
    <?php require __DIR__ . '/partials/clinic_header.php'; ?>
    <section class="report-card">
        <div class="report-head">
            <div class="report-head-top">
                <div class="report-code">USECHH 5ii</div>
                <div class="report-head-act">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="report-head-regulation">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                <h1 class="report-title">Workers With Abnormal Examination Results</h1>
            </div>
        </div>

        <div class="table-wrap">
            <table class="abnormal-table">
                <thead>
                    <tr>
                        <th>Name of Worker</th>
                        <th>Name of Chemical</th>
                        <th>Job / Type of Assessment</th>
                        <th>History of Health Effects</th>
                        <th>Clinical Findings</th>
                        <th>Target Organ Function</th>
                        <th>BEI Determinant</th>
                        <th>Work Relatedness</th>
                        <th>Removal Protection / Further Action</th>
                        <th>Department / Work Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo $esc($row['worker']); ?></td>
                            <td><?php echo $esc($row['chemical']); ?></td>
                            <td><?php echo $esc($row['job']); ?></td>
                            <td><?php echo $esc($row['history']); ?></td>
                            <td><?php echo $esc($row['clinical']); ?></td>
                            <td><?php echo $esc($row['target'] !== '' ? $row['target'] : 'Not recorded'); ?></td>
                            <td><?php echo $esc($row['bei']); ?></td>
                            <td><?php echo $esc($row['work'] !== '' ? $row['work'] : 'Not recorded'); ?></td>
                            <td><?php echo $esc($row['protection']); ?></td>
                            <td><?php echo $esc($row['department']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="foot-note">
            Use this printout for landscape review and follow-up tracking. Only USECHH 5ii is intentionally set to landscape for clearer row-based comparison.
        </div>
    </section>
</div>
</body>
</html>
