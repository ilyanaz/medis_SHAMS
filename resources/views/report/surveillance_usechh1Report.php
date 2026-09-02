<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>USECHH 1 Patient Details</title>
</head>
<body>
<?php
require dirname(__DIR__) . '/panel/navigation.php';

$pdfMode = ! empty($pdfMode);
$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$employee = $employeeData ?? (object) [];
$medicalHistory = $medicalHistoryData ?? (object) [];
$currentOccupational = $currentOccupationalData ?? (object) [];
$pastOccupationalRows = isset($pastOccupationalHistoryRows) && is_iterable($pastOccupationalHistoryRows) ? $pastOccupationalHistoryRows : [];
$personalSocialHistory = $personalSocialHistoryData ?? (object) [];
$trainingHistory = $trainingHistoryData ?? (object) [];
$workerName = trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? '')));
$identityNo = trim((string) (($employee->employee_NRIC ?? '') !== '' ? ($employee->employee_NRIC ?? '') : ($employee->employee_passportNo ?? '')));
$backUrl = function_exists('route') ? route('general.report') : 'general_report.php';
$printUrl = function_exists('route')
    ? route('pdf.usechh1.download', [
        'employee_id' => $employee->employee_id ?? request()->query('employee_id'),
        'company_id' => request()->query('company_id'),
        'declaration_id' => request()->query('declaration_id'),
        'surveillance_id' => request()->query('surveillance_id'),
    ])
    : 'PDF_USECHH1.php';
$query = request();
$declarationId = (int) ($surveillanceDeclarationId ?? $query->query('declaration_id', 0));
$employeeId = (int) ($surveillanceEmployeeId ?? $employee->employee_id ?? $query->query('employee_id', 0));
$companyId = (int) ($surveillanceCompanyId ?? $query->query('company_id', 0));
$surveillanceId = (int) ($surveillanceReportId ?? $query->query('surveillance_id', 0));

$showValue = static function ($value): string {
    $value = trim((string) ($value ?? ''));

    return $value !== '' ? $value : 'NA';
};

$showHistoryDetails = static function ($value): string {
    $value = trim((string) ($value ?? ''));

    return $value !== '' ? $value : 'NA';
};

$showNaValue = static function ($value): string {
    $value = trim((string) ($value ?? ''));

    return $value !== '' ? $value : 'NA';
};

$historyResult = static function (object $history, string $field) use ($showValue): string {
    $resultValue = trim((string) ($history->{$field . '_result'} ?? ''));
    $detailsValue = trim((string) ($history->{$field} ?? ''));

    if ($resultValue === '') {
        return $detailsValue !== '' ? 'Yes' : 'No';
    }

    $normalized = strtolower($resultValue);
    if (in_array($normalized, ['yes', 'no'], true)) {
        return ucfirst($normalized);
    }

    return $resultValue;
};

$formatDate = static function ($value): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return 'NA';
    }

    $timestamp = strtotime($value);

    return $timestamp ? date('d M Y', $timestamp) : $value;
};

$looksLikeImageSource = static function ($value): bool {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return false;
    }

    return str_starts_with($value, 'data:image')
        || str_starts_with($value, 'http://')
        || str_starts_with($value, 'https://')
        || str_starts_with($value, '/')
        || str_starts_with($value, '\\')
        || preg_match('/\.(png|jpg|jpeg|gif|webp|svg)$/i', $value) === 1;
};

$toSignatureDataUrl = static function ($value) use ($pdfMode, $looksLikeImageSource): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return '';
    }

    if ($pdfMode) {
        $localPath = public_path(ltrim($value, '/\\'));
        if (is_file($localPath)) {
            return $localPath;
        }
    }

    if ($looksLikeImageSource($value)) {
        if ($pdfMode && ! str_starts_with($value, 'data:image') && ! str_starts_with($value, 'http://') && ! str_starts_with($value, 'https://')) {
            $localPath = public_path(ltrim($value, '/\\'));
            return is_file($localPath) ? $localPath : $value;
        }
        return $value;
    }

    return 'data:image/png;base64,' . base64_encode($value);
};

$declaration = $declarationData ?? null;
$company = $companyData ?? null;
$doctor = $doctorData ?? null;
$doctorName = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
$doctorName = $doctorName !== '' ? $doctorName : trim((string) ($doctor->doctor_username ?? 'Doctor'));
$employeeSignature = $toSignatureDataUrl($declaration->employee_signature ?? '');
$doctorSignatureRaw = trim((string) ($doctorSignatureUrl ?? ''));
if ($doctorSignatureRaw === '') {
    $doctorSignatureRaw = trim((string) ($doctor->doctor_sign ?? ''));
}
if ($doctorSignatureRaw === '' || (! $looksLikeImageSource($doctorSignatureRaw) && ! is_file(public_path(ltrim($doctorSignatureRaw, '/\\'))))) {
    $doctorSignatureRaw = trim((string) ($declaration->doctor_signature ?? ''));
}
$doctorSignature = $toSignatureDataUrl($doctorSignatureRaw);

$chemicalInfo = $chemicalInfoData ?? null;
$historyOfHealth = $historyOfHealthData ?? null;
$clinicalFindings = $clinicalFindingsData ?? null;
$physicalExam = $physicalExamData ?? null;
$targetOrgan = $targetOrganData ?? null;
$biologicalMonitoring = $biologicalMonitoringData ?? null;
$fitnessRespirator = $fitnessRespiratorData ?? null;
$msFindings = $msFindingsData ?? null;
$recommendationData = $recommendationData ?? null;
$otherTargetTests = is_array($otherTargetTests ?? null) ? $otherTargetTests : [];

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
$historyGroups = [
    'Respiratory & Cardiovascular' => [
        'breathing_difficulty' => 'Breathing difficulty',
        'cough' => 'Cough',
        'sore_throat' => 'Sore throat',
        'sneezing' => 'Sneezing',
        'chest_pain' => 'Chest pain',
        'palpitation' => 'Palpitation',
        'limb_oedema' => 'Limb oedema',
    ],
    'Nervous System' => [
        'drowsiness' => 'Drowsiness',
        'dizziness' => 'Dizziness',
        'headache' => 'Headache',
        'confusion' => 'Confusion',
        'lethargy' => 'Lethargy',
        'nausea' => 'Nausea',
        'vomiting' => 'Vomiting',
    ],
    'Skin & Eyes' => [
        'eye_irritations' => 'Eye irritations',
        'blurred_vision' => 'Blurred vision',
        'blisters' => 'Blisters',
        'burns' => 'Burns',
        'itching' => 'Itching',
        'rash' => 'Rash',
        'redness' => 'Redness',
    ],
    'Gastrointestinal & Genitourinary' => [
        'abdominal_pain' => 'Abdominal pain',
        'history_abdominal_mass' => 'Abdominal mass',
        'history_jaundice' => 'Jaundice',
        'diarrhoea' => 'Diarrhoea',
        'loss_of_weight' => 'Loss of weight',
        'loss_of_appetite' => 'Loss of appetite',
        'dysuria' => 'Dysuria',
        'haematuria' => 'Haematuria',
    ],
];
$historyFieldColumnMap = [
    'history_abdominal_mass' => 'abdominal_mass',
    'history_jaundice' => 'jaundice',
];
$positiveSymptoms = [];
foreach ($historyLabels as $column => $label) {
    if (trim((string) ($historyOfHealth->{$column} ?? '')) === 'Yes') {
        $positiveSymptoms[] = $label;
    }
}

