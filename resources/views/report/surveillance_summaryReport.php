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
$doctorSignature = trim((string) ($doctor->doctor_sign ?? $declaration->doctor_signature ?? ''));
$statusMessage = (string) session('status', '');
$workplaceAddress = trim((string) (($company->company_address ?? '') . ', ' . ($company->company_postcode ?? '') . ' ' . ($company->company_district ?? '') . ', ' . ($company->company_state ?? '')));
$chraIndicators = array_values(array_filter([
    stripos((string) ($summary->indication_CHRAreport ?? ''), 'Significant personal exposure') !== false ? 'Significant personal exposure (>= 50% PEL)' : null,
    stripos((string) ($summary->indication_CHRAreport ?? ''), 'Reported health effects') !== false ? 'Reported health effects' : null,
    stripos((string) ($summary->indication_CHRAreport ?? ''), 'Skin absorption') !== false ? 'Skin absorption' : null,
]));
if ($chraIndicators === []) {
    $chraIndicators[] = trim((string) ($summary->indication_CHRAreport ?? 'Not recorded'));
}
$mrpTotal = (int) (($summary->no_ofWorkersRecommended_I ?? 0) + ($summary->no_ofWorkersRecommended_J ?? 0) + ($summary->no_ofWorkersRecommended_K ?? 0));
$decisionRows = [
    [
        'label' => 'Continue MS',
        'selected' => strcasecmp((string) ($summary->decision ?? ''), 'Continue MS') === 0,
    ],
    [
        'label' => 'Stop MS',
        'selected' => strcasecmp((string) ($summary->decision ?? ''), 'Stop MS') === 0,
    ],
];
$screenSummary = [
    'totalNo_workplace' => old('totalNo_workplace', (string) ($summary->totalNo_workplace ?? $company->total_workers ?? '')),
    'name_of_workUnit' => old('name_of_workUnit', (string) ($summary->name_of_workUnit ?? '')),
    'no_exposedWorkers' => old('no_exposedWorkers', (string) ($summary->no_exposedWorkers ?? '')),
    'totalNo_examined' => old('totalNo_examined', (string) ($summary->totalNo_examined ?? '')),
    'CHRA_reportNo' => old('CHRA_reportNo', (string) ($summary->CHRA_reportNo ?? '')),
    'indication_CHRAreport' => old('indication_CHRAreport', (string) ($summary->indication_CHRAreport ?? '')),
    'name_of_laboratoy' => old('name_of_laboratoy', (string) ($summary->name_of_laboratoy ?? '')),
    'recommendation' => old('recommendation', (string) ($summary->recommendation ?? '')),
    'decision' => old('decision', (string) ($summary->decision ?? '')),
    'justification_decision' => old('justification_decision', (string) ($summary->justification_decision ?? '')),
    'date_of_implementation' => old('date_of_implementation', (string) ($summary->date_of_implementation ?? '')),
];

