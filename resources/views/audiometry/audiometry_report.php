<?php
declare(strict_types=1);

use Carbon\Carbon;

$esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$selectedEmployee = $selectedEmployee ?? null;
$selectedCompany = $selectedCompany ?? null;
$currentOccupationalHistory = $currentOccupationalHistory ?? null;
$audiometryTest = $audiometryTest ?? null;
$pastMedical = $pastMedical ?? null;
$audioComments = $audioComments ?? null;
$calculatedAverages = $calculatedAverages ?? [];
$doctor = $doctor ?? null;
$doctorSignatureUrl = $doctorSignatureUrl ?? null;
$doctorSignatureName = $doctorSignatureName ?? 'Doctor';
$isAudiometryAbnormal = ! empty($isAudiometryAbnormal);

$employeeName = trim((string) (($selectedEmployee->employee_firstName ?? '') . ' ' . ($selectedEmployee->employee_lastName ?? '')));
$employeeIdentity = trim((string) (($selectedEmployee->employee_NRIC ?? '') ?: ($selectedEmployee->employee_passportNo ?? '')));
$employeeAge = ! empty($selectedEmployee->employee_DOB) ? (string) Carbon::parse($selectedEmployee->employee_DOB)->age : '-';
$jobTitle = trim((string) ($currentOccupationalHistory->job_title ?? '-'));
$workUnit = trim((string) ($currentOccupationalHistory->department ?? $currentOccupationalHistory->work_unit ?? '-'));
$companyName = trim((string) ($selectedCompany->company_name ?? '-'));
$companyAddress = trim((string) implode(', ', array_filter([
    $selectedCompany->company_address ?? null,
    $selectedCompany->company_postcode ?? null,
    $selectedCompany->company_district ?? null,
    $selectedCompany->company_state ?? null,
])));
$examDate = (string) ($audiometryTest->audioTest_date ?? '');
$doctorName = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
$doctorRegNo = trim((string) ($doctor->OHD_registrationNo ?? ''));
$doctorAddress = trim((string) implode(', ', array_filter([
    $doctor->doctor_address ?? null,
    $doctor->doctor_postcode ?? null,
    $doctor->doctor_district ?? null,
    $doctor->doctor_state ?? null,
])));

$showValue = static function ($value, string $fallback = '-'): string {
    $value = trim((string) $value);
    return $value !== '' ? $value : $fallback;
};
$yesNo = static function ($value): string {
    if ($value === null || $value === '') {
        return '-';
    }

    if (is_numeric($value)) {
        return (int) $value === 1 ? 'Yes' : 'No';
    }

    $normalized = strtolower(trim((string) $value));
    if (in_array($normalized, ['yes', 'y', '1', 'true', 'normal'], true)) {
        return 'Yes';
    }
    if (in_array($normalized, ['no', 'n', '0', 'false', 'abnormal'], true)) {
        return 'No';
    }

    return trim((string) $value);
};
$contains = static function (?string $haystack, string $needle): bool {
    return str_contains(strtolower(trim((string) $haystack)), strtolower($needle));
};

$conclusions = [
    'Occupational Hearing Impairment' => $isAudiometryAbnormal,
    'Occupational Permanent Standard Threshold Shift' => in_array((string) ($calculatedAverages['STS_right'] ?? ''), ['Yes', 'Abnormal'], true) || in_array((string) ($calculatedAverages['STS_left'] ?? ''), ['Yes', 'Abnormal'], true),
    'Occupational Noise-Induced Hearing Loss' => $contains($audioComments->standard_analysis ?? '', 'noise') || $contains($audioComments->audio_recommendation ?? '', 'noise'),
    'Age-related Hearing Loss (Presbycusis)' => $contains($audioComments->standard_analysis ?? '', 'presby'),
];

$recommendations = [
    'Repeat audiometry after treatment' => $contains($audioComments->audio_recommendation ?? '', 'repeat'),
    'Continue annual audiometry education & training' => $contains($audioComments->audio_recommendation ?? '', 'annual'),
    'Provision of PHP' => $contains($audioComments->audio_recommendation ?? '', 'php') || $contains($audioComments->audio_recommendation ?? '', 'hearing protection'),
    'Referral to specialist for further management' => $contains($audioComments->audio_recommendation ?? '', 'referral') || $contains($audioComments->audio_recommendation ?? '', 'specialist'),
    'Notification of DOSH' => $contains($audioComments->audio_recommendation ?? '', 'dosh'),
];

