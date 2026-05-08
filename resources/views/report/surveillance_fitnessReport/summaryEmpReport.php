<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;

$esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$query = request();
$declarationId = (int) $query->query('declaration_id', 0);
$employeeId = (int) $query->query('employee_id', 0);
$companyId = (int) $query->query('company_id', 0);
$surveillanceId = (int) $query->query('surveillance_id', 0);

$declaration = null;
if ($declarationId > 0 && DB::getSchemaBuilder()->hasTable('declaration')) {
    $declaration = DB::table('declaration')->where('declaration_id', $declarationId)->first();
}

if (! $declaration && DB::getSchemaBuilder()->hasTable('declaration')) {
    $declarationQuery = DB::table('declaration');
    if ($employeeId > 0) {
        $declarationQuery->where('employee_id', $employeeId);
    }
    if ($companyId > 0) {
        $declarationQuery->where('company_id', $companyId);
    }
    if ($surveillanceId > 0) {
        $declarationQuery->where('surveillance_id', $surveillanceId);
    }
    $declaration = $declarationQuery->orderByDesc('declaration_id')->first();
}

$surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);
$employeeId = (int) ($declaration->employee_id ?? $employeeId);
$companyId = (int) ($declaration->company_id ?? $companyId);

$employee = $employeeId > 0 && DB::getSchemaBuilder()->hasTable('employee')
    ? DB::table('employee')->where('employee_id', $employeeId)->first()
    : null;
$company = $companyId > 0 && DB::getSchemaBuilder()->hasTable('company')
    ? DB::table('company')->where('company_id', $companyId)->first()
    : null;
$chemical = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('chemical_information')
    ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first()
    : null;
$summaryReport = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('summary_report')
    ? DB::table('summary_report')->where('surveillance_id', $surveillanceId)->first()
    : null;
$recommendation = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('recommendation')
    ? DB::table('recommendation')->where('surveillance_id', $surveillanceId)->first()
    : null;
$findings = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('ms_findings')
    ? DB::table('ms_findings')->where('surveillance_id', $surveillanceId)->first()
    : null;
$targetOrgan = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('target_organ')
    ? DB::table('target_organ')->where('surveillance_id', $surveillanceId)->first()
    : null;
$biologicalMonitoring = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('biological_monitoring')
    ? DB::table('biological_monitoring')->where('surveillance_id', $surveillanceId)->first()
    : null;
$doctor = null;
if ($declaration && ! empty($declaration->doctor_id) && DB::getSchemaBuilder()->hasTable('doctor')) {
    $doctor = DB::table('doctor')->where('doctor_id', $declaration->doctor_id)->first();
}

