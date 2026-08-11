<?php
declare(strict_types=1);

$esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$viewMode = (bool) ($usechh5iiViewMode ?? false);
$downloadMode = (bool) ($pdfDownloadMode ?? false) || (bool) ($usechh5iiDownloadMode ?? false);
$items = is_array($usechh5iiItems ?? null) ? $usechh5iiItems : [];
$company = $usechh5iiCompany ?? null;
$doctor = $usechh5iiDoctor ?? null;
$chemicalName = trim((string) ($usechh5iiChemical ?? ''));
$groupDate = trim((string) ($usechh5iiDate ?? ''));
$reportId = (int) (($usechh5iiReport->abnormal_report_id ?? 0));
$companyId = (int) ($usechh5iiCompanyId ?? 0);
$declarationId = (int) ($usechh5iiDeclarationId ?? 0);
$employeeId = (int) ($usechh5iiEmployeeId ?? 0);
$surveillanceId = (int) ($usechh5iiSurveillanceId ?? 0);

$formatDate = static function (?string $value, string $format = 'd M Y'): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '-';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date($format, $timestamp) : $value;
};

$doctorName = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
$companyAddressParts = array_values(array_filter([
    trim((string) ($company->company_address ?? '')),
    trim((string) ($company->company_postcode ?? '')) !== '' || trim((string) ($company->company_district ?? '')) !== ''
        ? trim((string) ($company->company_postcode ?? '')) . (trim((string) ($company->company_postcode ?? '')) !== '' && trim((string) ($company->company_district ?? '')) !== '' ? ', ' : '') . trim((string) ($company->company_district ?? ''))
        : '',
    trim((string) ($company->company_state ?? '')),
    'Malaysia',
], static fn ($value) => trim((string) $value) !== ''));
$companyAddress = implode(', ', $companyAddressParts);
$downloadFilename = 'USECHH5ii - ' . ($chemicalName !== '' ? $chemicalName : 'Chemical');
$reportName = 'DETAILS OF WORKERS WITH ABNORMAL EXAMINATION RESULTS';
$formatSexShort = static function (?string $value): string {
    $value = strtolower(trim((string) $value));

    return match ($value) {
        'female', 'f' => 'F',
        'male', 'm' => 'M',
        default => trim((string) $value) !== '' ? strtoupper(substr(trim((string) $value), 0, 1)) : '-',
    };
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>USECHH 5ii</title>
<style>
@page {
    size: A4 landscape;
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

.screen-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
}

.screen-head h1 {
    margin: 0;
    font-size: 1.9rem;
}

.screen-head p {
    margin: 8px 0 0;
    color: #6b7280;
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

.meta-box h2 {
    margin: 0;
    font-size: 1.02rem;
}

.meta-row {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 14px;
    align-items: start;
}

.meta-label {
    font-weight: 600;
}

.meta-value {
    min-width: 0;
}

.patient-sections {
    display: grid;
    gap: 20px;
    margin-top: 22px;
}

.patient-section {
    padding: 20px 22px;
    border: 1px solid #edf0f2;
    border-radius: 14px;
    background: #fff;
}

.patient-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
}

.patient-section-title {
    margin: 0;
    font-size: 1.08rem;
    font-weight: 700;
}

.patient-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 12px;
    border: 1px solid #d6e4d9;
    border-radius: 999px;
    background: #f7fbf8;
    color: #166534;
    font-size: .84rem;
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
.form-field input,
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

.section-title {
    margin: 0 0 14px;
    font-size: 1.15rem;
    font-weight: 700;
}

.form-field textarea {
    min-height: 88px;
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

.readonly-cell {
    white-space: pre-line;
}

.empty-state {
    padding: 24px 0 4px;
    color: #6b7280;
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
    margin: 14px 0 18px;
    text-align: center;
}

.pdf-title .law {
    font-size: 11px;
    font-weight: 700;
}

.pdf-title .main {
    margin-top: 7px;
    font-size: 18px;
    font-weight: 700;
}

.pdf-meta {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
}

.pdf-meta td {
    padding: 6px 8px;
    border: 1px solid #d1d5db;
    font-size: 11px;
}

.pdf-meta .label {
    width: 180px;
    font-weight: 700;
    background: #fafafa;
}

.pdf-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: auto;
    margin-top: 6px;
}

.pdf-table th,
.pdf-table td {
    border: 1px solid #cbd5e1;
    padding: 7px 8px;
    font-size: 10px;
    vertical-align: top;
    text-align: left;
    word-wrap: break-word;
    overflow-wrap: anywhere;
}

.pdf-table th {
    background: #f8fafc;
    font-weight: 700;
    font-size: 9px;
    line-height: 1.25;
    white-space: normal;
    word-break: break-word;
    hyphens: auto;
}

.pdf-note {
    margin-top: 12px;
    font-size: 10px;
    line-height: 1.45;
    color: #4b5563;
}

.pdf-table .col-no {
    width: 3%;
}

.pdf-table .col-patient {
    width: 13%;
}

.pdf-table .col-nric {
    width: 10%;
}

.pdf-table .col-sex {
    width: 4%;
}

.pdf-table .col-designation {
    width: 6%;
}

.pdf-table .col-assessment {
    width: 10%;
}

.pdf-table .col-history {
    width: 7%;
}

.pdf-table .col-clinical {
    width: 7%;
}

.pdf-table .col-target {
    width: 10%;
}

.pdf-table .col-bm {
    width: 8%;
}

.pdf-table .col-work {
    width: 9%;
}

.pdf-table .col-recommendation {
    width: 12%;
}

.pdf-table .col-conclusion {
    width: 5%;
}

.pdf-table .nowrap {
    white-space: nowrap;
    word-break: normal;
    overflow-wrap: normal;
}

.pdf-table .nric-cell {
    white-space: nowrap;
    word-break: normal;
    overflow-wrap: normal;
}

@media (max-width: 980px) {
    .form-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .patient-section-head {
        align-items: flex-start;
        flex-direction: column;
    }
}

@media print {
    html, body {
        width: 297mm;
        min-height: 210mm;
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
        <section class="screen-head no-print" style="padding:22px 24px 0; justify-content:flex-end;">
            <div class="head-actions">
                <a class="head-btn" href="<?php echo $esc(function_exists('route') ? route('general.report.folder', array_filter([
                    'module' => 'surveillance',
                    'company' => trim((string) ($company->company_name ?? '')),
                    'date' => $groupDate,
                    'tab' => 'usechh 5ii',
                ], static fn ($value) => $value !== '')) : '#'); ?>">Back</a>
                <a class="head-btn" href="<?php echo $esc(function_exists('route') ? route('pdf.usechh5ii.download', array_filter([
                    'declaration_id' => $declarationId,
                    'employee_id' => $employeeId,
                    'company_id' => $companyId,
                    'surveillance_id' => $surveillanceId,
                    'group_chemical' => $chemicalName,
                    'group_date' => $groupDate,
                ], static fn ($value) => $value !== '' && $value !== 0)) : '#'); ?>">Download PDF</a>
            </div>
        </section>

        <form method="post" action="<?php echo $esc(function_exists('route') ? route('surveillance.report.abnormal.save') : '#'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="abnormal_report_id" value="<?php echo $esc((string) $reportId); ?>">
            <input type="hidden" name="company_id" value="<?php echo $esc((string) $companyId); ?>">
            <input type="hidden" name="group_date" value="<?php echo $esc($groupDate); ?>">
            <input type="hidden" name="group_chemical" value="<?php echo $esc($chemicalName); ?>">

            <section class="page-card">
                <div class="page-pad">
                    <div class="hero">
                        <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
                        <div class="law">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                        <div class="title"><?php echo $esc($reportName); ?></div>
                    </div>

                    <div class="meta-grid">
                        <div class="meta-box">
                            <h2 class="section-title">Report Details</h2>
                            <div class="form-grid">
                                <div class="form-field">
                                    <label class="form-label">Name of Employer</label>
                                    <div class="form-value"><?php echo $esc(trim((string) ($company->company_name ?? '-'))); ?></div>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Examination Date</label>
                                    <div class="form-value"><?php echo $esc($formatDate($groupDate)); ?></div>
                                </div>
                                <div class="form-field full">
                                    <label class="form-label">Chemical Name</label>
                                    <div class="form-value"><?php echo $esc($chemicalName !== '' ? $chemicalName : '-'); ?></div>
                                </div>
                                <div class="form-field full">
                                    <label class="form-label">Company Address</label>
                                    <div class="form-value multiline"><?php echo $esc($companyAddress !== '' ? $companyAddress : '-'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($items === []): ?>
                        <div class="empty-state">No saved USECHH 5i patient records were found for this chemical group yet.</div>
                    <?php else: ?>
                        <div class="patient-sections">
                            <?php foreach ($items as $index => $item): ?>
                                <section class="patient-section">
                                    <input type="hidden" name="items[<?php echo $esc((string) $index); ?>][item_id]" value="<?php echo $esc((string) ($item['abnormal_report_item_id'] ?? '')); ?>">

                                    <div class="patient-section-head">
                                        <h3 class="patient-section-title">Patient Information</h3>
                                        <span class="patient-badge">Patient <?php echo $esc((string) ($index + 1)); ?></span>
                                    </div>

                                    <div class="form-grid">
                                        <div class="form-field">
                                            <label class="form-label">Patient Name</label>
                                            <div class="form-value"><?php echo $esc($item['patient_name'] ?? ''); ?></div>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">NRIC / Passport</label>
                                            <div class="form-value"><?php echo $esc($item['identity_no'] ?? ''); ?></div>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">Sex</label>
                                            <div class="form-value"><?php echo $esc($item['sex'] ?? ''); ?></div>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">Designation</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value"><?php echo $esc($item['designation'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <input type="text" name="items[<?php echo $esc((string) $index); ?>][designation]" value="<?php echo $esc($item['designation'] ?? ''); ?>">
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field full">
                                            <label class="form-label">Type of Assessment</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['assessment_type'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][assessment_type]"><?php echo $esc($item['assessment_type'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field full">
                                            <label class="form-label">History of Health Effect Due to CTH Exposure</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['history_effect'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][history_effect]"><?php echo $esc($item['history_effect'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field full">
                                            <label class="form-label">Clinical Findings</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['clinical_findings'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][clinical_findings]"><?php echo $esc($item['clinical_findings'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field full">
                                            <label class="form-label">Target Organ Function Test</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['target_organ_function'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][target_organ_function]"><?php echo $esc($item['target_organ_function'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field full">
                                            <label class="form-label">BM Determinant</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['bm_determinant'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][bm_determinant]"><?php echo $esc($item['bm_determinant'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field full">
                                            <label class="form-label">Work Relatedness</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['work_relatedness'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][work_relatedness]"><?php echo $esc($item['work_relatedness'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field full">
                                            <label class="form-label">Recommendation / Action</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['recommendation_action'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][recommendation_action]"><?php echo $esc($item['recommendation_action'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field full">
                                            <label class="form-label">Conclusion</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['conclusion'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][conclusion]"><?php echo $esc($item['conclusion'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </section>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (! $viewMode && $items !== []): ?>
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
        <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
        <div class="law">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
        <div class="main"><?php echo $esc($reportName); ?></div>
    </div>

    <table class="pdf-meta">
        <tr>
            <td class="label">Name of Employer</td>
            <td><?php echo $esc(trim((string) ($company->company_name ?? '-'))); ?></td>
            <td class="label">Examination Date</td>
            <td><?php echo $esc($formatDate($groupDate)); ?></td>
        </tr>
        <tr>
            <td class="label">Chemical Name</td>
            <td><?php echo $esc($chemicalName !== '' ? $chemicalName : '-'); ?></td>
            <td class="label">Address</td>
            <td><?php echo $esc($companyAddress !== '' ? $companyAddress : '-'); ?></td>
        </tr>
    </table>

    <table class="pdf-table">
        <thead>
            <tr>
                <th class="col-no nowrap">No</th>
                <th class="col-patient">Patient Name</th>
                <th class="col-nric nowrap">NRIC / Passport</th>
                <th class="col-sex nowrap">Sex</th>
                <th class="col-designation">Designation</th>
                <th class="col-assessment">Type of Assessment</th>
                <th class="col-history">History Effect</th>
                <th class="col-clinical">Clinical Findings</th>
                <th class="col-target">Target Organ Function</th>
                <th class="col-bm">BM Determinant</th>
                <th class="col-work">Work Relatedness</th>
                <th class="col-recommendation">Recommendation / Action</th>
                <th class="col-conclusion">Conclusion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td><?php echo $esc((string) ($index + 1)); ?></td>
                    <td><?php echo $esc($item['patient_name'] ?? ''); ?></td>
                    <td class="nric-cell"><?php echo $esc($item['identity_no'] ?? ''); ?></td>
                    <td class="nowrap"><?php echo $esc($formatSexShort($item['sex'] ?? '')); ?></td>
                    <td><?php echo $esc($item['designation'] ?? ''); ?></td>
                    <td><?php echo $esc($item['assessment_type'] ?? ''); ?></td>
                    <td><?php echo $esc($item['history_effect'] ?? ''); ?></td>
                    <td><?php echo $esc($item['clinical_findings'] ?? ''); ?></td>
                    <td><?php echo $esc($item['target_organ_function'] ?? ''); ?></td>
                    <td><?php echo $esc($item['bm_determinant'] ?? ''); ?></td>
                    <td><?php echo $esc($item['work_relatedness'] ?? ''); ?></td>
                    <td><?php echo $esc($item['recommendation_action'] ?? ''); ?></td>
                    <td><?php echo $esc($item['conclusion'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="pdf-note">
        Prepared by <?php echo $esc($doctorName !== '' ? $doctorName : 'Occupational Health Doctor'); ?>. This USECHH 5ii report is grouped by chemical name and examination date based on patients with saved USECHH 5i records.
    </div>
</div>
</body>
</html>
