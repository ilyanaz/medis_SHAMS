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
$groupChemical = trim((string) $query->query('group_chemical', ''));
$groupDate = trim((string) $query->query('group_date', ''));

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
$isAbnormal = false;
if ($findings) {
    $isAbnormal = in_array((string) ($findings->conclusion_fitness ?? ''), ['Not Fit', 'Abnormal'], true)
        || in_array((string) ($findings->history_of_health ?? ''), ['Yes', 'Abnormal'], true)
        || in_array((string) ($findings->clinical_findings ?? ''), ['Yes', 'Abnormal'], true)
        || in_array((string) ($findings->target_organ ?? ''), ['Yes', 'Abnormal'], true)
        || in_array((string) ($findings->biological_monitoring ?? ''), ['Yes', 'Abnormal'], true)
        || in_array((string) ($findings->CF_work_related ?? ''), ['Yes', 'Abnormal'], true)
        || in_array((string) ($findings->TO_work_related ?? ''), ['Yes', 'Abnormal'], true)
        || in_array((string) ($findings->BM_work_related ?? ''), ['Yes', 'Abnormal'], true)
        || in_array((string) ($findings->pregnancy_breastFeding ?? ''), ['Yes', 'Abnormal'], true);
}

$targetResultValue = static function (?object $targetOrganRecord, string $resultColumn): string {
    if (! $targetOrganRecord) {
        return '';
    }

    return trim((string) ($targetOrganRecord->{$resultColumn} ?? ''));
};
$abnormalTargetSummary = array_filter([
    $targetResultValue($targetOrgan, 'blood_count_result') === 'Abnormal' ? 'Full Blood Count' : null,
    $targetResultValue($targetOrgan, 'renal_function_result') === 'Abnormal' ? 'Renal Function Test' : null,
    $targetResultValue($targetOrgan, 'liver_function_result') === 'Abnormal' ? 'Liver Function Test' : null,
    $targetResultValue($targetOrgan, 'chest_xray_result') === 'Abnormal' ? 'Chest X-ray' : null,
    ! empty($targetOrgan->spirometry_FEV_FVC) ? 'Spirometry' : null,
]);
$otherTargetTests = [];
if ($surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('target_organ_other_tests')) {
    $otherTargetTests = DB::table('target_organ_other_tests')
        ->where('surveillance_id', $surveillanceId)
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
if ($otherTargetTests === [] && ! empty($targetOrgan->other_tests ?? null)) {
    $decodedOtherTargetTests = json_decode((string) $targetOrgan->other_tests, true);
    if (is_array($decodedOtherTargetTests)) {
        $otherTargetTests = $decodedOtherTargetTests;
    }
}
if ($otherTargetTests !== []) {
    foreach ($otherTargetTests as $otherTargetTest) {
        $testName = trim((string) ($otherTargetTest['name'] ?? ''));
        $testResult = trim((string) ($otherTargetTest['result'] ?? ''));
        if ($testName !== '' && $testResult === 'Abnormal') {
            $abnormalTargetSummary[] = $testName;
        }
    }
}

$buildAbnormalRow = static function (
    ?object $employeeRecord,
    ?object $chemicalRecord,
    ?object $findingsRecord,
    ?object $targetOrganRecord,
    ?object $biologicalRecord,
    ?object $removalRecord,
    array $otherTargetTestRows,
    string $departmentName
): array {
    $targetResultValue = static function (?object $targetOrganInnerRecord, string $resultColumn): string {
        if (! $targetOrganInnerRecord) {
            return '';
        }

        return trim((string) ($targetOrganInnerRecord->{$resultColumn} ?? ''));
    };

    $targetSummary = array_filter([
        $targetResultValue($targetOrganRecord, 'blood_count_result') === 'Abnormal' ? 'Full Blood Count' : null,
        $targetResultValue($targetOrganRecord, 'renal_function_result') === 'Abnormal' ? 'Renal Function Test' : null,
        $targetResultValue($targetOrganRecord, 'liver_function_result') === 'Abnormal' ? 'Liver Function Test' : null,
        $targetResultValue($targetOrganRecord, 'chest_xray_result') === 'Abnormal' ? 'Chest X-ray' : null,
        ! empty($targetOrganRecord->spirometry_FEV_FVC) ? 'Spirometry' : null,
    ]);

    foreach ($otherTargetTestRows as $otherTargetTestRow) {
        $testName = trim((string) ($otherTargetTestRow['name'] ?? ''));
        $testResult = trim((string) ($otherTargetTestRow['result'] ?? ''));
        if ($testName !== '' && $testResult === 'Abnormal') {
            $targetSummary[] = $testName;
        }
    }

    return [
        'worker' => trim((string) (($employeeRecord->employee_firstName ?? '') . ' ' . ($employeeRecord->employee_lastName ?? ''))),
        'chemical' => (string) ($chemicalRecord->chemicals ?? '-'),
        'job' => (string) ($chemicalRecord->examination_type ?? 'Medical surveillance'),
        'history' => (string) ($findingsRecord->history_of_health ?? 'No'),
        'clinical' => (string) ($findingsRecord->clinical_findings ?? 'No'),
        'target' => implode(', ', $targetSummary),
        'bei' => (string) ($biologicalRecord->baseline_annual ?? $biologicalRecord->baseline_results ?? '-'),
        'work' => trim(implode(' / ', array_filter([
            isset($findingsRecord->CF_work_related) ? 'CF: ' . $findingsRecord->CF_work_related : null,
            isset($findingsRecord->TO_work_related) ? 'TO: ' . $findingsRecord->TO_work_related : null,
            isset($findingsRecord->BM_work_related) ? 'BM: ' . $findingsRecord->BM_work_related : null,
        ]))),
        'protection' => (string) ($removalRecord->removal_type ?? 'Monitoring'),
        'department' => $departmentName !== '' ? $departmentName : '-',
    ];
};

$rows = [];

if ($groupChemical !== '' && $groupDate !== '' && $companyId > 0 && DB::getSchemaBuilder()->hasTable('declaration')) {
    $candidateDeclarations = DB::table('declaration')
        ->leftJoin('chemical_information', 'chemical_information.surveillance_id', '=', 'declaration.surveillance_id')
        ->where('declaration.company_id', $companyId)
        ->where('chemical_information.chemicals', $groupChemical)
        ->where(function ($builder) use ($groupDate): void {
            $builder->whereDate('declaration.employee_date', $groupDate)
                ->orWhere(function ($fallback) use ($groupDate): void {
                    $fallback->whereNull('declaration.employee_date')
                        ->whereDate('declaration.doctor_date', $groupDate);
                });
        })
        ->orderBy('declaration.declaration_id')
        ->get(['declaration.declaration_id', 'declaration.employee_id', 'declaration.company_id', 'declaration.surveillance_id']);

    foreach ($candidateDeclarations as $candidateDeclaration) {
        $candidateFindings = DB::getSchemaBuilder()->hasTable('ms_findings')
            ? DB::table('ms_findings')->where('surveillance_id', $candidateDeclaration->surveillance_id)->first()
            : null;

        if (! $candidateFindings) {
            continue;
        }

        $candidateIsAbnormal = in_array((string) ($candidateFindings->conclusion_fitness ?? ''), ['Not Fit', 'Abnormal'], true)
            || in_array((string) ($candidateFindings->history_of_health ?? ''), ['Yes', 'Abnormal'], true)
            || in_array((string) ($candidateFindings->clinical_findings ?? ''), ['Yes', 'Abnormal'], true)
            || in_array((string) ($candidateFindings->target_organ ?? ''), ['Yes', 'Abnormal'], true)
            || in_array((string) ($candidateFindings->biological_monitoring ?? ''), ['Yes', 'Abnormal'], true)
            || in_array((string) ($candidateFindings->CF_work_related ?? ''), ['Yes', 'Abnormal'], true)
            || in_array((string) ($candidateFindings->TO_work_related ?? ''), ['Yes', 'Abnormal'], true)
            || in_array((string) ($candidateFindings->BM_work_related ?? ''), ['Yes', 'Abnormal'], true)
            || in_array((string) ($candidateFindings->pregnancy_breastFeding ?? ''), ['Yes', 'Abnormal'], true);

        if (! $candidateIsAbnormal) {
            continue;
        }

        $candidateEmployee = DB::table('employee')->where('employee_id', $candidateDeclaration->employee_id)->first();
        $candidateChemical = DB::table('chemical_information')->where('surveillance_id', $candidateDeclaration->surveillance_id)->first();
        $candidateTargetOrgan = DB::getSchemaBuilder()->hasTable('target_organ')
            ? DB::table('target_organ')->where('surveillance_id', $candidateDeclaration->surveillance_id)->first()
            : null;
        $candidateBiological = DB::getSchemaBuilder()->hasTable('biological_monitoring')
            ? DB::table('biological_monitoring')->where('surveillance_id', $candidateDeclaration->surveillance_id)->first()
            : null;
        $candidateRemoval = DB::getSchemaBuilder()->hasTable('removal_report')
            ? DB::table('removal_report')->where('surveillance_id', $candidateDeclaration->surveillance_id)->first()
            : null;
        $candidateOtherTargetTests = DB::getSchemaBuilder()->hasTable('target_organ_other_tests')
            ? DB::table('target_organ_other_tests')
                ->where('surveillance_id', $candidateDeclaration->surveillance_id)
                ->orderBy('sort_order')
                ->orderBy('other_target_test_id')
                ->get(['test_name', 'result', 'comments'])
                ->map(static fn ($row) => [
                    'name' => trim((string) ($row->test_name ?? '')),
                    'result' => trim((string) ($row->result ?? '')),
                    'comments' => trim((string) ($row->comments ?? '')),
                ])
                ->values()
                ->all()
            : [];

        $rows[] = $buildAbnormalRow(
            $candidateEmployee,
            $candidateChemical,
            $candidateFindings,
            $candidateTargetOrgan,
            $candidateBiological,
            $candidateRemoval,
            $candidateOtherTargetTests,
            (string) ($company->company_name ?? '')
        );
    }
} elseif ($isAbnormal) {
    $rows[] = $buildAbnormalRow(
        $employee,
        $chemical,
        $findings,
        $targetOrgan,
        $biological,
        $removal,
        $otherTargetTests,
        (string) ($company->company_name ?? '')
    );
}
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
.clinic-report-header{padding:0 0 8px}
.clinic-report-header img{display:block;width:100%;max-width:100%;max-height:none;height:auto;object-fit:contain}
.report-card{background:#fff;overflow:hidden}
.report-head{padding:6px 0 14px;border-bottom:2px solid #dce8de}
.report-head-top{position:relative;display:block;text-align:center}
.report-code{position:absolute;right:0;top:0;font-size:14px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#0f172a}
.report-head-act{font-size:14px;font-weight:700;line-height:1.35}
.report-head-regulation{margin-top:4px;font-size:15px;font-weight:700;line-height:1.35}
.report-title{margin:12px 0 0;text-align:center;font-size:18px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.table-wrap{padding:18px 0 0}
.abnormal-table{width:100%;border-collapse:collapse;table-layout:fixed}
.abnormal-table th,.abnormal-table td{border:1px solid #c9d8ea;padding:8px 7px;vertical-align:top;font-size:11.5px;line-height:1.45;text-align:left;word-break:break-word}
.abnormal-table th{background:#fff;color:#0f172a;font-weight:700}
.foot-note{padding:14px 0 0;color:#5f6f65;font-size:.84rem;line-height:1.5}
.empty-state{padding:24px 0 8px;color:#5f6f65;font-size:.95rem;line-height:1.6}
.print-only{display:none}
@media print{body{padding:0}.report-card{break-inside:avoid}.print-only{display:block}}
</style>
<div class="sheet">
    <div class="print-only"><?php require __DIR__ . '/partials/clinic_header.php'; ?></div>
    <section class="report-card">
        <div class="report-head">
            <div class="report-head-top">
                <div class="report-code">USECHH 5ii</div>
                <div class="report-head-act">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="report-head-regulation">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                <h1 class="report-title">Workers With Abnormal Examination Results</h1>
            </div>
        </div>

        <?php if (! empty($rows)): ?>
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
        <?php else: ?>
            <div class="empty-state">
                No abnormal examination result is recorded for this surveillance entry. USECHH 5ii is only generated when the medical surveillance findings show an abnormal outcome.
            </div>
        <?php endif; ?>

        <div class="foot-note">
            Submit this landscape summary together with the related medical surveillance findings when documenting workers with abnormal examination results.
        </div>
    </section>
</div>
</body>
</html>
