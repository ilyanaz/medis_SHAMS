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

$company = $companyId > 0 ? DB::table('company')->where('company_id', $companyId)->first() : null;
$chemical = $surveillanceId > 0 ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first() : null;
$summary = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('summary_report')
    ? DB::table('summary_report')->where('surveillance_id', $surveillanceId)->first()
    : null;
$doctor = ! empty($declaration->doctor_id) && DB::getSchemaBuilder()->hasTable('doctor')
    ? DB::table('doctor')->where('doctor_id', $declaration->doctor_id)->first()
    : null;

$overviewRows = [
    'Workplace Name' => $company->company_name ?? '-',
    'MyKKP Registration No.' => $company->mykpp_registration_no ?? '-',
    'Workplace Address' => trim((string) (($company->company_address ?? '') . ', ' . ($company->company_postcode ?? '') . ' ' . ($company->company_district ?? '') . ', ' . ($company->company_state ?? ''))),
    'Workers in Workplace' => $summary->totalNo_workplace ?? $company->total_workers ?? 0,
    'Work Unit' => $summary->name_of_workUnit ?? '-',
    'Exposed Workers' => $summary->no_exposedWorkers ?? 0,
    'Workers Examined' => $summary->totalNo_examined ?? 0,
    'Chemical' => $summary->chemical_name ?? $chemical->chemicals ?? '-',
    'CHRA Report No.' => $summary->CHRA_reportNo ?? '-',
    'CHRA Date' => $chemical->examination_date ?? '-',
    'CHRA Indication' => $summary->indication_CHRAreport ?? '-',
    'Laboratory' => $summary->name_of_laboratoy ?? '-',
];

$metricRows = [
    ['label' => 'History of health effects', 'normal' => $summary->no_ofWorkersNormal_H ?? 0, 'occ' => $summary->no_ofWorkersAbormal_OccupationalH ?? 0, 'non_occ' => 0, 'mrp' => 0],
    ['label' => 'Clinical findings', 'normal' => $summary->no_ofWorkersNormal_I ?? 0, 'occ' => $summary->no_ofWorkersAbormal_OccupationalI ?? 0, 'non_occ' => $summary->no_ofWorkersAbormal_nonOccupationalI ?? 0, 'mrp' => $summary->no_ofWorkersRecommended_I ?? 0],
    ['label' => 'Target organ function tests', 'normal' => $summary->no_ofWorkersNormal_J ?? 0, 'occ' => $summary->no_ofWorkersAbormal_OccupationalJ ?? 0, 'non_occ' => $summary->no_ofWorkersAbormal_nonOccupationalJ ?? 0, 'mrp' => $summary->no_ofWorkersRecommended_J ?? 0],
    ['label' => 'BEI determinant', 'normal' => $summary->no_ofWorkersNormal_K ?? 0, 'occ' => $summary->no_ofWorkersAbormal_OccupationalK ?? 0, 'non_occ' => $summary->no_ofWorkersAbormal_nonOccupationalK ?? 0, 'mrp' => $summary->no_ofWorkersRecommended_K ?? 0],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>USECHH 4 Summary Report</title>
</head>
<body>
<style>
@page{size:A4 portrait;margin:12mm}
body{margin:0;padding:18px;background:#fff;color:#0f172a;font-family:"Poppins","Segoe UI",Tahoma,Geneva,Verdana,sans-serif}
.sheet{display:grid;gap:18px}
.report-card{border:1px solid #d9e6dd;border-radius:22px;background:#fff;overflow:hidden}
.report-head{padding:18px 22px;border-bottom:1px solid #e5efe7}
.eyebrow{font-size:.82rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#2f7a45}
.report-title{margin:6px 0 0;font-size:1.7rem}
.report-subtitle{margin:6px 0 0;color:#5f6f65}
.section{padding:20px 22px;border-top:1px solid #edf2ee}
.overview{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.field{border:1px solid #dbe7de;border-radius:16px;padding:14px 16px;background:#fbfdfb}
.field-label{display:block;margin-bottom:6px;font-size:.76rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6b7d71}
.field-value{font-size:1rem;font-weight:600}
.field.full{grid-column:1 / -1}
.summary-table{width:100%;border-collapse:collapse}
.summary-table th,.summary-table td{border:1px solid #cfe0d3;padding:12px 10px;font-size:.95rem}
.summary-table th{background:#dcefe1;color:#123524;text-align:center}
.summary-table td:first-child{font-weight:600}
.decision-box{display:grid;grid-template-columns:1.1fr 1.4fr .9fr;gap:14px}
.note-box{border:1px solid #dbe7de;border-radius:16px;background:#f5faf6;padding:16px}
@media print{body{padding:0}.report-card{break-inside:avoid}}
</style>
<div class="sheet">
    <?php require __DIR__ . '/partials/clinic_header.php'; ?>
    <section class="report-card">
        <div class="report-head">
            <div class="eyebrow">USECHH 4</div>
            <h1 class="report-title">Summary Report for Medical Surveillance</h1>
            <p class="report-subtitle">Workplace-level surveillance summary in the system report style.</p>
        </div>

        <div class="section">
            <div class="overview">
                <?php foreach ($overviewRows as $label => $value): ?>
                    <div class="field<?php echo in_array($label, ['Workplace Address', 'CHRA Indication'], true) ? ' full' : ''; ?>">
                        <span class="field-label"><?php echo $esc($label); ?></span>
                        <div class="field-value"><?php echo $esc((string) ($value !== '' ? $value : '-')); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section">
            <table class="summary-table">
                <thead>
                    <tr>
                        <th rowspan="2">Medical Surveillance Result</th>
                        <th rowspan="2">Workers with Normal Findings</th>
                        <th colspan="2">Workers with Abnormal Findings</th>
                        <th rowspan="2">Workers Recommended for Removal Protection</th>
                    </tr>
                    <tr>
                        <th>Occupational</th>
                        <th>Non-Occupational</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metricRows as $row): ?>
                        <tr>
                            <td><?php echo $esc($row['label']); ?></td>
                            <td style="text-align:center"><?php echo $esc((string) $row['normal']); ?></td>
                            <td style="text-align:center"><?php echo $esc((string) $row['occ']); ?></td>
                            <td style="text-align:center"><?php echo $esc((string) $row['non_occ']); ?></td>
                            <td style="text-align:center"><?php echo $esc((string) $row['mrp']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="field full"><span class="field-label">Recommendation</span><div class="field-value"><?php echo nl2br($esc((string) ($summary->recommendation ?? '-'))); ?></div></div>
        </div>

        <div class="section">
            <div class="decision-box">
                <div class="field"><span class="field-label">Decision</span><div class="field-value"><?php echo $esc((string) ($summary->decision ?? '-')); ?></div></div>
                <div class="field"><span class="field-label">Justification of Decision</span><div class="field-value"><?php echo $esc((string) ($summary->justification_decision ?? 'Not recorded')); ?></div></div>
                <div class="field"><span class="field-label">Implementation Date</span><div class="field-value"><?php echo $esc((string) ($summary->date_of_implementation ?? '-')); ?></div></div>
            </div>
        </div>

        <div class="section">
            <div class="note-box">
                Submitted by <strong><?php echo $esc(trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')))); ?></strong>
                (<?php echo $esc((string) ($doctor->OHD_registrationNo ?? $doctor->MMC_no ?? '-')); ?>).
            </div>
        </div>
    </section>
</div>
</body>
</html>
