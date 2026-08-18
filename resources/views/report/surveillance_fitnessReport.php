<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;

$esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$viewMode = (bool) ($usechh3ViewMode ?? false);
$downloadMode = (bool) ($pdfDownloadMode ?? false) || (bool) ($usechh3DownloadMode ?? false);
$declaration = $usechh3Declaration ?? null;
$employee = $usechh3Employee ?? null;
$company = $usechh3Company ?? null;
$chemical = $usechh3Chemical ?? null;
$fitnessReport = $usechh3FitnessReport ?? null;
$doctor = $usechh3Doctor ?? null;
$employeeName = (string) ($usechh3EmployeeName ?? 'Not recorded');
$companyAddress = (string) ($usechh3CompanyAddress ?? '-');
$doctorName = trim((string) ($usechh3DoctorName ?? 'Doctor'));
$doctorRegNo = trim((string) ($usechh3DoctorRegNo ?? ''));
$doctorSignature = trim((string) ($usechh3DoctorSignature ?? ''));
$doctorPracticeAddress = trim((string) ($usechh3DoctorPracticeAddress ?? ''));
$doctorTelephone = trim((string) ($usechh3DoctorTelephone ?? ''));
$doctorEmail = trim((string) ($usechh3DoctorEmail ?? ''));
$declarationId = (int) ($usechh3DeclarationId ?? 0);
$employeeId = (int) ($usechh3EmployeeId ?? 0);
$companyId = (int) ($usechh3CompanyId ?? 0);
$surveillanceId = (int) ($usechh3SurveillanceId ?? 0);
$msFindings = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('ms_findings')
    ? DB::table('ms_findings')->where('surveillance_id', $surveillanceId)->first()
    : null;
$fitnessResult = trim((string) ($msFindings->conclusion_fitness ?? $fitnessReport->result ?? 'Pending review'));
$fitnessResult = in_array(strtolower($fitnessResult), ['fit', 'not fit'], true)
    ? (strtolower($fitnessResult) === 'fit' ? 'Fit' : 'Not Fit')
    : $fitnessResult;
$savedRemarks = trim((string) ($fitnessReport->remarks ?? ''));
$remarksValue = old('remarks', $savedRemarks);
$remarksPrint = trim((string) ($savedRemarks !== '' ? $savedRemarks : 'NA'));
$statusMessage = (string) session('status', '');
$doctorAddress = trim((string) (($doctor->doctor_address ?? '') . ', ' . ($doctor->doctor_postcode ?? '') . ' ' . ($doctor->doctor_district ?? '') . ', ' . ($doctor->doctor_state ?? '')), " ,");
$formatDate = static function (?string $value, string $format = 'd/m/Y'): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '-';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date($format, $timestamp) : $value;
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>USECHH 3</title>
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
    gap: 10px;
    flex-wrap: wrap;
}

.head-btn,
.save-btn {
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

.save-btn {
    border-color: #389b5b;
    background: #389b5b;
    color: #fff;
    font-weight: 600;
}

.page-card {
    border: 0;
    border-radius: 0;
    background: #fff;
    overflow: hidden;
}

.page-pad {
    padding: 0 0 28px;
}

.hero {
    display: grid;
    gap: 6px;
    text-align: center;
}

.hero .law {
    font-size: .94rem;
    font-weight: 600;
}

.hero .title {
    font-size: 1.75rem;
    font-weight: 700;
}

.meta-grid {
    display: grid;
    gap: 14px;
    margin-top: 22px;
}

.meta-box {
    display: grid;
    gap: 8px;
    padding: 20px 22px;
    border: 1px solid #edf0f2;
    border-radius: 14px;
}

.section-title {
    margin: 0 0 14px;
    font-size: 1.15rem;
    font-weight: 700;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px 18px;
}

.form-field {
    display: grid;
    gap: 7px;
}

.form-field.full {
    grid-column: 1 / -1;
}

.form-label {
    font-weight: 600;
}

.form-value,
.form-field textarea {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    padding: 10px 12px;
    font: inherit;
    color: #111827;
    background: #fff;
    box-sizing: border-box;
    min-height: 46px;
}

.form-field textarea {
    min-height: 120px;
    resize: vertical;
}

.form-value {
    display: flex;
    align-items: center;
    white-space: pre-line;
}

.form-value.multiline {
    display: block;
    min-height: 88px;
}

.narrative-card {
    padding: 20px 22px;
    border: 1px solid #edf0f2;
    border-radius: 14px;
    background: #fff;
    line-height: 1.75;
}

.flash {
    margin: 0 0 8px;
    padding: 10px 14px;
    border: 1px solid #cfe7d4;
    border-radius: 12px;
    background: #f3fbf4;
    color: #1f5f35;
    font-size: .9rem;
}

.footer-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 18px;
}

