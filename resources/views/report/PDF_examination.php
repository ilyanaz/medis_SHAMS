<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__) . '/panel/navigation.php';

$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$query = request();
$declarationId = (int) $query->query('declaration_id', 0);
$employeeId = (int) $query->query('employee_id', 0);
$companyId = (int) $query->query('company_id', 0);
$surveillanceId = (int) $query->query('surveillance_id', 0);

$declaration = $declarationId > 0 && Schema::hasTable('declaration')
    ? DB::table('declaration')->where('declaration_id', $declarationId)->first()
    : null;

if (! $declaration && Schema::hasTable('declaration')) {
    $declaration = DB::table('declaration')
        ->when($employeeId > 0, fn ($row) => $row->where('employee_id', $employeeId))
        ->when($companyId > 0, fn ($row) => $row->where('company_id', $companyId))
        ->when($surveillanceId > 0, fn ($row) => $row->where('surveillance_id', $surveillanceId))
        ->orderByDesc('declaration_id')
        ->first();
}

$surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);
$employeeId = (int) ($declaration->employee_id ?? $employeeId);
$companyId = (int) ($declaration->company_id ?? $companyId);

$employee = $employeeId > 0 && Schema::hasTable('employee')
    ? DB::table('employee')->where('employee_id', $employeeId)->first()
    : null;
$company = $companyId > 0 && Schema::hasTable('company')
    ? DB::table('company')->where('company_id', $companyId)->first()
    : null;
$chemicalInfo = $surveillanceId > 0 && Schema::hasTable('chemical_information')
    ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first()
    : null;
$historyOfHealth = $surveillanceId > 0 && Schema::hasTable('history_of_health')
    ? DB::table('history_of_health')->where('surveillance_id', $surveillanceId)->first()
    : null;
$clinicalFindings = $surveillanceId > 0 && Schema::hasTable('clinical_findings')
    ? DB::table('clinical_findings')->where('surveillance_id', $surveillanceId)->first()
    : null;
$physicalExam = $surveillanceId > 0 && Schema::hasTable('physical_examination')
    ? DB::table('physical_examination')->where('surveillance_id', $surveillanceId)->first()
    : null;
$targetOrgan = $surveillanceId > 0 && Schema::hasTable('target_organ')
    ? DB::table('target_organ')->where('surveillance_id', $surveillanceId)->first()
    : null;
$biologicalMonitoring = $surveillanceId > 0 && Schema::hasTable('biological_monitoring')
    ? DB::table('biological_monitoring')->where('surveillance_id', $surveillanceId)->first()
    : null;
$fitnessRespirator = $surveillanceId > 0 && Schema::hasTable('fitness_respirator')
    ? DB::table('fitness_respirator')->where('surveillance_id', $surveillanceId)->first()
    : null;
$msFindings = $surveillanceId > 0 && Schema::hasTable('ms_findings')
    ? DB::table('ms_findings')->where('surveillance_id', $surveillanceId)->first()
    : null;
$recommendationData = $surveillanceId > 0 && Schema::hasTable('recommendation')
    ? DB::table('recommendation')->where('surveillance_id', $surveillanceId)->first()
    : null;

$otherTargetTests = [];
if ($surveillanceId > 0 && Schema::hasTable('target_organ_other_tests')) {
    $otherTargetTests = DB::table('target_organ_other_tests')
        ->where('surveillance_id', $surveillanceId)
        ->orderBy('sort_order')
        ->orderBy('other_target_test_id')
        ->get(['test_name', 'result', 'comments'])
        ->map(static fn ($row) => [
            'test_name' => trim((string) ($row->test_name ?? '')),
            'result' => trim((string) ($row->result ?? '')),
            'comments' => trim((string) ($row->comments ?? '')),
        ])
        ->filter(static fn ($row) => $row['test_name'] !== '' || $row['result'] !== '' || $row['comments'] !== '')
        ->values()
        ->all();
}

