<?php
declare(strict_types=1);

$esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$viewMode = (bool) ($usechh2ViewMode ?? false);
$downloadMode = (bool) ($pdfDownloadMode ?? false) || (bool) ($usechh2DownloadMode ?? false);
$items = is_array($usechh2Items ?? null) ? $usechh2Items : [];
$company = $usechh2Company ?? null;
$employee = $usechh2Employee ?? null;
$doctor = $usechh2Doctor ?? null;
$chemicalName = trim((string) ($usechh2Chemical ?? ''));
$reportId = (int) (($usechh2Report->summary_employee_report_id ?? 0));
$companyId = (int) ($usechh2CompanyId ?? 0);
$declarationId = (int) ($usechh2DeclarationId ?? 0);
$employeeId = (int) ($usechh2EmployeeId ?? 0);
$surveillanceId = (int) ($usechh2SurveillanceId ?? 0);
$workerName = trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? '')));
$workerName = $workerName !== '' ? $workerName : 'Not recorded';

$formatDate = static function (?string $value, string $format = 'd M Y'): string {
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
<title>USECHH 2</title>
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

.record-sections {
    display: grid;
    gap: 20px;
    margin-top: 22px;
}

.record-section {
    padding: 20px 22px;
    border: 1px solid #edf0f2;
    border-radius: 14px;
    background: #fff;
}

.record-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
}

.record-section-title {
    margin: 0;
    font-size: 1.08rem;
    font-weight: 700;
}