.pdf-page {
    width: 100%;
}

.pdf-header img {
    display: block;
    width: 100%;
    max-width: 100%;
    height: auto;
}

.pdf-title {
    margin: 14px 0 6px;
    text-align: center;
    position: relative;
    padding-bottom: 0;
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

.pdf-details {
    margin-top: 10px;
    border-top: 1px solid #dce8de;
    border-bottom: 1px solid #e5e7eb;
    padding: 8px 0 6px;
}

.pdf-detail-table {
    width: 100%;
    border-collapse: collapse;
}

.pdf-detail-table td {
    padding: 6px 0;
    vertical-align: top;
}

.pdf-detail-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #2d2d2d;
    width: 185px;
    padding-right: 14px;
}

.pdf-detail-value {
    font-size: 11px;
    white-space: pre-line;
}

.pdf-copy {
    padding: 4px 0;
    font-size: 11px;
    line-height: 1.65;
    white-space: normal;
}

.pdf-copy + .pdf-copy {
    border-top: 1px solid #e5e7eb;
}

.pdf-copy.narrative-copy {
    padding-top: 4px;
    padding-bottom: 4px;
}

.pdf-copy.remarks-copy {
    padding-top: 4px;
    padding-bottom: 0;
    line-height: 1.45;
}

.remarks-label {
    display: block;
    margin: 0 0 2px;
    font-weight: 700;
}

.remarks-text {
    margin: 0;
    white-space: pre-line;
}

.signature-block {
    margin-top: 28px;
    display: grid;
    gap: 2px;
    justify-items: end;
    text-align: right;
    width: 300px;
    margin-left: auto;
}

.signature-art {
    width: 100%;
    min-height: 72px;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    margin-bottom: 2px;
    background: transparent;
}

.signature-block img {
    display: block;
    max-width: 190px;
    max-height: 88px;
    width: auto;
    height: auto;
    object-fit: contain;
    object-position: right top;
    margin: 0 0 0 auto;
    background: transparent;
}

.signature-meta {
    font-size: 11px;
    line-height: 1.25;
    white-space: pre-line;
    width: 100%;
}

.signature-label {
    font-weight: 700;
    text-transform: uppercase;
}

.narrative-strong {
    font-weight: 700;
}

@media screen and (max-width: 980px) {
    .form-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .pdf-page {
        overflow-x: auto;
    }
}