$overviewRows = [
    'Workplace Name' => $company->company_name ?? '-',
    'MyKKP Registration No.' => $company->mykpp_registration_no ?? '-',
    'Workplace Address' => $workplaceAddress,
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
.clinic-report-header{padding:0 0 8px}
.clinic-report-header img{display:block;width:100%;max-width:100%;max-height:none;height:auto;object-fit:contain}
.report-card{border:1px solid #d9e6dd;border-radius:22px;background:#fff;overflow:hidden}
.report-head{padding:18px 22px;border-bottom:1px solid #e5efe7}
.report-head-top{position:relative;display:block;text-align:center}
.report-code{position:absolute;right:0;top:0;font-size:14px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#0f172a}
.report-head-act{font-size:14px;font-weight:700;line-height:1.35}
.report-head-regulation{margin-top:4px;font-size:15px;font-weight:700;line-height:1.35}
.report-title{margin:12px 0 0;text-align:center;font-size:18px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.section{padding:20px 22px;border-top:1px solid #edf2ee}
.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 24px}
.detail-line{display:grid;grid-template-columns:190px 1fr;gap:12px;align-items:start;padding:6px 0}
.detail-line.full{grid-column:1 / -1}
.detail-label{font-size:.82rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#6b7d71}
.detail-value{font-size:1rem;font-weight:500}
.detail-list{margin:0;padding-left:18px}
.detail-list li{margin:4px 0}
.summary-table{width:100%;border-collapse:collapse}
.summary-table th,.summary-table td{border:1px solid #d6e2d8;padding:12px 10px;font-size:.95rem}
.summary-table th{background:#dcefe1;color:#123524;text-align:center}
.summary-table td:first-child{font-weight:600}
.narrative{line-height:1.8}
.flash{margin:0 22px 8px;padding:10px 14px;border:1px solid #cfe7d4;border-radius:12px;background:#f3fbf4;color:#1f5f35;font-size:.9rem}
.editor-form{display:grid;gap:14px}
.editor-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 18px}
.editor-field{display:grid;gap:8px}
.editor-field.full{grid-column:1 / -1}
.editor-label{font-size:.82rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#6b7d71}
.editor-input,.editor-select,.editor-textarea{width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:14px;font:inherit;background:#fff;color:#0f172a}
.editor-textarea{min-height:90px;resize:vertical}
.editor-actions{display:flex;justify-content:flex-end}
.save-btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 18px;border:1px solid #2f9e44;border-radius:999px;background:#2f9e44;color:#fff;font:inherit;font-weight:700;cursor:pointer}
.decision-table{width:100%;border-collapse:collapse}
.decision-table th,.decision-table td{border:1px solid #d6e2d8;padding:12px 10px;font-size:.95rem;vertical-align:top}
.decision-table th{background:#dcefe1;color:#123524;text-align:center}
.decision-table td:first-child,.decision-table td:last-child{text-align:center}
.tick{font-weight:800;color:#2f9e44}
.declaration-block{line-height:1.8}
.signature-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:end}
.signature-meta{display:grid;gap:8px}
.signature-line strong{display:inline-block;min-width:190px}
.signature-box{text-align:center}
.signature-box img{display:block;max-width:220px;max-height:72px;object-fit:contain;margin:0 auto 10px}
.signature-box .date-line{margin-top:24px;display:flex;justify-content:space-between;gap:20px}
.screen-only{display:block}
.print-only{display:none}
@media print{body{padding:0}.report-card{break-inside:avoid}.screen-only{display:none!important}.print-only{display:block}}
</style>
<div class="sheet">
    <?php require __DIR__ . '/partials/clinic_header.php'; ?>
    <section class="report-card">
        <div class="report-head">
            <div class="report-head-top">
                <div class="report-code">USECHH 4</div>
                <div class="report-head-act">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="report-head-regulation">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                <h1 class="report-title">Summary Report for Medical Surveillance</h1>
            </div>
        </div>

        <?php if ($statusMessage !== ''): ?>
            <div class="flash screen-only"><?php echo $esc($statusMessage); ?></div>
        <?php endif; ?>

        <div class="section">
            <div class="detail-grid">
                <?php foreach ($overviewRows as $label => $value): ?>
                    <div class="detail-line<?php echo in_array($label, ['Workplace Address', 'CHRA Indication'], true) ? ' full' : ''; ?>">
                        <div class="detail-label"><?php echo $esc($label); ?></div>
                        <div class="detail-value"><?php echo $esc((string) ($value !== '' ? $value : '-')); ?></div>
                    </div>
                <?php endforeach; ?>
                <div class="detail-line full">
                    <div class="detail-label">Indication for Medical Surveillance based on CHRA Report</div>
                    <div class="detail-value">
                        <ul class="detail-list">
                            <?php foreach ($chraIndicators as $indicator): ?>
                                <li><?php echo $esc((string) $indicator); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="detail-line">
                    <div class="detail-label">Total Employees Recommended for MRP</div>
                    <div class="detail-value"><?php echo $esc((string) $mrpTotal); ?></div>
                </div>
                <div class="detail-line">
                    <div class="detail-label">Name of Laboratory</div>
                    <div class="detail-value"><?php echo $esc((string) ($summary->name_of_laboratoy ?? '-')); ?></div>
                </div>
            </div>
        </div>

        <div class="section screen-only">
            <form class="editor-form" method="post" action="<?php echo $esc(route('surveillance.report.summary.save')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="declaration_id" value="<?php echo $esc((string) ($declaration->declaration_id ?? $declarationId)); ?>">
                <input type="hidden" name="employee_id" value="<?php echo $esc((string) $employeeId); ?>">
                <input type="hidden" name="company_id" value="<?php echo $esc((string) $companyId); ?>">
                <input type="hidden" name="surveillance_id" value="<?php echo $esc((string) $surveillanceId); ?>">
                <div class="editor-grid">
                    <div class="editor-field">
                        <label class="editor-label">Workers in Workplace</label>
                        <input class="editor-input" type="number" min="0" name="totalNo_workplace" value="<?php echo $esc($screenSummary['totalNo_workplace']); ?>">
                    </div>
                    <div class="editor-field">
                        <label class="editor-label">Work Unit</label>
                        <input class="editor-input" type="text" name="name_of_workUnit" value="<?php echo $esc($screenSummary['name_of_workUnit']); ?>">
                    </div>
                    <div class="editor-field">
                        <label class="editor-label">Exposed Workers</label>
                        <input class="editor-input" type="number" min="0" name="no_exposedWorkers" value="<?php echo $esc($screenSummary['no_exposedWorkers']); ?>">
                    </div>
                    <div class="editor-field">
                        <label class="editor-label">Workers Examined</label>
                        <input class="editor-input" type="number" min="0" name="totalNo_examined" value="<?php echo $esc($screenSummary['totalNo_examined']); ?>">
                    </div>
                    <div class="editor-field">
                        <label class="editor-label">CHRA Report No.</label>
                        <input class="editor-input" type="text" name="CHRA_reportNo" value="<?php echo $esc($screenSummary['CHRA_reportNo']); ?>">
                    </div>
                    <div class="editor-field">
                        <label class="editor-label">Laboratory Name</label>
                        <input class="editor-input" type="text" name="name_of_laboratoy" value="<?php echo $esc($screenSummary['name_of_laboratoy']); ?>">
                    </div>
                    <div class="editor-field full">
                        <label class="editor-label">CHRA Indication</label>
                        <textarea class="editor-textarea" name="indication_CHRAreport"><?php echo $esc($screenSummary['indication_CHRAreport']); ?></textarea>
                    </div>
                    <div class="editor-field full">
                        <label class="editor-label">Recommendation</label>
                        <textarea class="editor-textarea" name="recommendation"><?php echo $esc($screenSummary['recommendation']); ?></textarea>
                    </div>
                    <div class="editor-field">
                        <label class="editor-label">Decision</label>
                        <select class="editor-select" name="decision">
                            <option value="">Select decision</option>
                            <option value="Continue MS" <?php echo $screenSummary['decision'] === 'Continue MS' ? 'selected' : ''; ?>>Continue MS</option>
                            <option value="Stop MS" <?php echo $screenSummary['decision'] === 'Stop MS' ? 'selected' : ''; ?>>Stop MS</option>
                        </select>
                    </div>
                    <div class="editor-field">
                        <label class="editor-label">Date of Implementation</label>
                        <input class="editor-input" type="date" name="date_of_implementation" value="<?php echo $esc($screenSummary['date_of_implementation']); ?>">
                    </div>
                    <div class="editor-field full">
                        <label class="editor-label">Justification of Decision</label>
                        <textarea class="editor-textarea" name="justification_decision"><?php echo $esc($screenSummary['justification_decision']); ?></textarea>
                    </div>
                </div>
                <div class="editor-actions">
                    <button class="save-btn" type="submit">Save Details</button>
                </div>
            </form>
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
            <div class="detail-line full">
                <div class="detail-label">Recommendation</div>
                <div class="detail-value narrative"><?php echo nl2br($esc((string) ($summary->recommendation ?? '-'))); ?></div>
            </div>
        </div>

        <div class="section">
            <table class="decision-table">
                <thead>
                    <tr>
                        <th>*</th>
                        <th>Decision</th>
                        <th>Justification of Decision</th>
                        <th>Date of Implementation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($decisionRows as $row): ?>
                        <tr>
                            <td><?php echo $row['selected'] ? '<span class="tick">&#10003;</span>' : ''; ?></td>
                            <td><?php echo $esc($row['label']); ?></td>
                            <td><?php echo $esc((string) ($summary->justification_decision ?? 'Not recorded')); ?></td>
                            <td><?php echo $esc((string) ($summary->date_of_implementation ?? '-')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="narrative" style="margin-top:10px;">* Please mark the applicable decision.</div>
        </div>

        <div class="section">
            <div class="declaration-block">
                I hereby declare that all particulars given in this report are accurate to the best of my knowledge.
            </div>
            <div class="detail-grid" style="margin-top:12px;">
                <div class="detail-line full">
                    <div class="detail-label">Name of Occupational Health Doctor</div>
                    <div class="detail-value"><?php echo $esc(trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')))); ?></div>
                </div>
                <div class="detail-line">
                    <div class="detail-label">OHD Registration No.</div>
                    <div class="detail-value"><?php echo $esc((string) ($doctor->OHD_registrationNo ?? $doctor->MMC_no ?? '-')); ?></div>
                </div>
                <div class="detail-line full">
                    <div class="detail-label">Name of Practice &amp; Address</div>
                    <div class="detail-value"><?php echo $esc($workplaceAddress !== '' ? $workplaceAddress : trim((string) (($doctor->doctor_address ?? '') . ', ' . ($doctor->doctor_postcode ?? '') . ' ' . ($doctor->doctor_district ?? '') . ', ' . ($doctor->doctor_state ?? '')))); ?></div>
                </div>
                <div class="detail-line">
                    <div class="detail-label">Tel No.</div>
                    <div class="detail-value"><?php echo $esc((string) ($doctor->doctor_telephone ?? '-')); ?></div>
                </div>
                <div class="detail-line">
                    <div class="detail-label">Fax No.</div>
                    <div class="detail-value"><?php echo $esc((string) ($doctor->doctor_fax ?? '-')); ?></div>
                </div>
                <div class="detail-line full">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-value"><?php echo $esc((string) ($doctor->doctor_email ?? '-')); ?></div>
                </div>
            </div>
            <div class="signature-grid" style="margin-top:28px;">
                <div class="signature-meta">
                    <div class="signature-line"><strong>Date:</strong> <?php echo $esc((string) ($summary->date_of_implementation ?? $chemical->examination_date ?? '-')); ?></div>
                </div>
                <div class="signature-box">
                    <?php if ($doctorSignature !== ''): ?>
                        <img src="<?php echo $esc($doctorSignature); ?>" alt="Doctor signature">
                    <?php endif; ?>
                    <div><strong>Signature</strong></div>
                </div>
            </div>
        </div>
    </section>
</div>
</body>
</html>