$showValue = static function ($value, string $fallback = '-'): string {
    $value = trim((string) ($value ?? ''));
    return $value !== '' ? $value : $fallback;
};
$formatDate = static function ($value) use ($showValue): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return '-';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y', $timestamp) : $showValue($value);
};
$workerName = trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? '')));
$identityNo = trim((string) (($employee->employee_NRIC ?? '') !== '' ? ($employee->employee_NRIC ?? '') : ($employee->employee_passportNo ?? '')));
$historyLabels = [
    'breathing_difficulty' => 'Breathing difficulty',
    'cough' => 'Cough',
    'sore_throat' => 'Sore throat',
    'sneezing' => 'Sneezing',
    'chest_pain' => 'Chest pain',
    'palpitation' => 'Palpitation',
    'limb_oedema' => 'Limb oedema',
    'drowsiness' => 'Drowsiness',
    'dizziness' => 'Dizziness',
    'headache' => 'Headache',
    'confusion' => 'Confusion',
    'lethargy' => 'Lethargy',
    'nausea' => 'Nausea',
    'vomiting' => 'Vomiting',
    'eye_irritations' => 'Eye irritations',
    'blurred_vision' => 'Blurred vision',
    'blisters' => 'Blisters',
    'burns' => 'Burns',
    'itching' => 'Itching',
    'rash' => 'Rash',
    'redness' => 'Redness',
    'abdominal_pain' => 'Abdominal pain',
    'abdominal_mass' => 'Abdominal mass',
    'jaundice' => 'Jaundice',
    'diarrhoea' => 'Diarrhoea',
    'loss_of_weight' => 'Loss of weight',
    'loss_of_appetite' => 'Loss of appetite',
    'dysuria' => 'Dysuria',
    'haematuria' => 'Haematuria',
];
$positiveSymptoms = [];
foreach ($historyLabels as $column => $label) {
    if (trim((string) ($historyOfHealth->{$column} ?? '')) === 'Yes') {
        $positiveSymptoms[] = $label;
    }
}
$biologicalRows = [];
$baselineRows = preg_split('/\r\n|\r|\n/', trim((string) ($biologicalMonitoring->baseline_results ?? ''))) ?: [];
$annualRows = preg_split('/\r\n|\r|\n/', trim((string) ($biologicalMonitoring->baseline_annual ?? ''))) ?: [];
$rowCount = max(count($baselineRows), count($annualRows));
for ($index = 0; $index < $rowCount; $index += 1) {
    $biologicalRows[] = [
        'baseline' => trim((string) ($baselineRows[$index] ?? '')),
        'annual' => trim((string) ($annualRows[$index] ?? '')),
    ];
}
$recommendationLines = array_values(array_filter(preg_split('/\r\n|\r|\n/', trim((string) ($recommendationData->recommencation_type ?? ''))) ?: [], static fn ($line) => trim((string) $line) !== ''));