@media print {
    html, body {
        width: 210mm;
        min-height: 297mm;
        margin: 0;
        padding: 0;
        background: #fff;
    }

    .screen-shell,
    .no-print {
        display: none !important;
    }

    .pdf-page {
        display: block !important;
    }
}
</style>
</head>
<body>
<?php if (! $downloadMode): ?>
    <div class="screen-shell">
        <section class="no-print" style="padding:22px 24px 0; display:flex; justify-content:flex-end;">
            <div class="head-actions">
                <a class="head-btn" href="<?php echo $esc(function_exists('route') ? route('general.report.folder', array_filter([
                    'module' => 'surveillance',
                    'company' => trim((string) ($company->company_name ?? '')),
                    'date' => trim((string) ($chemical->examination_date ?? $declaration->doctor_date ?? $declaration->employee_date ?? '')),
                    'tab' => 'usechh 3',
                ], static fn ($value) => $value !== '')) : '#'); ?>">Back</a>
                <a class="head-btn" href="<?php echo $esc(function_exists('route') ? route('pdf.usechh3', array_filter([
                    'declaration_id' => $declarationId,
                    'employee_id' => $employeeId,
                    'company_id' => $companyId,
                    'surveillance_id' => $surveillanceId,
                ], static fn ($value) => $value !== 0)) : '#'); ?>">Download PDF</a>
            </div>
        </section>

        <form method="post" action="<?php echo $esc(function_exists('route') ? route('surveillance.report.fitness.save') : '#'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="declaration_id" value="<?php echo $esc((string) $declarationId); ?>">
            <input type="hidden" name="employee_id" value="<?php echo $esc((string) $employeeId); ?>">
            <input type="hidden" name="company_id" value="<?php echo $esc((string) $companyId); ?>">
            <input type="hidden" name="surveillance_id" value="<?php echo $esc((string) $surveillanceId); ?>">

            <section class="page-card">
                <div class="page-pad">
                    <div class="hero">
                        <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
                        <div class="law">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                        <div class="title">CERTIFICATE OF FITNESS</div>
                    </div>

                    <?php if (! $viewMode && $statusMessage !== ''): ?>
                        <div class="flash"><?php echo $esc($statusMessage); ?></div>
                    <?php endif; ?>

                    <div class="meta-grid">
                        <div class="meta-box">
                            <h2 class="section-title">Report Details</h2>
                            <div class="form-grid">
                                <div class="form-field">
                                    <label class="form-label">Person Examined</label>
                                    <div class="form-value"><?php echo $esc($employeeName); ?></div>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">NRIC / Passport</label>
                                    <div class="form-value"><?php echo $esc((string) (($employee->employee_NRIC ?? '') !== '' ? ($employee->employee_NRIC ?? '') : ($employee->employee_passportNo ?? '-'))); ?></div>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Date of Birth</label>
                                    <div class="form-value"><?php echo $esc((string) ($employee->employee_DOB ?? '-')); ?></div>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Sex</label>
                                    <div class="form-value"><?php echo $esc((string) ($employee->employee_gender ?? '-')); ?></div>
                                </div>
                                <div class="form-field full">
                                    <label class="form-label">Employer</label>
                                    <div class="form-value"><?php echo $esc((string) ($company->company_name ?? '-')); ?></div>
                                </div>
                                <div class="form-field full">
                                    <label class="form-label">Employer Address</label>
                                    <div class="form-value multiline"><?php echo $esc($companyAddress); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="narrative-card">
                        I hereby certify that I have examined the above-named person on
                        <span class="narrative-strong"><?php echo $esc($formatDate((string) ($chemical->examination_date ?? $declaration->doctor_date ?? '-'))); ?></span>
                        and that he is <span class="narrative-strong"><?php echo $esc($fitnessResult); ?></span>
                        for work which may expose him to <span class="narrative-strong"><?php echo $esc((string) ($chemical->chemicals ?? 'the stated chemical hazard')); ?></span>.
                    </div>

                    <div class="meta-grid">
                        <div class="meta-box">
                            <h2 class="section-title">Remarks</h2>
                            <?php if ($viewMode): ?>
                                <div class="form-value multiline"><?php echo $esc($remarksPrint); ?></div>
                            <?php else: ?>
                                <div class="form-field">
                                    <textarea name="remarks" placeholder="Enter remarks for USECHH 3 before printing..."><?php echo $esc((string) $remarksValue); ?></textarea>
                                </div>
                            <?php endif; ?>
                            <div class="form-grid">
                                <div class="form-field full">
                                    <label class="form-label">Address of Practice</label>
                                    <?php if ($viewMode): ?>
                                        <div class="form-value multiline"><?php echo $esc($doctorPracticeAddress !== '' ? $doctorPracticeAddress : '-'); ?></div>
                                    <?php else: ?>
                                        <textarea name="doctor_practice_address" placeholder="Enter address of practice"><?php echo $esc($doctorPracticeAddress); ?></textarea>
                                    <?php endif; ?>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Telephone</label>
                                    <?php if ($viewMode): ?>
                                        <div class="form-value"><?php echo $esc($doctorTelephone !== '' ? $doctorTelephone : '-'); ?></div>
                                    <?php else: ?>
                                        <textarea name="doctor_telephone" placeholder="Enter telephone"><?php echo $esc($doctorTelephone); ?></textarea>
                                    <?php endif; ?>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Email</label>
                                    <?php if ($viewMode): ?>
                                        <div class="form-value"><?php echo $esc($doctorEmail !== '' ? $doctorEmail : '-'); ?></div>
                                    <?php else: ?>
                                        <textarea name="doctor_email_address" placeholder="Enter email"><?php echo $esc($doctorEmail); ?></textarea>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (! $viewMode): ?>
                        <div class="footer-actions no-print">
                            <button class="save-btn" type="submit">Save Report</button>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </form>
    </div>