.record-badge {
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
    position: relative;
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

.pdf-code {
    position: absolute;
    right: 0;
    top: 0;
    font-size: 11px;
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
    vertical-align: top;
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
    white-space: pre-line;
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

.pdf-table .col-no { width: 3%; }
.pdf-table .col-date { width: 8%; }
.pdf-table .col-assessment { width: 12%; }
.pdf-table .col-history { width: 10%; }
.pdf-table .col-clinical { width: 9%; }
.pdf-table .col-target { width: 20%; }
.pdf-table .col-bm { width: 9%; }
.pdf-table .col-work { width: 10%; }
.pdf-table .col-conclusion { width: 10%; }
.pdf-table .col-mrp { width: 9%; }
.pdf-table .col-doctor { width: 13%; }
.pdf-table .nowrap {
    white-space: nowrap;
    word-break: normal;
    overflow-wrap: normal;
}

.pdf-table .doctor-cell {
    font-size: 9px;
    line-height: 1.2;
    font-weight: 700;
}

@media (max-width: 980px) {
    .form-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .record-section-head {
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
        <section class="no-print" style="padding:22px 24px 0; display:flex; justify-content:flex-end;">
            <div class="head-actions">
                <a class="head-btn" href="<?php echo $esc(function_exists('route') ? route('general.report.folder', array_filter([
                    'module' => 'surveillance',
                    'company' => trim((string) ($company->company_name ?? '')),
                    'date' => trim((string) ($usechh2LatestDateRaw ?? '')),
                    'tab' => 'usechh 2',
                ], static fn ($value) => $value !== '')) : '#'); ?>">Back</a>
                <a class="head-btn" href="<?php echo $esc(function_exists('route') ? route('pdf.usechh2', array_filter([
                    'declaration_id' => $declarationId,
                    'employee_id' => $employeeId,
                    'company_id' => $companyId,
                    'surveillance_id' => $surveillanceId,
                    'group_chemical' => $chemicalName,
                ], static fn ($value) => $value !== '' && $value !== 0)) : '#'); ?>">Download PDF</a>
            </div>
        </section>

        <form method="post" action="<?php echo $esc(function_exists('route') ? route('surveillance.report.summary-employee.save') : '#'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="summary_employee_report_id" value="<?php echo $esc((string) $reportId); ?>">
            <input type="hidden" name="employee_id" value="<?php echo $esc((string) $employeeId); ?>">
            <input type="hidden" name="company_id" value="<?php echo $esc((string) $companyId); ?>">
            <input type="hidden" name="group_chemical" value="<?php echo $esc($chemicalName); ?>">

            <section class="page-card">
                <div class="page-pad">
                    <div class="hero">
                        <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
                        <div class="law">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                        <div class="title">SUMMARY REPORTS OF EMPLOYEE</div>
                    </div>

                    <div class="meta-grid">
                        <div class="meta-box">
                            <h2 class="section-title">Report Details</h2>
                            <div class="form-grid">
                                <div class="form-field">
                                    <label class="form-label">Name of Worker</label>
                                    <div class="form-value"><?php echo $esc($workerName); ?></div>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Name of chemical</label>
                                    <div class="form-value"><?php echo $esc($chemicalName !== '' ? $chemicalName : '-'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($items === []): ?>
                        <div class="empty-state">No completed USECHH 2 records were found for this worker and chemical yet.</div>
                    <?php else: ?>
                        <div class="record-sections">
                            <?php foreach ($items as $index => $item): ?>
                                <section class="record-section">
                                    <input type="hidden" name="items[<?php echo $esc((string) $index); ?>][item_id]" value="<?php echo $esc((string) ($item['summary_employee_report_item_id'] ?? '')); ?>">

                                    <div class="record-section-head">
                                        <h3 class="record-section-title">Examination Record</h3>
                                        <span class="record-badge">Record <?php echo $esc((string) ($index + 1)); ?></span>
                                    </div>

                                    <div class="form-grid">
                                        <div class="form-field">
                                            <label class="form-label">MS Date</label>
                                            <div class="form-value"><?php echo $esc($item['ms_date'] ?? ''); ?></div>
                                        </div>
                                        <div class="form-field full">
                                            <label class="form-label">Type of Assessment</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['assessment_type'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][assessment_type]"><?php echo $esc($item['assessment_type'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">History of Health Effects due to CHTH Exposure</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['history_effect'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][history_effect]"><?php echo $esc($item['history_effect'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">Clinical Findings</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['clinical_findings'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][clinical_findings]"><?php echo $esc($item['clinical_findings'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field full">
                                            <label class="form-label">Target Organ Function Test (Specify Organ)</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['target_organ_function'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][target_organ_function]"><?php echo $esc($item['target_organ_function'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">BEI Determinants</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['bei_determinants'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][bei_determinants]"><?php echo $esc($item['bei_determinants'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">Work Relatedness</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['work_relatedness'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][work_relatedness]"><?php echo $esc($item['work_relatedness'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">Conclusion of MS Finding</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['conclusion'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][conclusion]"><?php echo $esc($item['conclusion'] ?? ''); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">Date of MRP</label>
                                            <?php if ($viewMode): ?>
                                                <div class="form-value multiline"><?php echo $esc($item['mrp_date'] ?? ''); ?></div>
                                            <?php else: ?>
                                                <textarea name="items[<?php echo $esc((string) $index); ?>][mrp_date]"><?php echo $esc($item['mrp_date'] ?? ''); ?></textarea>
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
        <?php require dirname(__DIR__) . '/partials/clinic_header.php'; ?>
    </div>
    <div class="pdf-title">
        <div class="pdf-code">USECHH 2</div>
        <div class="law">Occupational Safety and Health Act 1994 (Act 514)</div>
        <div class="law">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
        <div class="main">SUMMARY REPORTS OF EMPLOYEE</div>
    </div>

    <table class="pdf-meta">
        <tr>
            <td class="label">Name of Worker</td>
            <td><?php echo $esc($workerName); ?></td>
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
            <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td><?php echo $esc((string) ($index + 1)); ?></td>
                    <td class="nowrap"><?php echo $esc($item['ms_date'] ?? ''); ?></td>
                    <td><?php echo $esc($item['assessment_type'] ?? ''); ?></td>
                    <td><?php echo $esc($item['history_effect'] ?? ''); ?></td>
                    <td><?php echo $esc($item['clinical_findings'] ?? ''); ?></td>
                    <td><?php echo $esc($item['target_organ_function'] ?? ''); ?></td>
                    <td><?php echo $esc($item['bei_determinants'] ?? ''); ?></td>
                    <td><?php echo $esc($item['work_relatedness'] ?? ''); ?></td>
                    <td><?php echo $esc($item['conclusion'] ?? ''); ?></td>
                    <td><?php echo $esc($item['mrp_date'] ?? ''); ?></td>
                    <td class="doctor-cell"><?php echo $esc($item['doctor'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