$biologicalRows = [];
$exposureRows = preg_split('/\r\n|\r|\n/', trim((string) ($biologicalMonitoring->biological_exposure ?? ''))) ?: [];
$baselineRows = preg_split('/\r\n|\r|\n/', trim((string) ($biologicalMonitoring->baseline_results ?? ''))) ?: [];
$annualRows = preg_split('/\r\n|\r|\n/', trim((string) ($biologicalMonitoring->baseline_annual ?? ''))) ?: [];
if ($exposureRows === [] || in_array(strtolower(trim((string) ($biologicalMonitoring->biological_exposure ?? ''))), ['yes', 'no'], true)) {
    $exposureRows = [];
    foreach ($baselineRows as $index => $baselineRow) {
        $parts = explode('::', $baselineRow, 2);
        $exposureRows[$index] = trim($parts[0] ?? '');
        $baselineRows[$index] = trim($parts[1] ?? '');
    }
}
$rowCount = max(count($exposureRows), count($baselineRows), count($annualRows));
for ($index = 0; $index < $rowCount; $index += 1) {
    $biologicalRows[] = [
        'exposure' => trim((string) ($exposureRows[$index] ?? '')),
        'baseline' => trim((string) ($baselineRows[$index] ?? '')),
        'annual' => trim((string) ($annualRows[$index] ?? '')),
    ];
}

$recommendationLines = array_values(array_filter(
    preg_split('/\r\n|\r|\n/', trim((string) ($recommendationData->recommencation_type ?? ''))) ?: [],
    static fn ($line) => trim((string) $line) !== ''
));

$recommendationEmployeeSignature = $toSignatureDataUrl($recommendationData->employee_signature ?? $declaration->employee_signature ?? '');
$recommendationAckDate = trim((string) ($recommendationData->employee_signature_date ?? $declaration->employee_date ?? ''));
$doctorRegistrationDisplay = trim((string) ($doctor->OHD_registrationNo ?? $doctor->MMC_no ?? ''));
$clinicDisplayName = trim((string) ($activeClinic->clinic_name ?? ($clinicName ?? '')));
$clinicTelephoneDisplay = trim((string) ($activeClinic->clinic_telephone ?? ''));
$clinicFaxDisplay = trim((string) ($activeClinic->clinic_fax ?? ''));
$clinicEmailDisplay = trim((string) ($activeClinic->clinic_email ?? ''));

$bmiCategory = static function ($value): string {
    $bmi = (float) trim((string) ($value ?? ''));
    if ($bmi <= 0) {
        return 'NA';
    }
    if ($bmi < 18.5) {
        return 'Underweight';
    }
    if ($bmi < 23) {
        return 'Normal';
    }
    if ($bmi < 30) {
        return 'Overweight';
    }

    return 'Obese';
};

$bpCategory = static function ($systolic, $diastolic): string {
    $sys = (int) trim((string) ($systolic ?? '0'));
    $dia = (int) trim((string) ($diastolic ?? '0'));
    if ($sys <= 0 || $dia <= 0) {
        return 'NA';
    }
    if ($sys >= 180 || $dia >= 120) {
        return 'Hypertensive Crisis';
    }
    if ($sys >= 140 || $dia >= 90) {
        return 'Hypertension Stage 2';
    }
    if ($sys >= 130 || $dia >= 80) {
        return 'Hypertension Stage 1';
    }
    if ($sys >= 120 && $dia < 80) {
        return 'Elevated';
    }

    return 'Normal';
};

$addressLines = array_values(array_filter([
    trim((string) ($employee->employee_address ?? '')),
    trim(implode(' ', array_values(array_filter([
        trim((string) ($employee->employee_postcode ?? '')),
        trim((string) ($employee->employee_district ?? '')),
    ], static fn ($value) => $value !== '')))),
    trim((string) ($employee->employee_state ?? '')),
], static fn ($value) => $value !== ''));
$fullAddress = $addressLines !== [] ? implode("\n", $addressLines) : '-';

medis_render_navigation_start([
    'clinicName' => $clinicName ?? 'Medis SHAMS',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'User',
    'active' => '',
    'pdfMode' => $pdfMode,
]);
?>
<style>
@page {
    size: A4 portrait;
    margin: 12mm;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    color: #111827;
    background: #ffffff;
    font-size: 14px;
}

.screen-shell {
    display: grid;
    gap: 20px;
    min-height: 100vh;
    padding: 0 0 28px;
}

.head-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

.head-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0 16px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    background: #fff;
    color: #374151;
    font: inherit;
    text-decoration: none;
    cursor: pointer;
}

.head-btn.primary {
    border-color: #389b5b;
    background: #389b5b;
    color: #fff;
    font-weight: 600;
}

.page-card {
    border: 0;
    border-radius: 0;
    background: #fff;
    overflow: visible;
}

.page-pad {
    padding: 0 0 28px;
}

.pdf-page {
    width: 100%;
}

.pdf-page + .pdf-page {
    margin-top: 24px;
}

.pdf-header img {
    display: block;
    width: 100%;
    max-width: 100%;
    height: auto;
}

.pdf-header {
    margin-bottom: 10px;
    padding-bottom: 10px;
}

.pdf-title {
    margin: 14px 0 18px;
    text-align: center;
    position: relative;
}

.pdf-title .law {
    font-size: 11px;
    font-weight: 700;
}

.pdf-title .main {
    margin-top: 7px;
    font-size: 16px;
    font-weight: 700;
}

.pdf-code {
    position: absolute;
    right: 0;
    top: 0;
    font-size: 11px;
    font-weight: 700;
}

