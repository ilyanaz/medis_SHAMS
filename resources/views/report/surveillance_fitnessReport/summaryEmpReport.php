<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
$targetResultValue = static function (?object $targetOrganRecord, string $resultColumn): string {
    if (! $targetOrganRecord) {
        return '';
    }

    return trim((string) ($targetOrganRecord->{$resultColumn} ?? ''));
};
$doctorName = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
$doctorName = $doctorName !== '' ? $doctorName : trim((string) ($doctor->doctor_username ?? ''));
$doctorRegNo = trim((string) ($doctor->OHD_registrationNo ?? $doctor->MMC_no ?? ''));
$resolvedUser = Auth::user();
$loggedInDoctorName = trim((string) (($resolvedUser->first_name ?? $resolvedUser->firstName ?? '') . ' ' . ($resolvedUser->last_name ?? $resolvedUser->lastName ?? '')));
$loggedInDoctorName = $loggedInDoctorName !== '' ? $loggedInDoctorName : trim((string) (($resolvedUser->name ?? '') ?: $doctorName));
$loggedInDoctorRegNo = trim((string) (($resolvedUser->OHD_registrationNo ?? $resolvedUser->ohd_registration_no ?? $resolvedUser->MMC_no ?? '') ?: $doctorRegNo));
$formatDisplayValue = static function (?string $value): string {
    $value = trim((string) $value);
    return $value !== '' ? $value : 'Not recorded';
};
$formatYesNoValue = static function (?string $value): string {
    $value = strtolower(trim((string) $value));
    return match ($value) {
        'yes', 'abnormal', 'not fit' => 'Yes',
        'no', 'normal', 'fit' => 'No',
        default => trim((string) $value) !== '' ? trim((string) $value) : 'Not recorded',
    };
};
$formatDate = static function (?string $value, string $format = 'd/m/Y'): string {
    $value = trim((string) $value);
    if ($value === '') {
        return 'Not recorded';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date($format, $timestamp) : $value;
};
$buildTargetOrganSummary = static function (?object $targetOrganRecord, int $rowSurveillanceId) use ($targetResultValue): string {
    $summaryParts = array_filter([
        $targetResultValue($targetOrganRecord, 'blood_count_result') !== '' ? 'Full Blood Count: ' . $targetResultValue($targetOrganRecord, 'blood_count_result') : null,
        $targetResultValue($targetOrganRecord, 'renal_function_result') !== '' ? 'Renal Function Test: ' . $targetResultValue($targetOrganRecord, 'renal_function_result') : null,
        $targetResultValue($targetOrganRecord, 'liver_function_result') !== '' ? 'Liver Function Test: ' . $targetResultValue($targetOrganRecord, 'liver_function_result') : null,
        $targetResultValue($targetOrganRecord, 'chest_xray_result') !== '' ? 'Chest X-ray: ' . $targetResultValue($targetOrganRecord, 'chest_xray_result') : null,
        ! empty($targetOrganRecord->spirometry_FEV_FVC) ? 'Spirometry FEV/FVC: ' . $targetOrganRecord->spirometry_FEV_FVC : null,
    ]);

    $otherTargetTests = [];
    if ($rowSurveillanceId > 0 && DB::getSchemaBuilder()->hasTable('target_organ_other_tests')) {
        $otherTargetTests = DB::table('target_organ_other_tests')
            ->where('surveillance_id', $rowSurveillanceId)
            ->orderBy('sort_order')
            ->orderBy('other_target_test_id')
            ->get(['test_name', 'result', 'comments'])
            ->map(static fn ($row) => [
                'name' => trim((string) ($row->test_name ?? '')),
                'result' => trim((string) ($row->result ?? '')),
                'comments' => trim((string) ($row->comments ?? '')),
            ])
            ->filter(static fn ($row) => $row['name'] !== '' || $row['result'] !== '' || $row['comments'] !== '')
            ->values()
            ->all();
    }

    if ($otherTargetTests === [] && ! empty($targetOrganRecord->other_tests ?? null)) {
        $decodedOtherTargetTests = json_decode((string) $targetOrganRecord->other_tests, true);
        if (is_array($decodedOtherTargetTests)) {
            $otherTargetTests = $decodedOtherTargetTests;
        }
    }

    if ($otherTargetTests !== []) {
        foreach ($otherTargetTests as $otherTargetTest) {
            $testName = trim((string) ($otherTargetTest['name'] ?? ''));
            $testResult = trim((string) ($otherTargetTest['result'] ?? ''));
            if ($testName !== '') {
                $summaryParts[] = $testResult !== '' ? $testName . ': ' . $testResult : $testName;
            }
        }
    }

    return implode("\n", $summaryParts);
};
$formatDoctorBlock = static function (?object $doctorRecord, string $fallbackName, string $fallbackRegNo): string {
    $doctorDisplayName = trim((string) (($doctorRecord->doctor_firstName ?? '') . ' ' . ($doctorRecord->doctor_lastName ?? '')));
    $doctorDisplayName = $doctorDisplayName !== '' ? $doctorDisplayName : $fallbackName;
    $doctorDisplayName = $doctorDisplayName !== '' ? strtoupper($doctorDisplayName) : 'NOT RECORDED';

    $registrationNo = trim((string) ($doctorRecord->OHD_registrationNo ?? $doctorRecord->MMC_no ?? $fallbackRegNo));
    if ($registrationNo === '') {
        return $doctorDisplayName;
    }

    return $doctorDisplayName . "\n" . 'Reg. No. ' . $registrationNo;
};

$reportRows = [];
if ($employeeId > 0 && DB::getSchemaBuilder()->hasTable('declaration') && DB::getSchemaBuilder()->hasTable('chemical_information')) {
    $historyDeclarations = DB::table('declaration')
        ->join('chemical_information', 'chemical_information.surveillance_id', '=', 'declaration.surveillance_id')
        ->leftJoin('recommendation', 'recommendation.surveillance_id', '=', 'declaration.surveillance_id')
        ->select([
            'declaration.declaration_id',
            'declaration.surveillance_id',
            'declaration.doctor_id',
            'declaration.employee_date',
            'declaration.doctor_date',
            'chemical_information.chemicals',
            'chemical_information.examination_type',
            'chemical_information.examination_date',
        ])
        ->where('declaration.employee_id', $employeeId)
        ->when($companyId > 0, static fn ($builder) => $builder->where('declaration.company_id', $companyId))
        ->when(
            DB::getSchemaBuilder()->hasColumn('recommendation', 'is_final'),
            static fn ($builder) => $builder->where('recommendation.is_final', 1)
        )
        ->when(
            $chemicalName !== '',
            static fn ($builder) => $builder->where('chemical_information.chemicals', $chemicalName)
        )
        ->orderByRaw('COALESCE(chemical_information.examination_date, declaration.doctor_date, declaration.employee_date) desc')
        ->orderByDesc('declaration.declaration_id')
        ->get();

    if ($chemicalName === '' && $historyDeclarations->isNotEmpty()) {
        $chemicalName = trim((string) ($historyDeclarations->first()->chemicals ?? ''));
    }

    foreach ($historyDeclarations as $index => $historyDeclaration) {
        $rowSurveillanceId = (int) ($historyDeclaration->surveillance_id ?? 0);
        $rowFindings = $rowSurveillanceId > 0 && DB::getSchemaBuilder()->hasTable('ms_findings')
            ? DB::table('ms_findings')->where('surveillance_id', $rowSurveillanceId)->first()
            : null;
        $rowTargetOrgan = $rowSurveillanceId > 0 && DB::getSchemaBuilder()->hasTable('target_organ')
            ? DB::table('target_organ')->where('surveillance_id', $rowSurveillanceId)->first()
            : null;
        $rowBiologicalMonitoring = $rowSurveillanceId > 0 && DB::getSchemaBuilder()->hasTable('biological_monitoring')
            ? DB::table('biological_monitoring')->where('surveillance_id', $rowSurveillanceId)->first()
            : null;
        $rowRecommendation = $rowSurveillanceId > 0 && DB::getSchemaBuilder()->hasTable('recommendation')
            ? DB::table('recommendation')->where('surveillance_id', $rowSurveillanceId)->first()
            : null;
        $rowDoctor = ! empty($historyDeclaration->doctor_id) && DB::getSchemaBuilder()->hasTable('doctor')
            ? DB::table('doctor')->where('doctor_id', (int) $historyDeclaration->doctor_id)->first()
            : null;

        $rowDoctorName = trim((string) (($rowDoctor->doctor_firstName ?? '') . ' ' . ($rowDoctor->doctor_lastName ?? '')));
        $rowDoctorName = $rowDoctorName !== '' ? $rowDoctorName : trim((string) ($rowDoctor->doctor_username ?? ''));
        $rowDoctorRegNo = trim((string) ($rowDoctor->OHD_registrationNo ?? $rowDoctor->MMC_no ?? ''));
        $rowWorkRelatedValues = array_values(array_filter([
            trim((string) ($rowFindings->CF_work_related ?? '')),
            trim((string) ($rowFindings->TO_work_related ?? '')),
            trim((string) ($rowFindings->BM_work_related ?? '')),
        ], static fn ($value) => $value !== ''));
        $rowWorkRelatedness = 'Not recorded';
        if ($rowWorkRelatedValues !== []) {
            $rowWorkRelatedness = count(array_unique(array_map('strtolower', $rowWorkRelatedValues))) === 1
                ? $formatYesNoValue($rowWorkRelatedValues[0])
                : implode(' / ', array_map($formatYesNoValue, $rowWorkRelatedValues));
        }

        $reportRows[] = [
            'index' => $index + 1,
            'ms_date' => $formatDate((string) ($historyDeclaration->examination_date ?? $historyDeclaration->doctor_date ?? $historyDeclaration->employee_date ?? '')),
            'assessment_type' => $formatDisplayValue((string) ($historyDeclaration->examination_type ?? '')),
            'history_effects' => $formatYesNoValue((string) ($rowFindings->history_of_health ?? '')),
            'clinical_findings' => $formatYesNoValue((string) ($rowFindings->clinical_findings ?? '')),
            'target_organ_summary' => $formatDisplayValue($buildTargetOrganSummary($rowTargetOrgan, $rowSurveillanceId)),
            'bel_determinant' => $formatDisplayValue((string) ($rowBiologicalMonitoring->baseline_annual ?? $rowBiologicalMonitoring->baseline_results ?? $rowBiologicalMonitoring->biological_exposure ?? '')),
            'work_relatedness' => $formatDisplayValue($rowWorkRelatedness),
            'conclusion' => $formatDisplayValue((string) ($rowFindings->conclusion_fitness ?? '')),
            'mrp_date' => $formatDate((string) ($rowRecommendation->MRPdate_start ?? $rowRecommendation->nextReview_date ?? '')),
            'doctor' => $formatDoctorBlock(null, $loggedInDoctorName !== '' ? $loggedInDoctorName : $rowDoctorName, $loggedInDoctorRegNo !== '' ? $loggedInDoctorRegNo : $rowDoctorRegNo),
        ];
    }
}

if ($reportRows === []) {
    $fallbackWorkRelatedValues = array_values(array_filter([
        trim((string) ($findings->CF_work_related ?? '')),
        trim((string) ($findings->TO_work_related ?? '')),
        trim((string) ($findings->BM_work_related ?? '')),
    ], static fn ($value) => $value !== ''));
    $fallbackWorkRelatedness = 'Not recorded';
    if ($fallbackWorkRelatedValues !== []) {
        $fallbackWorkRelatedness = count(array_unique(array_map('strtolower', $fallbackWorkRelatedValues))) === 1
            ? $formatYesNoValue($fallbackWorkRelatedValues[0])
            : implode(' / ', array_map($formatYesNoValue, $fallbackWorkRelatedValues));
    }

    $reportRows[] = [
        'index' => 1,
        'ms_date' => $formatDate((string) ($chemical->examination_date ?? $declaration->doctor_date ?? $declaration->employee_date ?? '')),
        'assessment_type' => $formatDisplayValue((string) ($chemical->examination_type ?? '')),
        'history_effects' => $formatYesNoValue((string) ($findings->history_of_health ?? '')),
        'clinical_findings' => $formatYesNoValue((string) ($findings->clinical_findings ?? '')),
        'target_organ_summary' => $formatDisplayValue($buildTargetOrganSummary($targetOrgan, $surveillanceId)),
        'bel_determinant' => $formatDisplayValue((string) ($biologicalMonitoring->baseline_annual ?? $biologicalMonitoring->baseline_results ?? $biologicalMonitoring->biological_exposure ?? '')),
        'work_relatedness' => $formatDisplayValue($fallbackWorkRelatedness),
        'conclusion' => $formatDisplayValue((string) ($findings->conclusion_fitness ?? '')),
        'mrp_date' => $formatDate((string) ($recommendation->MRPdate_start ?? $recommendation->nextReview_date ?? '')),
        'doctor' => $formatDoctorBlock(null, $loggedInDoctorName !== '' ? $loggedInDoctorName : $doctorName, $loggedInDoctorRegNo !== '' ? $loggedInDoctorRegNo : $doctorRegNo),
    ];
}
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
html,body{margin:0;padding:0;background:#fff;color:#000;font-family:Arial,Helvetica,sans-serif;font-size:11pt}
body{padding:12mm}
.pdf-page{width:100%}
.pdf-header img{display:block;width:100%;max-width:100%;height:auto}
.pdf-title{margin:14px 0 18px;text-align:center}
.pdf-title .law{font-size:11px;font-weight:700}
.pdf-title .main{margin-top:7px;font-size:18px;font-weight:700}
.pdf-meta{width:100%;border-collapse:collapse;margin-bottom:12px}
.pdf-meta td{padding:6px 8px;border:1px solid #d1d5db;font-size:11px;vertical-align:top}
.pdf-meta .label{width:180px;font-weight:700;background:#fafafa}
.pdf-table{width:100%;border-collapse:collapse;table-layout:auto;margin-top:6px}
.pdf-table th,.pdf-table td{border:1px solid #cbd5e1;padding:7px 8px;font-size:10px;vertical-align:top;text-align:left;word-wrap:break-word;overflow-wrap:anywhere}
.pdf-table th{background:#f8fafc;font-weight:700;font-size:9px;line-height:1.25;white-space:normal;word-break:break-word;hyphens:auto}
.pdf-table .col-no{width:3%}
.pdf-table .col-date{width:8%}
.pdf-table .col-assessment{width:12%}
.pdf-table .col-history{width:10%}
.pdf-table .col-clinical{width:9%}
.pdf-table .col-target{width:20%}
.pdf-table .col-bm{width:9%}
.pdf-table .col-work{width:10%}
.pdf-table .col-conclusion{width:10%}
.pdf-table .col-mrp{width:9%}
.pdf-table .col-doctor{width:14%}
.pdf-table .nowrap{white-space:nowrap;word-break:normal;overflow-wrap:normal}
.pdf-table .doctor-cell{font-size:9px;line-height:1.2;font-weight:700}
@media print{
  html,body{padding:0;background:#fff}
}
</style>
<div class="pdf-page">
    <div class="pdf-header">
        <?php require dirname(__DIR__) . '/partials/clinic_header.php'; ?>
    </div>
    <div class="pdf-title">
        <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
        <div class="law">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
        <div class="main">SUMMARY REPORTS OF EMPLOYEE</div>
    </div>

    <table class="pdf-meta">
        <tr>
            <td class="label">Name of Worker</td>
            <td><?php echo $esc($employeeName !== '' ? $employeeName : '-'); ?></td>
            <td class="label">Name of chemical</td>
            <td><?php echo $esc($chemicalName !== '' ? $chemicalName : '-'); ?></td>
        </tr>
    </table>

    <table class="pdf-table">
            <thead>
                <tr>
                    <th class="col-no nowrap">No</th>
                    <th class="col-date nowrap">MS Date</th>
                    <th class="col-assessment">Type of Assessment</th>
                    <th class="col-history">History Of Health Effects due to CHTH Exposure</th>
                    <th class="col-clinical">Clinical Findings</th>
                    <th class="col-target">Target Organ Function Test (Specify Organ)</th>
                    <th class="col-bm">BEI Determinants</th>
                    <th class="col-work">Work Relatedness</th>
                    <th class="col-conclusion">Conclusion of MS Finding (Fit / Not Fit)</th>
                    <th class="col-mrp">Date of MRP</th>
                    <th class="col-doctor">Name of OHD/DOSH Register No.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportRows as $reportRow): ?>
                    <tr>
                        <td><?php echo $esc((string) $reportRow['index']); ?></td>
                        <td class="nowrap"><?php echo $esc($reportRow['ms_date']); ?></td>
                        <td><?php echo $esc($reportRow['assessment_type']); ?></td>
                        <td><?php echo $esc($reportRow['history_effects']); ?></td>
                        <td><?php echo $esc($reportRow['clinical_findings']); ?></td>
                        <td><?php echo $esc($reportRow['target_organ_summary']); ?></td>
                        <td><?php echo $esc($reportRow['bel_determinant']); ?></td>
                        <td><?php echo $esc($reportRow['work_relatedness']); ?></td>
                        <td><?php echo $esc($reportRow['conclusion']); ?></td>
                        <td><?php echo $esc($reportRow['mrp_date']); ?></td>
                        <td class="doctor-cell"><?php echo $esc($reportRow['doctor']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
    </table>

</div>
</body>
</html>