$employeeName = trim((string) (($employee->employee_firstName ?? $declaration->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? $declaration->employee_lastName ?? '')));
$chemicalName = trim((string) ($summaryReport->chemical_name ?? $chemical->chemicals ?? ''));
$assessmentType = trim((string) ($chemical->examination_type ?? ''));
$msDate = trim((string) ($chemical->examination_date ?? $declaration->doctor_date ?? $declaration->employee_date ?? ''));
$historyEffects = trim((string) ($findings->history_of_health ?? ''));
$clinicalFindings = trim((string) ($findings->clinical_findings ?? ''));
$targetOrganSummary = implode('; ', array_filter([
    ! empty($targetOrgan->blood_count) ? 'Blood: ' . $targetOrgan->blood_count : null,
    ! empty($targetOrgan->renal_function) ? 'Renal: ' . $targetOrgan->renal_function : null,
    ! empty($targetOrgan->liver_function) ? 'Liver: ' . $targetOrgan->liver_function : null,
    ! empty($targetOrgan->chest_xray) ? 'Chest X-ray: ' . $targetOrgan->chest_xray : null,
    ! empty($targetOrgan->spirometry_FEV_FVC) ? 'Spirometry FEV/FVC: ' . $targetOrgan->spirometry_FEV_FVC : null,
]));
$belDeterminant = trim((string) ($biologicalMonitoring->baseline_annual ?? $biologicalMonitoring->baseline_results ?? $biologicalMonitoring->biological_exposure ?? ''));
$workRelatedness = implode(' / ', array_filter([
    isset($findings->CF_work_related) ? 'CF: ' . $findings->CF_work_related : null,
    isset($findings->TO_work_related) ? 'TO: ' . $findings->TO_work_related : null,
    isset($findings->BM_work_related) ? 'BM: ' . $findings->BM_work_related : null,
]));
$conclusion = trim((string) ($findings->conclusion_fitness ?? ''));
$mrpDate = trim((string) ($recommendation->MRPdate_start ?? $recommendation->nextReview_date ?? ''));
$doctorName = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
$doctorName = $doctorName !== '' ? $doctorName : trim((string) ($doctor->doctor_username ?? ''));
$doctorRegNo = trim((string) ($doctor->OHD_registrationNo ?? $doctor->MMC_no ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>USECHH 2 Summary Report</title>
</head>
<body>
<style>
@page{size:A4 landscape;margin:12mm}
body{margin:0;padding:18px;background:#fff;color:#000;font-family:Arial,Helvetica,sans-serif}
.sheet{display:grid;gap:16px}
.clinic-report-header{text-align:center}
.clinic-report-header img{max-width:100%;max-height:140px;object-fit:contain}
.clinic-report-header__fallback{padding:16px;border:1px solid #c9d8ea;border-radius:12px;font-size:18px;font-weight:700;letter-spacing:.04em}
.report-shell{border:1px solid #111;background:#fff}
.usechh-head{padding:8px 12px 0;text-align:center}
.usechh-head__top{display:grid;grid-template-columns:1fr auto;align-items:start;font-size:14px}
.usechh-head__right{font-weight:700}
.usechh-head__title{margin:12px 0 0;font-size:18px;font-weight:700}
.usechh-head__subtitle{margin:6px 0 10px;font-size:15px}
.meta{padding:0 10px 8px;font-size:14px}
.meta-row{display:flex;align-items:center;gap:8px;margin:4px 0}
.meta-label{min-width:138px;font-weight:700}
.meta-value{flex:1;min-height:18px;padding:1px 0;font-weight:500}
table{width:100%;border-collapse:collapse;table-layout:fixed}
th,td{border:1px solid #111;padding:8px 6px;vertical-align:top;font-size:13px;line-height:1.35}
th{background:#dcefe1;font-weight:700;text-align:center;color:#123524}
td{min-height:100px;word-wrap:break-word;white-space:pre-wrap}
.small-col{width:4%}
.date-col{width:9%}
.assessment-col{width:15%}
.history-col{width:12%}
.clinical-col{width:10%}
.target-col{width:11%}
.bel-col{width:10%}
.work-col{width:10%}
.conclusion-col{width:10%}
.mrp-col{width:10%}
.doctor-col{width:13%}
@media print{
  body{padding:0;background:#fff}
  .sheet{gap:10px}
  .clinic-report-header img{max-height:110px}
  .report-shell{border:1px solid #000}
  th,td{font-size:12px;padding:6px 5px}
  .usechh-head{padding:4px 10px 0}
  .usechh-head__top{font-size:13px}
  .usechh-head__title{font-size:16px}
  .usechh-head__subtitle{font-size:14px;margin-bottom:8px}
  .meta{padding:0 10px 6px;font-size:13px}
}
</style>
<div class="sheet">
    <?php require dirname(__DIR__) . '/partials/clinic_header.php'; ?>

    <section class="report-shell">
        <div class="usechh-head">
            <div class="usechh-head__top">
                <div>Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="usechh-head__right">USECHH 2</div>
            </div>
            <div class="usechh-head__subtitle">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
            <div class="usechh-head__title">SUMMARY REPORTS OF EMPLOYEE</div>
        </div>

        <div class="meta">
            <div class="meta-row">
                <div class="meta-label">Name of Worker:</div>
                <div class="meta-value"><?php echo $esc($employeeName); ?></div>
            </div>
            <div class="meta-row">
                <div class="meta-label">Name of chemical:</div>
                <div class="meta-value"><?php echo $esc($chemicalName); ?></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="small-col">No.</th>
                    <th class="date-col">MS Date</th>
                    <th class="assessment-col">Type of Assessment</th>
                    <th class="history-col">History of Health Effects due to CHTH Exposure</th>
                    <th class="clinical-col">Clinical Findings</th>
                    <th class="target-col">Target Organ Function Test (Specify Organ)</th>
                    <th class="bel-col">BEI Determinant</th>
                    <th class="work-col">Work Relatedness</th>
                    <th class="conclusion-col">Conclusion of MS Finding (Fit/Not Fit)</th>
                    <th class="mrp-col">Date Of MRP</th>
                    <th class="doctor-col">Name Of OHD/DOSH Reg. No.</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td><?php echo $esc($msDate); ?></td>
                    <td><?php echo $esc($assessmentType !== '' ? $assessmentType : 'Not recorded'); ?></td>
                    <td><?php echo $esc($historyEffects !== '' ? $historyEffects : 'Not recorded'); ?></td>
                    <td><?php echo $esc($clinicalFindings !== '' ? $clinicalFindings : 'Not recorded'); ?></td>
                    <td><?php echo $esc($targetOrganSummary !== '' ? $targetOrganSummary : 'Not recorded'); ?></td>
                    <td><?php echo $esc($belDeterminant !== '' ? $belDeterminant : 'Not recorded'); ?></td>
                    <td><?php echo $esc($workRelatedness !== '' ? $workRelatedness : 'Not recorded'); ?></td>
                    <td><?php echo $esc($conclusion !== '' ? $conclusion : 'Not recorded'); ?></td>
                    <td><?php echo $esc($mrpDate !== '' ? $mrpDate : 'Not recorded'); ?></td>
                    <td><?php echo $esc(trim($doctorRegNo !== '' ? ($doctorName . ' / ' . $doctorRegNo) : $doctorName)); ?></td>
                </tr>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>
