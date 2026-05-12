<?php
declare(strict_types=1);

use Carbon\Carbon;

$esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$selectedEmployee = $selectedEmployee ?? null;
$selectedCompany = $selectedCompany ?? null;
$currentOccupationalHistory = $currentOccupationalHistory ?? null;
$audiometryTest = $audiometryTest ?? null;
$pastMedical = $pastMedical ?? null;
$annualAudiograph = $annualAudiograph ?? null;
$doctorSignatureName = $doctorSignatureName ?? 'Doctor';

$employeeName = trim((string) (($selectedEmployee->employee_firstName ?? '') . ' ' . ($selectedEmployee->employee_lastName ?? '')));
$employeeIdentity = trim((string) (($selectedEmployee->employee_NRIC ?? '') ?: ($selectedEmployee->employee_passportNo ?? '')));
$employeeAge = ! empty($selectedEmployee->employee_DOB) ? (string) Carbon::parse($selectedEmployee->employee_DOB)->age : '-';
$jobTitle = trim((string) ($currentOccupationalHistory->job_title ?? '-'));
$workUnit = trim((string) ($currentOccupationalHistory->department ?? $currentOccupationalHistory->work_unit ?? '-'));
$annualValue = static function (?object $row, string $column) {
    return $row ? ($row->{$column} ?? null) : null;
};

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
    if (in_array($normalized, ['yes', 'y', '1', 'true'], true)) {
        return 'Yes';
    }
    if (in_array($normalized, ['no', 'n', '0', 'false'], true)) {
        return 'No';
    }

    return trim((string) $value);
};
$freqMap = ['500' => 'R_500', '1K' => 'R_1k', '2K' => 'R_2k', '3K' => 'R_3k', '4K' => 'R_4k', '6K' => 'R_6k', '8K' => 'R_8k'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Audiometry Questionnaire Report</title>
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
.detail-table,.question-table,.test-table{width:100%;border-collapse:collapse;table-layout:fixed}
.detail-table th,.detail-table td,.question-table th,.question-table td,.test-table th,.test-table td{border:1px solid #d8e1e6;padding:8px 9px;vertical-align:top;font-size:11.5px;line-height:1.45}
.detail-table th,.question-table th,.test-table th{background:#f5faf6;font-weight:700;text-align:left}
.question-table td:first-child{width:34px;text-align:center;font-weight:700}
.test-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:16px}
.section-title{margin:0;font-size:14px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.mini-note{font-size:11px;color:#64748b}
@media print{body{padding:0}.report-card{break-inside:avoid}}
</style>
<div class="sheet">
    <?php require __DIR__ . '/partials/clinic_header.php'; ?>
    <section class="report-card">
        <div class="report-head">
            <div class="report-head-top">
                <div class="report-code">QUESTIONNAIRE</div>
                <div class="report-head-act">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="report-head-regulation">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                <h1 class="report-title">Questionnaire Form for Audiometric Testing</h1>
            </div>
        </div>

        <table class="detail-table">
            <tr><th>Name</th><td><?php echo $esc($showValue($employeeName)); ?></td><th>Gender</th><td><?php echo $esc($showValue($selectedEmployee->employee_gender ?? null)); ?></td></tr>
            <tr><th>Age</th><td><?php echo $esc($showValue($employeeAge)); ?></td><th>Company</th><td><?php echo $esc($showValue($selectedCompany->company_name ?? null)); ?></td></tr>
            <tr><th>IC / Passport</th><td><?php echo $esc($showValue($employeeIdentity)); ?></td><th>Job</th><td><?php echo $esc($showValue($jobTitle)); ?></td></tr>
            <tr><th>Department</th><td><?php echo $esc($showValue($workUnit)); ?></td><th>Years of Service</th><td><?php echo $esc($showValue($audiometryTest->noYears_working ?? null)); ?></td></tr>
        </table>

        <div>
            <h2 class="section-title">Saved Questionnaire Responses</h2>
            <div class="mini-note">This print view reflects the questionnaire-related data currently saved in the audiometry examination record.</div>
        </div>
        <table class="question-table">
            <tr><td>1</td><th>Were you exposed to loud noise within 14 hours prior to today’s test?</th><td><?php echo $esc($showValue($pastMedical->pre_noiseExposure ?? null)); ?></td></tr>
            <tr><td>2</td><th>Have you suffered any illness that affected your hearing?</th><td>-</td></tr>
            <tr><td>3</td><th>Have you ever had an ear operation or other major operation that affected your hearing?</th><td><?php echo $esc($yesNo($pastMedical->prev_earSurgery ?? null)); ?></td></tr>
            <tr><td>4</td><th>Have you ever taken medication that affected your hearing?</th><td><?php echo $esc($yesNo($pastMedical->ototoxic_drugs ?? null)); ?></td></tr>
            <tr><td>5</td><th>Have you been exposed to loud noise?</th><td><?php echo $esc($showValue($pastMedical->pre_noiseExposure ?? null)); ?></td></tr>
            <tr><td>6</td><th>Any family history of hearing loss / disorders?</th><td>-</td></tr>
            <tr><td>7</td><th>Do you attend night clubs / pubs / discotheques or pop / rock concerts?</th><td><?php echo $esc($showValue($pastMedical->significant_hobbies ?? null)); ?></td></tr>
            <tr><td>8</td><th>Do you use a personal stereo?</th><td><?php echo $esc($showValue($pastMedical->significant_hobbies ?? null)); ?></td></tr>
            <tr><td>9</td><th>Do you play loud music instruments?</th><td><?php echo $esc($showValue($pastMedical->significant_hobbies ?? null)); ?></td></tr>
            <tr><td>10</td><th>Have you worked in noisy jobs in the past?</th><td><?php echo $esc($showValue($pastMedical->pre_noiseExposure ?? null)); ?></td></tr>
            <tr><td>11</td><th>Were you wearing personal hearing protectors at that time?</th><td>-</td></tr>
            <tr><td>12</td><th>Have you had an audiometric test before?</th><td><?php echo $esc(($pastMedical && ($pastMedical->type_audiogram ?? '') === 'Annual') ? 'Yes' : 'No'); ?></td></tr>
        </table>

        <div class="test-grid">
            <div>
                <h2 class="section-title">Annual Audiometry Test</h2>
                <table class="test-table">
                    <thead>
                        <tr><th>Ear</th><?php foreach (array_keys($freqMap) as $freq): ?><th><?php echo $esc($freq); ?></th><?php endforeach; ?></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>Right Air</th>
                            <?php foreach ($freqMap as $column): ?><td><?php echo $esc($showValue($annualValue($annualAudiograph, $column))); ?></td><?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Left Air</th>
                            <?php foreach (array_keys($freqMap) as $freq): $column = 'L_' . strtolower(str_replace('K', 'k', $freq)); ?><td><?php echo $esc($showValue($annualValue($annualAudiograph, $column))); ?></td><?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div>
                <h2 class="section-title">Visual Examination of Ear</h2>
                <table class="test-table">
                    <tr><th>Otoscopy</th><td><?php echo $esc(((int) ($pastMedical->otoscopy ?? 1) === 1) ? 'Normal' : 'Abnormal'); ?></td></tr>
                    <tr><th>Technician Signature</th><td><?php echo $esc($doctorSignatureName); ?></td></tr>
                    <tr><th>Name</th><td><?php echo $esc($doctorSignatureName); ?></td></tr>
                    <tr><th>Date</th><td><?php echo $esc($showValue($audiometryTest->audioTest_date ?? null)); ?></td></tr>
                </table>
            </div>
        </div>
    </section>
</div>
</body>
</html>