<?php endif; ?>

<div class="pdf-page" style="<?php echo $downloadMode ? '' : 'display:none;'; ?>">
    <div class="pdf-header">
        <?php require __DIR__ . '/partials/clinic_header.php'; ?>
    </div>
    <div class="pdf-title">
        <div class="pdf-code">USECHH 3</div>
        <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
        <div class="law">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
        <div class="main">CERTIFICATE OF FITNESS</div>
    </div>

    <div class="pdf-details">
        <table class="pdf-detail-table">
            <tr>
                <td class="pdf-detail-label">Person Examined</td>
                <td class="pdf-detail-value"><?php echo $esc($employeeName); ?></td>
                <td class="pdf-detail-label">NRIC / Passport</td>
                <td class="pdf-detail-value"><?php echo $esc((string) (($employee->employee_NRIC ?? '') !== '' ? ($employee->employee_NRIC ?? '') : ($employee->employee_passportNo ?? '-'))); ?></td>
            </tr>
            <tr>
                <td class="pdf-detail-label">Date of Birth</td>
                <td class="pdf-detail-value"><?php echo $esc((string) ($employee->employee_DOB ?? '-')); ?></td>
                <td class="pdf-detail-label">Sex</td>
                <td class="pdf-detail-value"><?php echo $esc((string) ($employee->employee_gender ?? '-')); ?></td>
            </tr>
            <tr>
                <td class="pdf-detail-label">Employer</td>
                <td class="pdf-detail-value" colspan="3"><?php echo $esc((string) ($company->company_name ?? '-')); ?></td>
            </tr>
            <tr>
                <td class="pdf-detail-label">Employer Address</td>
                <td class="pdf-detail-value" colspan="3"><?php echo $esc($companyAddress); ?></td>
            </tr>
        </table>
    </div>

    <div class="pdf-copy narrative-copy">I hereby certify that I have examined the above-named person on <strong><?php echo $esc($formatDate((string) ($chemical->examination_date ?? $declaration->doctor_date ?? '-'))); ?></strong> and that he is <strong><?php echo $esc($fitnessResult); ?></strong> for work which may expose him to <strong><?php echo $esc((string) ($chemical->chemicals ?? 'the stated chemical hazard')); ?></strong>.
    </div>

    <div class="pdf-copy remarks-copy">
        <span class="remarks-label">REMARKS</span>
        <p class="remarks-text"><?php echo $esc($remarksPrint); ?></p>
    </div>

    <div class="signature-block">
        <?php if ($doctorSignature !== ''): ?>
            <div class="signature-art">
                <img src="<?php echo $esc($doctorSignature); ?>" alt="Doctor signature">
            </div>
        <?php endif; ?>
        <div class="signature-meta"><span class="signature-label">Name of OHD</span><?php echo "\n" . $esc($doctorName); ?></div>
        <div class="signature-meta"><span class="signature-label">OHD Signature Date</span><?php echo "\n" . $esc($formatDate((string) ($declaration->doctor_date ?? $chemical->examination_date ?? ''))); ?></div>
        <div class="signature-meta"><span class="signature-label">Address of Practice</span><?php echo "\n" . $esc($doctorPracticeAddress !== '' ? $doctorPracticeAddress : '-'); ?></div>
        <div class="signature-meta"><span class="signature-label">Telephone</span><?php echo "\n" . $esc($doctorTelephone !== '' ? $doctorTelephone : '-'); ?></div>
        <div class="signature-meta"><span class="signature-label">Email</span><?php echo "\n" . $esc($doctorEmail !== '' ? $doctorEmail : '-'); ?></div>
        <div class="signature-meta"><span class="signature-label">Registration No.</span><?php echo "\n" . $esc($doctorRegNo !== '' ? $doctorRegNo : '-'); ?></div>
    </div>
</div>
</body>
</html>