.section-block {
    margin-top: 20px;
    break-inside: auto;
    page-break-inside: auto;
}

.section-title {
    margin: 0 0 12px;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    color: #1f2937;
    letter-spacing: .03em;
}

.section-note {
    margin: -2px 0 12px;
    color: #6b7280;
    font-size: 10px;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    page-break-inside: auto;
    break-inside: auto;
}

.report-table th,
.report-table td {
    border: 1px solid #d1d5db;
    padding: 8px 9px;
    font-size: 10.5px;
    vertical-align: top;
    text-align: left;
    word-wrap: break-word;
    overflow-wrap: anywhere;
    white-space: pre-line;
}

.report-table th {
    background: #f8fafc;
    font-weight: 700;
    color: #0f172a;
}

.report-table thead,
.exam-table thead {
    display: table-header-group;
}

.report-table tfoot,
.exam-table tfoot {
    display: table-row-group;
}

.report-table .label-cell {
    width: 21%;
    font-weight: 700;
    background: #fafafa;
}

.report-table .value-cell {
    width: 29%;
}

.report-table .status-cell {
    width: 11%;
    text-align: center;
    font-weight: 700;
}

.report-table.employee-table td {
    border-color: #dbe2ea;
}

.page-break {
    page-break-before: always;
    break-before: page;
}

.exam-table,
.declaration-summary-table,
.declaration-signature-table {
    page-break-inside: auto;
    break-inside: auto;
}

.exam-table tr,
.report-table tr,
.declaration-summary-table tr,
.declaration-signature-table tr {
    page-break-inside: avoid;
    break-inside: avoid;
}

.statement-box {
    font-size: 10.5px;
    line-height: 1.65;
}

.statement-copy {
    display: grid;
    gap: 12px;
}

.statement-copy strong {
    display: block;
    margin-bottom: 4px;
}

.declaration-summary-table,
.declaration-signature-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.declaration-summary-table {
    margin: 12px 0 14px;
}

.declaration-summary-table td {
    width: 50%;
    vertical-align: top;
    padding: 0 12px 0 0;
}

.declaration-summary-label {
    display: block;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
}

.declaration-summary-value {
    font-size: 10.5px;
    line-height: 1.45;
}

.declaration-signature-table {
    margin-top: 16px;
}

.declaration-signature-table td {
    width: 50%;
    vertical-align: top;
    padding: 0 10px;
}

.signature-card {
    padding: 0;
    min-height: 110px;
    text-align: center;
}

.signature-card-title {
    font-size: 11px;
    font-weight: 700;
    color: #0f172a;
    text-transform: uppercase;
}

.signature-box {
    min-height: 52px;
    display: block;
    padding: 6px 10px 4px;
    background: #fff;
    border: 0;
    text-align: center;
}

.signature-box img {
    max-width: 180px;
    width: auto;
    max-height: 54px;
    object-fit: contain;
}

.signature-meta {
    display: grid;
    gap: 2px;
    font-size: 10.5px;
    margin-top: 2px;
}

.exam-card {
    margin-top: 18px;
}

.exam-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.exam-table th,
.exam-table td {
    border: 1px solid #d1d5db;
    padding: 7px 8px;
    font-size: 10px;
    vertical-align: top;
    text-align: left;
    word-wrap: break-word;
    overflow-wrap: anywhere;
    white-space: pre-line;
}

.exam-table th {
    background: #f8fafc;
    font-weight: 700;
    color: #0f172a;
}