$impression = [];
if ($isAudiometryAbnormal) {
    $impression[] = 'Sensorineural Hearing Loss';
}
if (! empty($pastMedical->otoscopy) && (int) $pastMedical->otoscopy !== 1) {
    $impression[] = 'Conductive Hearing Loss';
}
if ($impression === []) {
    $impression[] = 'No abnormal impression recorded';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Audiometry Report</title>
</head>
<body>
<style>
@page{size:A4 portrait;margin:12mm}
body{margin:0;padding:16px;background:#fff;color:#0f172a;font-family:"Poppins","Segoe UI",Tahoma,Geneva,Verdana,sans-serif}
.sheet{display:grid;gap:16px}
.clinic-report-header{padding:0 0 8px}
.clinic-report-header img{display:block;width:100%;max-width:100%;height:auto;object-fit:contain}
.report-card{display:grid;gap:16px}
.report-head{padding:6px 0 14px;border-bottom:2px solid #dce8de}
.report-head-top{position:relative;display:block;text-align:center}
.report-code{position:absolute;right:0;top:0;font-size:14px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#0f172a}
.report-head-act{font-size:14px;font-weight:700;line-height:1.35}
.report-head-regulation{margin-top:4px;font-size:15px;font-weight:700;line-height:1.35}
.report-title{margin:12px 0 0;text-align:center;font-size:18px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.report-subtitle{text-align:center;margin-top:6px;font-size:12px;font-weight:600;color:#475569;letter-spacing:.03em;text-transform:uppercase}
.section{display:grid;gap:10px}
.section-title{display:flex;justify-content:space-between;align-items:center;gap:12px}
.section-title h2{margin:0;font-size:14px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.section-note{font-size:11px;color:#64748b}
.detail-table,.check-table{width:100%;border-collapse:collapse;table-layout:fixed}
.detail-table th,.detail-table td,.check-table th,.check-table td{border:1px solid #d8e1e6;padding:8px 9px;vertical-align:top;font-size:11.5px;line-height:1.45}
.detail-table th,.check-table th{background:#f5faf6;font-weight:700;text-align:left}
.detail-table td strong{font-weight:700}
.two-col{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.bullet-list{display:grid;gap:6px;font-size:11.5px;line-height:1.45}
.bullet-list div{padding:7px 9px;border:1px solid #d8e1e6}
.flag{display:inline-flex;align-items:center;gap:8px}
.mark{width:16px;height:16px;border:1.5px solid #9fb2a5;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}
.mark.on{background:#389B5B;color:#fff;border-color:#389B5B}
.signature-wrap{display:grid;grid-template-columns:260px 1fr;gap:16px;align-items:start}
.stamp-box{min-height:180px;border:1px dashed #cbd5e1;border-radius:14px;background:#fcfcfd;padding:16px;color:#6b7280}
.stamp-box img{max-width:100%;max-height:100px;display:block;margin-bottom:12px;object-fit:contain}
.stamp-name{font-size:.95rem;font-weight:700;color:#0f172a}
.stamp-caption{font-size:.82rem;color:#64748b}
.consent-box{border:1px solid #d8e1e6;border-radius:14px;padding:14px;background:#fff}
.consent-box p{margin:0 0 12px;font-size:11.5px;line-height:1.55}
.grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.line-field{display:grid;gap:4px}
.line-field span{font-size:10.5px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.line-field strong,.line-field div{border-bottom:1px solid #cbd5e1;padding-bottom:5px;font-size:11.5px;min-height:20px}
.empty-state{padding:16px 0;color:#5f6f65;font-size:.95rem;line-height:1.6}
@media print{body{padding:0}.report-card{break-inside:avoid}}
</style>
<div class="sheet">
    <?php require dirname(__DIR__) . '/report/partials/clinic_header.php'; ?>
    <section class="report-card">
        <div class="report-head">
            <div class="report-head-top">
                <div class="report-code">AUDIO REPORT</div>
                <div class="report-head-act">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="report-head-regulation">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                <h1 class="report-title"><?php echo $esc($isAudiometryAbnormal ? 'Medical Examination for Abnormal Audiogram' : 'Audiometry Examination Summary'); ?></h1>
                <div class="report-subtitle">Report of Occupational Disease / Poisonings</div>
            </div>
        </div>

        <?php if (! $audiometryTest): ?>
            <div class="empty-state">No audiometry examination record is available for the selected employee yet.</div>
        <?php else: ?>
            <section class="section">
                <div class="section-title"><h2>Part A: Employee Details</h2></div>
                <table class="detail-table">
                    <tr><th>Name</th><td><?php echo $esc($showValue($employeeName)); ?></td><th>Age</th><td><?php echo $esc($showValue($employeeAge)); ?></td></tr>
                    <tr><th>IC / Passport</th><td><?php echo $esc($showValue($employeeIdentity)); ?></td><th>Sex</th><td><?php echo $esc($showValue($selectedEmployee->employee_gender ?? null)); ?></td></tr>
                    <tr><th>Nationality</th><td><?php echo $esc($showValue($selectedEmployee->employee_citizenship ?? null)); ?></td><th>Job Title</th><td><?php echo $esc($showValue($jobTitle)); ?></td></tr>
                    <tr><th>Company</th><td><?php echo $esc($showValue($companyName)); ?></td><th>Work Unit</th><td><?php echo $esc($showValue($workUnit)); ?></td></tr>
                </table>
            </section>

            <section class="section">
                <div class="section-title">
                    <h2>Part B: Medical History</h2>
                    <div class="section-note">Saved audiometry questionnaire and examination history</div>
                </div>
                <table class="detail-table">
                    <tr><th>Personal Exposure Monitoring</th><td><?php echo $esc($showValue($pastMedical->exposure_lex ?? null) . ' dB(A)'); ?></td><th>Date of Monitoring</th><td><?php echo $esc($showValue($examDate)); ?></td></tr>
                    <tr><th>Current Illness / Symptoms</th><td colspan="3"><?php echo $esc($showValue($audioComments->remarks ?? null, 'No symptoms recorded.')); ?></td></tr>
                    <tr><th>Past Ear Disease / Infection</th><td><?php echo $esc($yesNo($pastMedical->ear_infections ?? null)); ?></td><th>Smoking</th><td>-</td></tr>
                    <tr><th>Past Head Injury / Surgery</th><td><?php echo $esc($yesNo($pastMedical->head_injury ?? null)); ?></td><th>Past Medical History</th><td>-</td></tr>
                    <tr><th>Ototoxic Medication / Chemical Exposure</th><td><?php echo $esc($yesNo($pastMedical->ototoxic_drugs ?? null)); ?></td><th>Previous Noise Exposure</th><td><?php echo $esc($showValue($pastMedical->pre_noiseExposure ?? null)); ?></td></tr>
                    <tr><th>Hobbies with Noise Exposure</th><td><?php echo $esc($showValue($pastMedical->significant_hobbies ?? null)); ?></td><th>Use of PHP</th><td>-</td></tr>
                </table>
            </section>

            <section class="section">
                <div class="section-title"><h2>Part C: Physical Examination</h2></div>
                <div class="two-col">
                    <table class="detail-table">
                        <tr><th>External Ear</th><td><?php echo $esc(((int) ($pastMedical->otoscopy ?? 1) === 1) ? 'Normal' : 'Abnormal'); ?></td></tr>
                        <tr><th>Middle Ear (Otoscopy)</th><td><?php echo $esc(((int) ($pastMedical->otoscopy ?? 1) === 1) ? 'Normal' : 'Abnormal'); ?></td></tr>
                        <tr><th>Weber</th><td><?php echo $esc($showValue($pastMedical->audio_weber ?? null)); ?></td></tr>
                        <tr><th>Rinne Right</th><td><?php echo $esc($showValue($pastMedical->audio_rinneRight ?? null)); ?></td></tr>
                        <tr><th>Rinne Left</th><td><?php echo $esc($showValue($pastMedical->audio_rinneLeft ?? null)); ?></td></tr>
                        <tr><th>Impression</th><td><?php echo $esc(implode(', ', $impression)); ?></td></tr>
                    </table>
                    <table class="detail-table">
                        <tr><th>STS Right</th><td><?php echo $esc($showValue($calculatedAverages['STS_right'] ?? null)); ?></td></tr>
                        <tr><th>STS Left</th><td><?php echo $esc($showValue($calculatedAverages['STS_left'] ?? null)); ?></td></tr>
                        <tr><th>Average 2K / 3K / 4K Right</th><td><?php echo $esc($showValue($calculatedAverages['average1_right'] ?? null)); ?></td></tr>
                        <tr><th>Average 2K / 3K / 4K Left</th><td><?php echo $esc($showValue($calculatedAverages['average1_left'] ?? null)); ?></td></tr>
                        <tr><th>Average 0.5K / 1K / 2K / 3K Right</th><td><?php echo $esc($showValue($calculatedAverages['average2_right'] ?? null)); ?></td></tr>
                        <tr><th>Average 0.5K / 1K / 2K / 3K Left</th><td><?php echo $esc($showValue($calculatedAverages['average2_left'] ?? null)); ?></td></tr>
                    </table>
                </div>
            </section>

            <section class="section">
                <div class="section-title"><h2>Part D: Conclusion</h2></div>
                <table class="check-table">
                    <thead>
                        <tr><th style="width:44px;">#</th><th>Conclusion</th><th>Selected</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conclusions as $label => $checked): ?>
                            <tr>
                                <td><?php echo $esc(substr($label, 0, 1)); ?></td>
                                <td><?php echo $esc($label); ?></td>
                                <td><span class="flag"><span class="mark <?php echo $checked ? 'on' : ''; ?>"><?php echo $checked ? '&#10003;' : ''; ?></span><?php echo $checked ? 'Yes' : 'No'; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr><td>O</td><td>Others</td><td><?php echo $esc($showValue($audioComments->standard_analysis ?? null)); ?></td></tr>
                    </tbody>
                </table>
            </section>

            <section class="section">
                <div class="section-title"><h2>Part E: Recommendation</h2></div>
                <table class="check-table">
                    <thead>
                        <tr><th style="width:44px;">#</th><th>Recommendation</th><th>Selected</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recommendations as $label => $checked): ?>
                            <tr>
                                <td><?php echo $esc(substr($label, 0, 1)); ?></td>
                                <td><?php echo $esc($label); ?></td>
                                <td><span class="flag"><span class="mark <?php echo $checked ? 'on' : ''; ?>"><?php echo $checked ? '&#10003;' : ''; ?></span><?php echo $checked ? 'Yes' : 'No'; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr><td>O</td><td>Others</td><td><?php echo $esc($showValue($audioComments->audio_recommendation ?? null)); ?></td></tr>
                    </tbody>
                </table>
            </section>

            <section class="section">
                <div class="section-title"><h2>Remarks</h2></div>
                <div class="bullet-list">
                    <div><?php echo nl2br($esc($showValue($audioComments->remarks ?? null, 'NA'))); ?></div>
                </div>
            </section>

            <section class="section">
                <div class="signature-wrap">
                    <div class="stamp-box">
                        <?php if ($doctorSignatureUrl): ?>
                            <img src="<?php echo $esc($doctorSignatureUrl); ?>" alt="<?php echo $esc($doctorSignatureName); ?> signature">
                        <?php endif; ?>
                        <div class="stamp-name"><?php echo $esc($showValue($doctorName, $doctorSignatureName)); ?></div>
                        <div class="stamp-caption">Name, signature &amp; stamp OHD</div>
                    </div>
                    <div class="consent-box">
                        <p>
                            I acknowledge that the doctor attending me has explained the results of the examination and their implication.
                            I hereby authorize the doctor to disclose the information in third form to my employer / representative and DOSH if necessary.
                        </p>
                        <div class="grid-2">
                            <div class="line-field"><span>Employee Name</span><strong><?php echo $esc($showValue($employeeName)); ?></strong></div>
                            <div class="line-field"><span>Date</span><strong><?php echo $esc($showValue($examDate)); ?></strong></div>
                            <div class="line-field"><span>IC / Passport</span><strong><?php echo $esc($showValue($employeeIdentity)); ?></strong></div>
                            <div class="line-field"><span>OHD Reg. No.</span><strong><?php echo $esc($showValue($doctorRegNo)); ?></strong></div>
                            <div class="line-field"><span>Practice Address</span><div><?php echo $esc($showValue($doctorAddress)); ?></div></div>
                            <div class="line-field"><span>Company Address</span><div><?php echo $esc($showValue($companyAddress)); ?></div></div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
