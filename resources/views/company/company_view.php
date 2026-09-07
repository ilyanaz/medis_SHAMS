<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Company</title>
    <style>
        :root{--line:#e5e7eb;--bg:rgba(15,23,42,.55);--panel:#fff;--text:#111827;--muted:#6b7280;--green:#389B5B}
        *{box-sizing:border-box}
        body{margin:0;font-family:"Poppins","Segoe UI",Tahoma,Geneva,Verdana,sans-serif;background:#f3f4f6}
        .overlay{min-height:100vh;background:var(--bg);display:grid;place-items:center;padding:24px}
        .modal{width:min(980px,100%);background:var(--panel);border-radius:20px;box-shadow:0 20px 55px rgba(15,23,42,.25);padding:26px}
        h1{margin:0 0 6px;font-size:2.1rem}
        .muted{margin:0;color:var(--muted)}
        .panel{margin-top:18px;border:1px solid #e8ebf2;border-radius:16px;padding:16px}
        .subpanel{margin-top:18px;border:1px solid #e8ebf2;border-radius:16px;padding:16px;background:#fbfcfd}
        .grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
        .field{display:block;font-size:.9rem;color:#1f2937}
        input,select{width:100%;border:1px solid #d7dce7;border-radius:10px;padding:11px 12px;font:inherit;background:#f8fafc;color:#334155}
        .full{grid-column:1/-1}
        .phone-group{display:grid;grid-template-columns:92px 1fr;gap:8px}
        .choice-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
        .choice-card{position:relative;border:1px solid #d7dce7;border-radius:14px;padding:14px 16px;background:#fff}
        .choice-card strong{display:block;color:#111827}
        .choice-card.is-active{border-color:#389B5B;background:#eef7f0;box-shadow:0 0 0 1px rgba(56,155,91,.16)}
        .actions{margin-top:14px;display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap}
        .btn{border:1px solid #d1d5db;border-radius:10px;padding:10px 14px;background:#fff;color:#374151;text-decoration:none;font-size:.92rem;display:inline-flex;align-items:center;gap:6px;cursor:pointer}
        .btn.primary{background:var(--green);border-color:var(--green);color:#fff;font-weight:600}
        .field input,.field select,.field .phone-group,.field .choice-grid{margin-top:6px}
        .section-title{margin:0;font-size:1rem;font-weight:700;color:#111827}
        .empty-note{margin:12px 0 0;color:var(--muted)}
        .repeat-list{display:grid;gap:12px;margin-top:14px}
        .repeat-card{border:1px solid #e8ebf2;border-radius:14px;padding:14px;background:#fff}
        .repeat-card-title{font-size:.92rem;font-weight:700;color:#111827;margin-bottom:10px}
        .nested-chemical-list{display:grid;gap:10px;margin-top:12px}
        .nested-chemical-row{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(180px,.75fr) 120px;gap:10px;align-items:end}
        .hidden{display:none}
        @media (max-width:760px){.nested-chemical-row{grid-template-columns:1fr}}
        @media (max-width:760px){.grid,.choice-grid{grid-template-columns:1fr}.phone-group{grid-template-columns:1fr}}
    </style>
</head>
<body>
<?php
require_once __DIR__ . '/view_bootstrap.php';

$esc = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$companyRecord = $companyRecord ?? null;
$companyFormData = is_array($companyFormData ?? null) ? $companyFormData : [];
$countryCodes = config('country_codes', []);
$backUrl = function_exists('route') ? route('panel.company_list') : '#';
$editUrl = ($companyRecord && function_exists('route')) ? route('panel.company.edit', ['company' => $companyRecord->company_id]) : '#';
$phoneRaw = trim((string) ($companyRecord->company_telephone ?? ''));
$phoneCode = '+60';
$phoneNumber = $phoneRaw;
if ($phoneRaw !== '' && preg_match('/^(\+\d{1,3})\s*(.*)$/', $phoneRaw, $matches) === 1) {
    $phoneCode = $matches[1];
    $phoneNumber = $matches[2];
}
$selectedModule = strtolower((string) ($companyRecord->company_module ?? 'surveillance'));
$workUnits = $companyFormData['work_unit_name'] ?? [''];
$workUnits = is_array($workUnits) && $workUnits !== [] ? array_values($workUnits) : [''];
$workUnitChemicals = is_array($companyFormData['work_unit_chemical_name'] ?? null) ? $companyFormData['work_unit_chemical_name'] : [];
$workUnitChraReports = is_array($companyFormData['work_unit_chemical_chra_report_no'] ?? null) ? $companyFormData['work_unit_chemical_chra_report_no'] : [];
$workUnitWorkers = is_array($companyFormData['work_unit_chemical_total_workers'] ?? null) ? $companyFormData['work_unit_chemical_total_workers'] : [];
$hasWorkUnitContent = false;
foreach ($workUnits as $workUnitIndex => $workUnitName) {
    $chemicalNames = (array) ($workUnitChemicals[$workUnitIndex] ?? []);
    $chemicalChraReports = (array) ($workUnitChraReports[$workUnitIndex] ?? []);
    $chemicalWorkers = (array) ($workUnitWorkers[$workUnitIndex] ?? []);
    if (trim((string) $workUnitName) !== '' || array_filter(array_merge($chemicalNames, $chemicalChraReports, $chemicalWorkers), static fn ($value) => trim((string) $value) !== '') !== []) {
        $hasWorkUnitContent = true;
        break;
    }
}
?>
<div class="overlay">
    <div class="modal">
        <h1>Company Details</h1>
        <div class="panel">
            <div class="grid">
                <label class="field full">
                    Display In
                    <div class="choice-grid">
                        <div class="choice-card<?php echo $selectedModule === 'surveillance' ? ' is-active' : ''; ?>">
                            <strong>Surveillance</strong>
                        </div>
                        <div class="choice-card<?php echo $selectedModule === 'audiometry' ? ' is-active' : ''; ?>">
                            <strong>Audiometry</strong>
                        </div>
                    </div>
                </label>
                <label class="field">
                    Company Name
                    <input type="text" value="<?php echo $esc($companyRecord->company_name ?? ''); ?>" readonly>
                </label>
                <label class="field">
                    MYKPP Registration No
                    <input type="text" value="<?php echo $esc($companyRecord->mykpp_registration_no ?? ''); ?>" readonly>
                </label>
                <label class="field full">
                    Company Address
                    <input type="text" value="<?php echo $esc($companyRecord->company_address ?? ''); ?>" readonly>
                </label>
                <label class="field">
                    Company Postcode
                    <input type="text" value="<?php echo $esc($companyRecord->company_postcode ?? ''); ?>" readonly>
                </label>
                <label class="field">
                    Company District
                    <input type="text" value="<?php echo $esc($companyRecord->company_district ?? ''); ?>" readonly>
                </label>
                <label class="field">
                    Company State
                    <input type="text" value="<?php echo $esc($companyRecord->company_state ?? ''); ?>" readonly>
                </label>
                <label class="field">
                    Company Telephone
                    <div class="phone-group">
                        <select disabled>
                            <option value="">Code</option>
                            <?php foreach ($countryCodes as $country): $code = (string) ($country['code'] ?? '+60'); ?>
                                <option value="<?php echo $esc($code); ?>"<?php echo $phoneCode === $code ? ' selected' : ''; ?>><?php echo $esc($code); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" value="<?php echo $esc($phoneNumber); ?>" readonly>
                    </div>
                </label>
                <label class="field">
                    Company Email
                    <input type="text" value="<?php echo $esc($companyRecord->company_email ?? ''); ?>" readonly>
                </label>
                <label class="field">
                    Company Fax
                    <input type="text" value="<?php echo $esc($companyRecord->company_fax ?? ''); ?>" readonly>
                </label>
                <label class="field">
                    Total Workers
                    <input type="text" value="<?php echo $esc((string) ($companyRecord->total_workers ?? '0')); ?>" readonly>
                </label>
            </div>
        </div>
        <div class="subpanel<?php echo $selectedModule === 'surveillance' ? '' : ' hidden'; ?>">
            <h2 class="section-title">Work Unit Information</h2>
            <?php if ($hasWorkUnitContent): ?>
                <div class="repeat-list">
                    <?php foreach ($workUnits as $workUnitIndex => $workUnitName): ?>
                        <?php
                        $chemicalNames = (array) ($workUnitChemicals[$workUnitIndex] ?? ['']);
                        $chemicalChraReports = (array) ($workUnitChraReports[$workUnitIndex] ?? ['']);
                        $chemicalWorkers = (array) ($workUnitWorkers[$workUnitIndex] ?? ['']);
                        $chemicalRowCount = max(count($chemicalNames), count($chemicalChraReports), count($chemicalWorkers), 1);
                        $workUnitHasContent = trim((string) $workUnitName) !== '' || array_filter(array_merge($chemicalNames, $chemicalChraReports, $chemicalWorkers), static fn ($value) => trim((string) $value) !== '') !== [];
                        if (! $workUnitHasContent) {
                            continue;
                        }
                        ?>
                        <div class="repeat-card">
                            <div class="repeat-card-title">Work Unit <?php echo $workUnitIndex + 1; ?></div>
                            <div class="grid">
                                <label class="field full">
                                    Work Unit Name
                                    <input type="text" value="<?php echo $esc((string) $workUnitName); ?>" readonly>
                                </label>
                            </div>
                            <div class="nested-chemical-list">
                                <?php for ($chemicalIndex = 0; $chemicalIndex < $chemicalRowCount; $chemicalIndex++): ?>
                                    <?php
                                    $chemicalName = (string) ($chemicalNames[$chemicalIndex] ?? '');
                                    $chraReportNo = (string) ($chemicalChraReports[$chemicalIndex] ?? '');
                                    $totalWorkers = (string) ($chemicalWorkers[$chemicalIndex] ?? '');
                                    if (trim($chemicalName) === '' && trim($chraReportNo) === '' && trim($totalWorkers) === '') {
                                        continue;
                                    }
                                    ?>
                                    <div class="nested-chemical-row">
                                        <label class="field">
                                            Chemical Name
                                            <input type="text" value="<?php echo $esc($chemicalName); ?>" readonly>
                                        </label>
                                        <label class="field">
                                            CHRA Report No.
                                            <input type="text" value="<?php echo $esc($chraReportNo); ?>" readonly>
                                        </label>
                                        <label class="field">
                                            Total Workers
                                            <input type="text" value="<?php echo $esc($totalWorkers); ?>" readonly>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-note">No work unit information saved.</p>
            <?php endif; ?>
        </div>
        <div class="actions">
            <a class="btn" href="<?php echo $esc($backUrl); ?>">Back</a>
            <a class="btn primary" href="<?php echo $esc($editUrl); ?>">Edit Company</a>
        </div>
    </div>
</div>
</body>
</html>