medis_render_navigation_start([
    'clinicName' => $clinicName ?? 'Medis SHAMS',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'User',
    'active' => 'report',
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PDF Examination</title>
</head>
<body>
<style>
@page{size:A4 portrait;margin:10mm}
html,body{margin:0;padding:0;background:#fff;color:#0f172a;font-family:Arial,Helvetica,sans-serif;font-size:11pt}
.report-page{width:100%;max-width:none;margin:0;color:#0f172a;font-family:Arial,Helvetica,sans-serif;font-size:11pt}
.sheet{width:186mm;min-height:273mm;margin:0 auto;padding:0;background:#fff}
.sheet-top{position:relative;display:block;text-align:center;margin-bottom:10px}
.center-title{display:block;width:100%;text-align:center;font-size:11pt;font-weight:700;line-height:1.35;color:#0f172a}
.right-code{position:absolute;right:0;top:0;font-size:11pt;font-weight:700;line-height:1.35;color:#0f172a}
.sheet-title{text-align:center;margin-bottom:18px}
.sheet-title .line{font-size:11pt;font-weight:700;line-height:1.35}
.sheet-title .main{font-size:13pt;font-weight:700;line-height:1.35;margin-top:8px;letter-spacing:.02em}
.document-table{width:100%;border-collapse:collapse;margin-bottom:16px;table-layout:fixed}
.document-table th,.document-table td{border:1px solid #c9d8ea;padding:8px 10px;font-size:11pt;vertical-align:top;text-align:left;word-wrap:break-word}
.document-table th{font-weight:700;color:#0f172a;background:#fff}
.section-block{margin-top:22px}
.section-heading{display:flex;align-items:center;gap:14px;margin:0 0 14px}
.section-heading::after{content:"";flex:1;height:1px;background:#c9d8ea}
.section-heading span{font-size:11pt;font-weight:700;letter-spacing:.02em;text-transform:uppercase;white-space:nowrap}
.text-block{line-height:1.6;white-space:pre-wrap}
.pill-list{display:flex;gap:8px;flex-wrap:wrap}
.pill{display:inline-flex;align-items:center;padding:4px 8px;border:1px solid #c9d8ea;border-radius:999px;font-size:11pt}
.two-col{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.pdf-page-break{page-break-before:always;break-before:page}
@media print{.app-topbar,.app-sidebar{display:none !important}.app-shell,.app-main,.app-page,.app-card{display:block;height:auto;overflow:visible;padding:0!important;border:0!important;background:#fff!important}.sheet{width:auto;min-height:auto;padding:0;border:0;box-shadow:none}}
</style>
<div class="report-page">
    <section class="sheet">
        <div class="sheet-top">
            <span class="center-title">Occupational Safety and Health Act 1994 (Act 514)</span>
            <span class="right-code">EXAMINATION</span>
        </div>
        <div class="sheet-title">
            <div class="line">Medical Surveillance Examination Report</div>
            <div class="main">EXAMINATION DETAILS</div>
        </div>

        <table class="document-table">
            <tbody>
                <tr>
                    <th>Patient Name</th>
                    <td><?php echo $esc($showValue($workerName)); ?></td>
                    <th>NRIC / Passport</th>
                    <td><?php echo $esc($showValue($identityNo)); ?></td>
                </tr>
                <tr>
                    <th>Company</th>
                    <td><?php echo $esc($showValue($company->company_name ?? null)); ?></td>
                    <th>Date Examined</th>
                    <td><?php echo $esc($formatDate($chemicalInfo->examination_date ?? $declaration->doctor_date ?? $declaration->employee_date ?? null)); ?></td>
                </tr>
                <tr>
                    <th>Chemical</th>
                    <td><?php echo $esc($showValue($chemicalInfo->chemicals ?? null)); ?></td>
                    <th>Examination Type</th>
                    <td><?php echo $esc($showValue($chemicalInfo->examination_type ?? null)); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="section-block">
            <div class="section-heading"><span>Health Effect History</span></div>
            <table class="document-table">
                <tbody>
                    <tr>
                        <th>Positive Symptoms</th>
                        <td colspan="3">
                            <?php if ($positiveSymptoms !== []): ?>
                                <div class="pill-list">
                                    <?php foreach ($positiveSymptoms as $symptom): ?>
                                        <span class="pill"><?php echo $esc($symptom); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                None reported
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section-block">
            <div class="section-heading"><span>Clinical And Physical Findings</span></div>
            <table class="document-table">
                <tbody>
                    <tr>
                        <th>Clinical Findings</th>
                        <td colspan="3"><?php echo $esc($showValue($clinicalFindings->result_clinical_findings ?? null)); ?></td>
                    </tr>
                    <tr>
                        <th>Weight</th>
                        <td><?php echo $esc($showValue($physicalExam->weight ?? null)); ?></td>
                        <th>Height</th>
                        <td><?php echo $esc($showValue($physicalExam->height ?? null)); ?></td>
                    </tr>
                    <tr>
                        <th>BMI</th>
                        <td><?php echo $esc($showValue($physicalExam->BMI ?? null)); ?></td>
                        <th>Others</th>
                        <td><?php echo $esc($showValue($physicalExam->others ?? null)); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section-block">
            <div class="section-heading"><span>Target Organ Test</span></div>
            <table class="document-table">
                <thead>
                    <tr>
                        <th>Test</th>
                        <th>Result</th>
                        <th colspan="2">Comments / Findings</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Full Blood Count</td><td><?php echo $esc($showValue($targetOrgan->blood_count_result ?? null)); ?></td><td colspan="2"><?php echo $esc($showValue($targetOrgan->blood_comments ?? null)); ?></td></tr>
                    <tr><td>Renal Function Test</td><td><?php echo $esc($showValue($targetOrgan->renal_function_result ?? null)); ?></td><td colspan="2"><?php echo $esc($showValue($targetOrgan->renal_comments ?? null)); ?></td></tr>
                    <tr><td>Liver Function Test</td><td><?php echo $esc($showValue($targetOrgan->liver_function_result ?? null)); ?></td><td colspan="2"><?php echo $esc($showValue($targetOrgan->liver_comments ?? null)); ?></td></tr>
                    <tr><td>Chest X-ray</td><td><?php echo $esc($showValue($targetOrgan->chest_xray_result ?? null)); ?></td><td colspan="2"><?php echo $esc($showValue($targetOrgan->chest_comments ?? null)); ?></td></tr>
                    <?php foreach ($otherTargetTests as $targetTest): ?>
                        <tr>
                            <td><?php echo $esc($showValue($targetTest['test_name'] ?? null)); ?></td>
                            <td><?php echo $esc($showValue($targetTest['result'] ?? null)); ?></td>
                            <td colspan="2"><?php echo $esc($showValue($targetTest['comments'] ?? null)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr><td>Spirometry FEV1</td><td><?php echo $esc($showValue($targetOrgan->spirometry_FEV1 ?? null)); ?></td><td colspan="2"><?php echo $esc($showValue($targetOrgan->spirometry_comments ?? null)); ?></td></tr>
                    <tr><td>Spirometry FVC</td><td><?php echo $esc($showValue($targetOrgan->spirometry_FVC ?? null)); ?></td><td colspan="2">-</td></tr>
                    <tr><td>Spirometry FEV/FVC</td><td><?php echo $esc($showValue($targetOrgan->spirometry_FEV_FVC ?? null)); ?></td><td colspan="2">-</td></tr>
                </tbody>
            </table>
        </div>

        <div class="section-block pdf-page-break">
            <div class="section-heading"><span>Biological Monitoring</span></div>
            <table class="document-table">
                <thead>
                    <tr>
                        <th>Baseline</th>
                        <th>Annual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($biologicalRows !== []): ?>
                        <?php foreach ($biologicalRows as $biologicalRow): ?>
                            <tr>
                                <td><?php echo $esc($showValue($biologicalRow['baseline'] ?? null)); ?></td>
                                <td><?php echo $esc($showValue($biologicalRow['annual'] ?? null)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2"><?php echo $esc(($biologicalMonitoring->manual_completed ?? false) ? 'Marked as completed manually.' : 'No biological monitoring data.'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section-block">
            <div class="section-heading"><span>Fitness And MS Findings</span></div>
            <table class="document-table">
                <tbody>
                    <tr>
                        <th>Respirator Fitness</th>
                        <td><?php echo $esc($showValue($fitnessRespirator->fitness_result ?? null)); ?></td>
                        <th>Justification</th>
                        <td><?php echo $esc($showValue($fitnessRespirator->fitness_justification ?? null)); ?></td>
                    </tr>
                    <tr>
                        <th>History Of Health Effects</th>
                        <td><?php echo $esc($showValue($msFindings->history_of_health ?? null)); ?></td>
                        <th>Clinical Findings</th>
                        <td><?php echo $esc($showValue($msFindings->clinical_findings ?? null)); ?></td>
                    </tr>
                    <tr>
                        <th>Target Organ</th>
                        <td><?php echo $esc($showValue($msFindings->target_organ ?? null)); ?></td>
                        <th>Biological Monitoring</th>
                        <td><?php echo $esc($showValue($msFindings->biological_monitoring ?? null)); ?></td>
                    </tr>
                    <tr>
                        <th>Pregnancy / Breastfeeding</th>
                        <td><?php echo $esc($showValue($msFindings->pregnancy_breastFeding ?? null)); ?></td>
                        <th>Conclusion Of Fitness To Work</th>
                        <td><?php echo $esc($showValue($msFindings->conclusion_fitness ?? null)); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section-block">
            <div class="section-heading"><span>Recommendation</span></div>
            <table class="document-table">
                <tbody>
                    <tr>
                        <th>Recommendation Type</th>
                        <td colspan="3">
                            <div class="text-block"><?php echo $esc($recommendationLines !== [] ? implode("\n", $recommendationLines) : '-'); ?></div>
                        </td>
                    </tr>
                    <tr>
                        <th>MRP Start Date</th>
                        <td><?php echo $esc($formatDate($recommendationData->MRPdate_start ?? null)); ?></td>
                        <th>MRP End Date</th>
                        <td><?php echo $esc($formatDate($recommendationData->MRPdate_end ?? null)); ?></td>
                    </tr>
                    <tr>
                        <th>Next Review Date</th>
                        <td><?php echo $esc($formatDate($recommendationData->nextReview_date ?? null)); ?></td>
                        <th>Notes</th>
                        <td><?php echo $esc($showValue($recommendationData->notes ?? null)); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php medis_render_navigation_end(); ?>
</body>
</html>