.pill-list {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.pill {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    font-size: 10px;
}

.acknowledgement-block {
    margin-top: 16px;
    display: grid;
    gap: 12px;
}

.acknowledgement-text {
    font-size: 10.5px;
    line-height: 1.5;
}

.ack-signature-box {
    min-height: 84px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px 0;
    background: #fff;
}

.ack-signature-box img {
    max-width: 220px;
    max-height: 72px;
    object-fit: contain;
}

.ack-line {
    display: grid;
    grid-template-columns: 240px minmax(0, 1fr);
    gap: 12px;
    font-size: 10.5px;
}

.ack-label {
    font-weight: 700;
}

.no-print {
    display: block;
}

@media print {
    html, body {
        width: 210mm;
        min-height: 297mm;
        margin: 0;
        padding: 0;
        background: #fff;
    }

    .app-topbar,
    .app-sidebar,
    .no-print {
        display: none !important;
    }

    .app-shell,
    .app-main,
    .app-page,
    .app-card {
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
        border: 0 !important;
        background: #fff !important;
        box-shadow: none !important;
        overflow: visible !important;
        height: auto !important;
    }

    .screen-shell {
        display: none !important;
    }

    .pdf-page {
        display: block !important;
    }
}
</style>

<?php if (! $pdfMode): ?>
    <div class="screen-shell no-print">
        <section style="padding:22px 24px 0;">
            <div class="head-actions">
                <a class="head-btn" href="<?php echo $esc($backUrl); ?>">Back</a>
                <a class="head-btn primary" href="<?php echo $esc($printUrl); ?>">Download PDF</a>
            </div>
        </section>
    </div>
<?php endif; ?>

<div class="pdf-page">
    <section class="page-card">
        <div class="page-pad">
            <div class="pdf-header">
                <?php require __DIR__ . '/partials/clinic_header.php'; ?>
            </div>

            <div class="pdf-title">
                <div class="pdf-code">USECHH 1</div>
                <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="law">Use and Standard of Exposure of Chemicals Hazardous to Health Regulations 2000</div>
                <div class="main">PATIENT DETAILS AND PATIENT INFORMATION</div>
            </div>

            <div class="section-block">
                <h2 class="section-title">Patient Details</h2>
                <table class="report-table employee-table">
                    <tbody>
                        <tr>
                            <td class="label-cell">Name</td>
                            <td class="value-cell"><?php echo $esc($showValue($workerName)); ?></td>
                            <td class="label-cell">NRIC / Passport</td>
                            <td class="value-cell"><?php echo $esc($showValue($identityNo)); ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">Date of Birth</td>
                            <td class="value-cell"><?php echo $esc($showValue($employee->employee_DOB ?? null)); ?></td>
                            <td class="label-cell">Gender</td>
                            <td class="value-cell"><?php echo $esc($showValue($employee->employee_gender ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">Telephone</td>
                            <td class="value-cell"><?php echo $esc($showValue($employee->employee_telephone ?? null)); ?></td>
                            <td class="label-cell">Email</td>
                            <td class="value-cell"><?php echo $esc($showValue($employee->employee_email ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">Address</td>
                            <td colspan="3"><?php echo $esc($fullAddress); ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">Ethnicity</td>
                            <td class="value-cell"><?php echo $esc($showValue($employee->employee_ethnicity ?? null)); ?></td>
                            <td class="label-cell">Citizenship</td>
                            <td class="value-cell"><?php echo $esc($showValue($employee->employee_citizenship ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">Marital Status</td>
                            <td class="value-cell"><?php echo $esc($showValue($employee->employee_martialStatus ?? null)); ?></td>
                            <td class="label-cell">No. of Children</td>
                            <td class="value-cell"><?php echo $esc($showValue($employee->no_of_children ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">Years Married</td>
                            <td class="value-cell"><?php echo $esc($showValue($employee->years_married ?? null)); ?></td>
                            <td class="label-cell">&nbsp;</td>
                            <td class="value-cell">&nbsp;</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-block">
                <h2 class="section-title">Medical History</h2>
                <div class="section-note">Patient information section from the surveillance examination record.</div>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width:21%;">&nbsp;</th>
                            <th style="width:11%;">Result</th>
                            <th colspan="2">History Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="label-cell">Diagnosed History</td>
                            <td class="status-cell"><?php echo $esc($historyResult($medicalHistory, 'diagnosed_history')); ?></td>
                            <td colspan="2"><?php echo $esc($showHistoryDetails($medicalHistory->diagnosed_history ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">Medication History</td>
                            <td class="status-cell"><?php echo $esc($historyResult($medicalHistory, 'medication_history')); ?></td>
                            <td colspan="2"><?php echo $esc($showHistoryDetails($medicalHistory->medication_history ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">Admitted History</td>
                            <td class="status-cell"><?php echo $esc($historyResult($medicalHistory, 'admitted_history')); ?></td>
                            <td colspan="2"><?php echo $esc($showHistoryDetails($medicalHistory->admitted_history ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">Family History</td>
                            <td class="status-cell"><?php echo $esc($historyResult($medicalHistory, 'family_history')); ?></td>
                            <td colspan="2"><?php echo $esc($showHistoryDetails($medicalHistory->family_history ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">Other History</td>
                            <td class="status-cell"><?php echo $esc($historyResult($medicalHistory, 'others_history')); ?></td>
                            <td colspan="2"><?php echo $esc($showHistoryDetails($medicalHistory->others_history ?? null)); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-block">
                <h2 class="section-title">Occupational and Company History</h2>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width:14%;">Record</th>
                            <th style="width:16%;">Job Title</th>
                            <th style="width:18%;">Company Name</th>
                            <th style="width:16%;">Employment Duration</th>
                            <th style="width:16%;">Chemical Exposure Duration</th>
                            <th style="width:20%;">Chemical Exposure Incidents</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Current Company</strong></td>
                            <td><?php echo $esc($showValue($currentOccupational->job_title ?? null)); ?></td>
                            <td><?php echo $esc($showValue($currentOccupational->company_name ?? null)); ?></td>
                            <td><?php echo $esc($showValue($currentOccupational->employment_duration ?? null)); ?></td>
                            <td><?php echo $esc($showValue($currentOccupational->chemical_exposure_duration ?? null)); ?></td>
                            <td><?php echo $esc($showValue($currentOccupational->chemical_exposure_incidents ?? null)); ?></td>
                        </tr>
                        <?php foreach ($pastOccupationalRows as $index => $row): ?>
                            <tr>
                                <td><strong><?php echo $esc('Past Company ' . ($index + 1)); ?></strong></td>
                                <td><?php echo $esc($showValue($row->job_title ?? null)); ?></td>
                                <td><?php echo $esc($showValue($row->company_name ?? null)); ?></td>
                                <td><?php echo $esc($showValue($row->employment_duration ?? null)); ?></td>
                                <td><?php echo $esc($showValue($row->chemical_exposure_duration ?? null)); ?></td>
                                <td><?php echo $esc($showValue($row->chemical_exposure_incidents ?? null)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<div class="pdf-page page-break">
    <section class="page-card">
        <div class="page-pad">
            <div class="pdf-header">
                <?php require __DIR__ . '/partials/clinic_header.php'; ?>
            </div>

            <div class="pdf-title">
                <div class="pdf-code">USECHH 1</div>
                <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="law">Use and Standard of Exposure of Chemicals Hazardous to Health Regulations 2000</div>
                <div class="main">PATIENT DETAILS AND PATIENT INFORMATION</div>
            </div>

            <div class="section-block">
                <h2 class="section-title">Personal and Social History</h2>
                <table class="report-table">
                    <tbody>
                        <tr>
                            <td class="label-cell">Smoking History</td>
                            <td class="value-cell"><?php echo $esc($showValue($personalSocialHistory->smoking_history ?? null)); ?></td>
                            <td class="label-cell">Vaping History</td>
                            <td class="value-cell"><?php echo $esc($showValue($personalSocialHistory->vaping_history ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">No. of Cigarettes</td>
                            <td class="value-cell"><?php echo $esc($showValue($personalSocialHistory->no_of_cigarettes ?? null)); ?></td>
                            <td class="label-cell">Years of Vaping</td>
                            <td class="value-cell"><?php echo $esc($showValue($personalSocialHistory->years_of_vaping ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td class="label-cell">Years of Smoking</td>
                            <td class="value-cell"><?php echo $esc($showValue($personalSocialHistory->years_of_smoking ?? null)); ?></td>
                            <td class="label-cell">Hobby</td>
                            <td class="value-cell"><?php echo $esc($showValue($personalSocialHistory->hobby ?? null)); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-block">
                <h2 class="section-title">Training History</h2>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width:34%;">Training Item</th>
                            <th style="width:16%;">Answer</th>
                            <th style="width:50%;">Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Handling of Chemical</strong></td>
                            <td><?php echo $esc($showValue($trainingHistory->handling_of_chemical ?? null)); ?></td>
                            <td><?php echo $esc($showValue($trainingHistory->chemical_comments ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Sign and Symptoms Knowledge</strong></td>
                            <td><?php echo $esc($showValue($trainingHistory->sign_symptoms ?? null)); ?></td>
                            <td><?php echo $esc($showValue($trainingHistory->sign_comments ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Chemical Poisoning Knowledge</strong></td>
                            <td><?php echo $esc($showValue($trainingHistory->chemical_poisoning ?? null)); ?></td>
                            <td><?php echo $esc($showValue($trainingHistory->poisoning_comments ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Proper PPE Knowledge</strong></td>
                            <td><?php echo $esc($showValue($trainingHistory->proper_PPE ?? null)); ?></td>
                            <td><?php echo $esc($showValue($trainingHistory->proper_comments ?? null)); ?></td>
                        </tr>
                        <tr>
                            <td><strong>PPE Usage</strong></td>
                            <td><?php echo $esc($showValue($trainingHistory->PPE_usage ?? null)); ?></td>
                            <td><?php echo $esc($showValue($trainingHistory->usage_comments ?? null)); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<div class="pdf-page page-break">
    <section class="page-card">
        <div class="page-pad">
            <div class="pdf-header">
                <?php require __DIR__ . '/partials/clinic_header.php'; ?>
            </div>

            <div class="pdf-title">
                <div class="pdf-code">USECHH 1</div>
                <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="law">Use and Standard of Exposure of Chemicals Hazardous to Health Regulations 2000</div>
                <div class="main">MEDICAL DECLARATION</div>
            </div>

            <table class="declaration-summary-table">
                <tr>
                    <td>
                        <span class="declaration-summary-label">Patient Name:</span>
                        <div class="declaration-summary-value"><?php echo $esc($showValue($workerName)); ?></div>
                    </td>
                    <td>
                        <span class="declaration-summary-label">Employer:</span>
                        <div class="declaration-summary-value"><?php echo $esc($showValue($company->company_name ?? null)); ?></div>
                    </td>
                </tr>
            </table>

            <div class="statement-box">
                <div class="statement-copy">
                    <div>
                        <strong>English:</strong>
                        <div>
                            This is to certify that the above statement is true. I hereby give consent to the Occupational Health Doctor (OHD)
                            to perform medical examination, necessary tests, and communicate with the employer the results of my medical examination
                            and work capability.
                        </div>
                    </div>
                    <div>
                        <strong>Bahasa Malaysia:</strong>
                        <div>
                            Ini adalah untuk mengesahkan bahawa kenyataan di atas adalah benar. Saya, dengan ini memberi persetujuan kepada Doktor
                            Kesihatan Pekerjaan (OHD) untuk melaksanakan pemeriksaan perubatan, ujian yang diperlukan, dan berkomunikasi dengan
                            majikan hasil pemeriksaan perubatan dan keupayaan kerja saya.
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-block">
                <table class="declaration-signature-table">
                    <tr>
                        <td>
                            <div class="signature-card">
                                <div class="signature-card-title">Patient Signature</div>
                                <div class="signature-box">
                                    <?php if ($employeeSignature !== ''): ?>
                                        <img src="<?php echo $esc($employeeSignature); ?>" alt="Patient signature">
                                    <?php endif; ?>
                                </div>
                                <div class="signature-meta">
                                    <div><strong>Date:</strong> <?php echo $esc($formatDate($declaration->employee_date ?? null)); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="signature-card">
                                <div class="signature-card-title">Doctor Signature</div>
                                <div class="signature-box">
                                    <?php if ($doctorSignature !== ''): ?>
                                        <img src="<?php echo $esc($doctorSignature); ?>" alt="Doctor signature">
                                    <?php endif; ?>
                                </div>
                                <div class="signature-meta">
                                    <div><strong>Date:</strong> <?php echo $esc($formatDate($declaration->doctor_date ?? null)); ?></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </section>
</div>

<div class="pdf-page page-break">
    <section class="page-card">
        <div class="page-pad">
            <div class="pdf-header">
                <?php require __DIR__ . '/partials/clinic_header.php'; ?>
            </div>
            <div class="pdf-title">
                <div class="pdf-code">USECHH 1</div>
                <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="law">Use and Standard of Exposure of Chemicals Hazardous to Health Regulations 2000</div>
                <div class="main">EXAMINATION RESULTS</div>
            </div>
            <div class="exam-card">
                <table class="exam-table">
                    <tbody>
                        <tr>
                            <th style="width:18%;">Patient Name</th>
                            <td style="width:32%;"><?php echo $esc($showValue($workerName)); ?></td>
                            <th style="width:18%;">Date Examined</th>
                            <td style="width:32%;"><?php echo $esc($formatDate($chemicalInfo->examination_date ?? $declaration->doctor_date ?? $declaration->employee_date ?? null)); ?></td>
                        </tr>
                        <tr>
                            <th>Company</th>
                            <td><?php echo $esc($showValue($company->company_name ?? null)); ?></td>
                            <th>Examination Type</th>
                            <td><?php echo $esc($showValue($chemicalInfo->examination_type ?? null)); ?></td>
                        </tr>
                        <tr>
                            <th>Chemical Name</th>
                            <td colspan="3"><?php echo $esc($showValue($chemicalInfo->chemicals ?? null)); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="section-block">
                <h2 class="section-title">History Of Health Effects Due To Chemical Exposure</h2>
                <?php foreach (['Respiratory & Cardiovascular', 'Nervous System', 'Skin & Eyes'] as $groupTitle): ?>
                    <?php $groupFields = $historyGroups[$groupTitle] ?? []; ?>
                    <table class="exam-table" style="margin-bottom:12px;">
                        <thead>
                            <tr>
                                <th colspan="4"><?php echo $esc($groupTitle); ?></th>
                            </tr>
                            <tr>
                                <th style="width:38%;">Category</th>
                                <th style="width:12%;">Result</th>
                                <th style="width:38%;">Category</th>
                                <th style="width:12%;">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $groupRows = array_values($groupFields); ?>
                            <?php $groupKeys = array_keys($groupFields); ?>
                            <?php for ($index = 0; $index < count($groupRows); $index += 2): ?>
                                <?php
                                $leftKey = $groupKeys[$index] ?? null;
                                $rightKey = $groupKeys[$index + 1] ?? null;
                                $leftColumn = $historyFieldColumnMap[$leftKey] ?? $leftKey;
                                $rightColumn = $historyFieldColumnMap[$rightKey] ?? $rightKey;
                                $leftValue = $leftKey !== null ? (trim((string) ($historyOfHealth->{$leftColumn} ?? '')) !== '' ? trim((string) ($historyOfHealth->{$leftColumn} ?? '')) : 'No') : 'NA';
                                $rightValue = $rightKey !== null ? (trim((string) ($historyOfHealth->{$rightColumn} ?? '')) !== '' ? trim((string) ($historyOfHealth->{$rightColumn} ?? '')) : 'No') : 'NA';
                                ?>
                                <tr>
                                    <td><?php echo $esc($groupRows[$index] ?? 'NA'); ?></td>
                                    <td><?php echo $esc($leftValue); ?></td>
                                    <td><?php echo $esc($groupRows[$index + 1] ?? 'NA'); ?></td>
                                    <td><?php echo $esc($rightValue); ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>

<div class="pdf-page page-break">
    <section class="page-card">
        <div class="page-pad">
            <div class="pdf-header">
                <?php require __DIR__ . '/partials/clinic_header.php'; ?>
            </div>
            <div class="pdf-title">
                <div class="pdf-code">USECHH 1</div>
                <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="law">Use and Standard of Exposure of Chemicals Hazardous to Health Regulations 2000</div>
                <div class="main">EXAMINATION RESULTS</div>
            </div>
            <div class="section-block">
                <h2 class="section-title">History Of Health Effects Due To Chemical Exposure</h2>
                <?php $groupFields = $historyGroups['Gastrointestinal & Genitourinary'] ?? []; ?>
                <table class="exam-table" style="margin-bottom:12px;">
                    <thead>
                        <tr>
                            <th colspan="4">Gastrointestinal &amp; Genitourinary</th>
                        </tr>
                        <tr>
                            <th style="width:38%;">Category</th>
                            <th style="width:12%;">Result</th>
                            <th style="width:38%;">Category</th>
                            <th style="width:12%;">Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $groupRows = array_values($groupFields); ?>
                        <?php $groupKeys = array_keys($groupFields); ?>
                        <?php for ($index = 0; $index < count($groupRows); $index += 2): ?>
                            <?php
                            $leftKey = $groupKeys[$index] ?? null;
                            $rightKey = $groupKeys[$index + 1] ?? null;
                            $leftColumn = $historyFieldColumnMap[$leftKey] ?? $leftKey;
                            $rightColumn = $historyFieldColumnMap[$rightKey] ?? $rightKey;
                            $leftValue = $leftKey !== null ? (trim((string) ($historyOfHealth->{$leftColumn} ?? '')) !== '' ? trim((string) ($historyOfHealth->{$leftColumn} ?? '')) : 'No') : 'NA';
                            $rightValue = $rightKey !== null ? (trim((string) ($historyOfHealth->{$rightColumn} ?? '')) !== '' ? trim((string) ($historyOfHealth->{$rightColumn} ?? '')) : 'No') : 'NA';
                            ?>
                            <tr>
                                <td><?php echo $esc($groupRows[$index] ?? 'NA'); ?></td>
                                <td><?php echo $esc($leftValue); ?></td>
                                <td><?php echo $esc($groupRows[$index + 1] ?? 'NA'); ?></td>
                                <td><?php echo $esc($rightValue); ?></td>
                            </tr>
                        <?php endfor; ?>
                        <tr>
                            <th>Others</th>
                            <td colspan="3"><?php echo $esc($showValue($historyOfHealth->others_symptoms ?? null)); ?></td>
                        </tr>
                    </tbody>
                </table>
                <table class="exam-table">
                    <thead>
                        <tr>
                            <th colspan="4">Clinical Findings</th>
                        </tr>
                        <tr>
                            <th style="width:24%;">Finding</th>
                            <th style="width:16%;">Result</th>
                            <th colspan="2">Describe Current Health Effects</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Clinical Findings</td>
                            <td><?php echo $esc($showValue($clinicalFindings->result_clinical_findings ?? null)); ?></td>
                            <td colspan="2"><?php echo $esc($showNaValue($clinicalFindings->elaboration ?? null)); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="section-block">
                <h2 class="section-title">Physical Examination</h2>
                <table class="exam-table">
                    <thead>
                        <tr>
                            <th style="width:24%;">Area / System</th>
                            <th style="width:24%;">Finding</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td rowspan="3">Anthropometry</td><td>Weight (kg)</td><td><?php echo $esc($showValue($physicalExam->weight ?? null)); ?></td></tr>
                        <tr><td>Height (cm)</td><td><?php echo $esc($showValue($physicalExam->height ?? null)); ?></td></tr>
                        <tr><td>BMI</td><td><?php echo $esc($showValue($physicalExam->BMI ?? null) . ' (' . $bmiCategory($physicalExam->BMI ?? null) . ')'); ?></td></tr>
                        <tr><td rowspan="3">Vital Signs</td><td>Blood Pressure (mm/Hg)</td><td><?php echo $esc($showValue($physicalExam->bp_systolic ?? null) . '/' . $showValue($physicalExam->bp_distolic ?? null) . ' (' . $bpCategory($physicalExam->bp_systolic ?? null, $physicalExam->bp_distolic ?? null) . ')'); ?></td></tr>
                        <tr><td>Pulse Rate (bpm)</td><td><?php echo $esc($showValue($physicalExam->pulse_rate ?? null)); ?></td></tr>
                        <tr><td>Respiratory Rate</td><td><?php echo $esc($showValue($physicalExam->respiratory_rate ?? null)); ?></td></tr>
                        <tr><td>General Appearances</td><td>Finding</td><td><?php echo $esc($showValue($physicalExam->general_appearances ?? null)); ?></td></tr>
                        <tr><td rowspan="2">Cardiovascular System</td><td>S1 &amp; S2</td><td><?php echo $esc($showValue($physicalExam->s1_s2 ?? null)); ?></td></tr>
                        <tr><td>Murmur</td><td><?php echo $esc($showValue($physicalExam->murmur ?? null)); ?></td></tr>
                        <tr><td>Ear, Nose and Throat</td><td>ENT Findings</td><td><?php echo $esc($showValue($physicalExam->ear_nose_throat ?? null)); ?></td></tr>
                        <tr><td rowspan="2">Eyes</td><td>Visual Acuity</td><td><?php echo $esc('R: ' . $showNaValue($physicalExam->visual_acuity_right ?? null) . ' | L: ' . $showNaValue($physicalExam->visual_acuity_left ?? null)); ?></td></tr>
                        <tr><td>Colour Blindness</td><td><?php echo $esc($showValue($physicalExam->colour_blindness ?? null)); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<div class="pdf-page page-break">
    <section class="page-card">
        <div class="page-pad">
            <div class="pdf-header">
                <?php require __DIR__ . '/partials/clinic_header.php'; ?>
            </div>
            <div class="pdf-title">
                <div class="pdf-code">USECHH 1</div>
                <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="law">Use and Standard of Exposure of Chemicals Hazardous to Health Regulations 2000</div>
                <div class="main">EXAMINATION RESULTS</div>
            </div>
            <div class="section-block">
                <table class="exam-table">
                    <thead>
                        <tr>
                            <th style="width:24%;">Area / System</th>
                            <th style="width:24%;">Finding</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td rowspan="2">Haematology</td><td>Lymph Nodes</td><td><?php echo $esc($showValue($physicalExam->lymph_nodes ?? null)); ?></td></tr>
                        <tr><td>Splenomegaly</td><td><?php echo $esc($showValue($physicalExam->splenomegaly ?? null)); ?></td></tr>
                        <tr><td rowspan="2">Kidney</td><td>Tenderness</td><td><?php echo $esc($showValue($physicalExam->kidney_tenderness ?? null)); ?></td></tr>
                        <tr><td>Ballotable</td><td><?php echo $esc($showValue($physicalExam->ballotable ?? null)); ?></td></tr>
                        <tr><td rowspan="2">Liver</td><td>Jaundice</td><td><?php echo $esc($showValue($physicalExam->jaundice ?? null)); ?></td></tr>
                        <tr><td>Hepatomegaly</td><td><?php echo $esc($showValue($physicalExam->hepatomegaly ?? null)); ?></td></tr>
                        <tr><td rowspan="2">Musculoskeletal</td><td>Muscle Tone</td><td><?php echo $esc($showValue($physicalExam->muscle_tone ?? null)); ?></td></tr>
                        <tr><td>Muscle Tenderness</td><td><?php echo $esc($showValue($physicalExam->muscle_tenderness ?? null)); ?></td></tr>
                        <tr><td>Nervous System</td><td>Power</td><td><?php echo $esc($showValue($physicalExam->power ?? null)); ?></td></tr>
                        <tr><td>Nervous System</td><td>Sensation</td><td><?php echo $esc($showValue($physicalExam->sensation ?? null)); ?></td></tr>
                        <tr><td rowspan="2">Respiratory</td><td>Sound</td><td><?php echo $esc($showValue($physicalExam->sound ?? null)); ?></td></tr>
                        <tr><td>Air Entry</td><td><?php echo $esc($showValue($physicalExam->air_entry ?? null)); ?></td></tr>
                        <tr><td>Reproductive</td><td>Finding</td><td><?php echo $esc($showValue($physicalExam->reproductive ?? null)); ?></td></tr>
                        <tr><td>Skin</td><td>Finding</td><td><?php echo $esc($showValue($physicalExam->skin ?? null)); ?></td></tr>
                        <tr><td>Others</td><td>Finding</td><td><?php echo $esc($showNaValue($physicalExam->others ?? null)); ?></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="section-block">
                <h2 class="section-title">Target Organ Function Test</h2>
                <table class="exam-table">
                    <thead>
                        <tr>
                            <th style="width:28%;">Test</th>
                            <th style="width:24%;">Result</th>
                            <th>Comments / Findings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Full Blood Count</td><td><?php echo $esc($showValue($targetOrgan->blood_count_result ?? null)); ?></td><td><?php echo $esc($showValue($targetOrgan->blood_comments ?? null)); ?></td></tr>
                        <tr><td>Renal Function Test</td><td><?php echo $esc($showValue($targetOrgan->renal_function_result ?? null)); ?></td><td><?php echo $esc($showValue($targetOrgan->renal_comments ?? null)); ?></td></tr>
                        <tr><td>Liver Function Test</td><td><?php echo $esc($showValue($targetOrgan->liver_function_result ?? null)); ?></td><td><?php echo $esc($showValue($targetOrgan->liver_comments ?? null)); ?></td></tr>
                        <tr><td>Chest X-ray</td><td><?php echo $esc($showValue($targetOrgan->chest_xray_result ?? null)); ?></td><td><?php echo $esc($showValue($targetOrgan->chest_comments ?? null)); ?></td></tr>
                        <?php foreach ($otherTargetTests as $targetTest): ?>
                            <tr>
                                <td><?php echo $esc($showValue($targetTest['test_name'] ?? null)); ?></td>
                                <td><?php echo $esc($showValue($targetTest['result'] ?? null)); ?></td>
                                <td><?php echo $esc($showValue($targetTest['comments'] ?? null)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr><td>Spirometry FEV1</td><td><?php echo $esc($showValue($targetOrgan->spirometry_FEV1 ?? null)); ?></td><td><?php echo $esc($showValue($targetOrgan->spirometry_comments ?? null)); ?></td></tr>
                        <tr><td>Spirometry FVC</td><td><?php echo $esc($showValue($targetOrgan->spirometry_FVC ?? null)); ?></td><td>NA</td></tr>
                        <tr><td>Spirometry FEV/FVC</td><td><?php echo $esc($showValue($targetOrgan->spirometry_FEV_FVC ?? null)); ?></td><td>NA</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<div class="pdf-page page-break">
    <section class="page-card">
        <div class="page-pad">
            <div class="pdf-header">
                <?php require __DIR__ . '/partials/clinic_header.php'; ?>
            </div>
            <div class="pdf-title">
                <div class="pdf-code">USECHH 1</div>
                <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="law">Use and Standard of Exposure of Chemicals Hazardous to Health Regulations 2000</div>
                <div class="main">EXAMINATION RESULTS</div>
            </div>
            <div class="section-block">
                <h2 class="section-title">Biological Monitoring</h2>
                <table class="exam-table">
                    <thead>
                        <tr>
                            <th style="width:34%;">Biological Exposure Indices / Determinants</th>
                            <th style="width:33%;">Baseline</th>
                            <th style="width:33%;">Annual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (($biologicalMonitoring->manual_completed ?? false)): ?>
                            <tr><td>NA</td><td>NA</td><td>NA</td></tr>
                        <?php elseif ($biologicalRows !== []): ?>
                            <?php foreach ($biologicalRows as $biologicalRow): ?>
                                <tr>
                                    <td><?php echo $esc($showValue($biologicalRow['exposure'] ?? null)); ?></td>
                                    <td><?php echo $esc($showValue($biologicalRow['baseline'] ?? null)); ?></td>
                                    <td><?php echo $esc($showValue($biologicalRow['annual'] ?? null)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td>NA</td><td>NA</td><td>NA</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="section-block">
                <h2 class="section-title">Fitness To Wear Respirator</h2>
                <table class="exam-table">
                    <thead>
                        <tr>
                            <th style="width:35%;">Conclusion On Fitness To Wear Respirator</th>
                            <th style="width:20%;">Result</th>
                            <th>Justification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Respirator Fitness</td>
                            <td><?php echo $esc($showValue($fitnessRespirator->fitness_result ?? null)); ?></td>
                            <td><?php echo $esc($showNaValue($fitnessRespirator->fitness_justification ?? null)); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="section-block">
                <h2 class="section-title">MS Findings</h2>
                <table class="exam-table">
                    <thead>
                        <tr>
                            <th>MS Finding</th>
                            <th style="width:10%;">Yes</th>
                            <th style="width:10%;">No</th>
                            <th style="width:14%;">Work Related Yes</th>
                            <th style="width:14%;">Work Related No</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>History Of Health Effects Due To Chemical Exposure</td>
                            <td><?php echo $esc((string) ($msFindings->history_of_health ?? '') === 'Yes' ? 'Yes' : ''); ?></td>
                            <td><?php echo $esc((string) ($msFindings->history_of_health ?? '') === 'No' ? 'No' : ''); ?></td>
                            <td colspan="2">Not applicable</td>
                        </tr>
                        <tr>
                            <td>Clinical Findings</td>
                            <td><?php echo $esc((string) ($msFindings->clinical_findings ?? '') === 'Yes' ? 'Yes' : ''); ?></td>
                            <td><?php echo $esc((string) ($msFindings->clinical_findings ?? '') === 'No' ? 'No' : ''); ?></td>
                            <td><?php echo $esc((string) ($msFindings->CF_work_related ?? '') === 'Yes' ? 'Yes' : ''); ?></td>
                            <td><?php echo $esc((string) ($msFindings->CF_work_related ?? '') === 'No' ? 'No' : ''); ?></td>
                        </tr>
                        <tr>
                            <td>Target Organ Function Test Results</td>
                            <td><?php echo $esc((string) ($msFindings->target_organ ?? '') === 'Yes' ? 'Yes' : ''); ?></td>
                            <td><?php echo $esc((string) ($msFindings->target_organ ?? '') === 'No' ? 'No' : ''); ?></td>
                            <td><?php echo $esc((string) ($msFindings->TO_work_related ?? '') === 'Yes' ? 'Yes' : ''); ?></td>
                            <td><?php echo $esc((string) ($msFindings->TO_work_related ?? '') === 'No' ? 'No' : ''); ?></td>
                        </tr>
                        <tr>
                            <td>BEI Determinant (BM/BEM)</td>
                            <td><?php echo $esc((string) ($msFindings->biological_monitoring ?? '') === 'Yes' ? 'Yes' : ''); ?></td>
                            <td><?php echo $esc((string) ($msFindings->biological_monitoring ?? '') === 'No' ? 'No' : ''); ?></td>
                            <td><?php echo $esc((string) ($msFindings->BM_work_related ?? '') === 'Yes' ? 'Yes' : ''); ?></td>
                            <td><?php echo $esc((string) ($msFindings->BM_work_related ?? '') === 'No' ? 'No' : ''); ?></td>
                        </tr>
                        <tr>
                            <td>Pregnancy / Breastfeeding</td>
                            <td><?php echo $esc((string) ($msFindings->pregnancy_breastFeding ?? '') === 'Yes' ? 'Yes' : ''); ?></td>
                            <td><?php echo $esc((string) ($msFindings->pregnancy_breastFeding ?? '') === 'No' ? 'No' : ''); ?></td>
                            <td colspan="2">Not applicable</td>
                        </tr>
                    </tbody>
                </table>
                <table class="exam-table" style="margin-top:12px;">
                    <tbody>
                        <tr>
                            <th style="width:28%;">Conclusion Of Fitness To Work</th>
                            <td><?php echo $esc($showValue($msFindings->conclusion_fitness ?? null)); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<div class="pdf-page page-break">
    <section class="page-card">
        <div class="page-pad">
            <div class="pdf-header">
                <?php require __DIR__ . '/partials/clinic_header.php'; ?>
            </div>
            <div class="pdf-title">
                <div class="pdf-code">USECHH 1</div>
                <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="law">Use and Standard of Exposure of Chemicals Hazardous to Health Regulations 2000</div>
                <div class="main">RECOMMENDATION</div>
            </div>
            <div class="section-block">
                <table class="exam-table">
                    <thead>
                        <tr>
                            <th style="width:24%;">Recommendation Label</th>
                            <th>Recommendation Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Recommendation Type</td><td><?php echo $esc($recommendationLines !== [] ? implode("\n", $recommendationLines) : 'NA'); ?></td></tr>
                        <tr><td>MRP Start Date</td><td><?php echo $esc($formatDate($recommendationData->MRPdate_start ?? null)); ?></td></tr>
                        <tr><td>MRP End Date</td><td><?php echo $esc($formatDate($recommendationData->MRPdate_end ?? null)); ?></td></tr>
                        <tr><td>Next Review Date</td><td><?php echo $esc($formatDate($recommendationData->nextReview_date ?? null)); ?></td></tr>
                        <tr><td>Notes</td><td><?php echo $esc($showValue($recommendationData->notes ?? null)); ?></td></tr>
                    </tbody>
                </table>
                <div class="acknowledgement-block">
                    <div class="acknowledgement-text">The implication of the above results has been explained to me by the OHD.</div>
                    <div class="acknowledgement-text">Signature of the patient:</div>
                    <div class="ack-signature-box">
                        <?php if ($recommendationEmployeeSignature !== ''): ?>
                            <img src="<?php echo $esc($recommendationEmployeeSignature); ?>" alt="Patient acknowledgement signature">
                        <?php endif; ?>
                    </div>
                    <div class="acknowledgement-text"><strong>Date:</strong> <?php echo $esc($formatDate($recommendationAckDate)); ?></div>
                    <div class="ack-line"><div class="ack-label">Name of Occupational Health Doctor</div><div><?php echo $esc($showNaValue($doctorName)); ?></div></div>
                    <div class="ack-line"><div class="ack-label">Name of Clinic</div><div><?php echo $esc($showNaValue($clinicDisplayName)); ?></div></div>
                    <div class="ack-line"><div class="ack-label">MMC / OHD Registration No.</div><div><?php echo $esc($showNaValue($doctorRegistrationDisplay)); ?></div></div>
                    <div class="ack-line"><div class="ack-label">Clinic Tel. No.</div><div><?php echo $esc($showNaValue($clinicTelephoneDisplay)); ?></div></div>
                    <div class="ack-line"><div class="ack-label">Fax No.</div><div><?php echo $esc($showNaValue($clinicFaxDisplay)); ?></div></div>
                    <div class="ack-line"><div class="ack-label">Email</div><div><?php echo $esc($showNaValue($clinicEmailDisplay)); ?></div></div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php medis_render_navigation_end(); ?>
</body>
</html>
