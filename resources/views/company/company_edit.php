<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Company</title>
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
        input,select{width:100%;border:1px solid #d7dce7;border-radius:10px;padding:11px 12px;font:inherit}
        .full{grid-column:1/-1}
        .phone-group{display:grid;grid-template-columns:92px 1fr;gap:8px}
        .choice-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
        .choice-card{position:relative;border:1px solid #d7dce7;border-radius:14px;padding:14px 16px;background:#fff;cursor:pointer}
        .choice-card input{position:absolute;opacity:0;pointer-events:none}
        .choice-card strong{display:block;color:#111827}
        .choice-card.is-active{border-color:#389B5B;background:#eef7f0;box-shadow:0 0 0 1px rgba(56,155,91,.16)}
        .actions{margin-top:14px;display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap}
        .btn{border:1px solid #d1d5db;border-radius:10px;padding:10px 14px;background:#fff;color:#374151;text-decoration:none;font-size:.92rem;display:inline-flex;align-items:center;gap:6px;cursor:pointer}
        .btn.primary{background:var(--green);border-color:var(--green);color:#fff;font-weight:600}
        .btn.small{padding:8px 12px;font-size:.85rem}
        .btn.danger{color:#dc2626;border-color:#fecaca;background:#fff}
        .field input,.field select,.field .phone-group,.field .choice-grid{margin-top:6px}
        .req{color:#dc2626}
        .error{margin-top:14px;padding:12px 14px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;border-radius:12px}
        .section-title{margin:0;font-size:1rem;font-weight:700;color:#111827}
        .section-note{margin:6px 0 0;color:#6b7280;font-size:.88rem}
        .repeat-list{display:grid;gap:12px;margin-top:14px}
        .repeat-card{border:1px solid #e8ebf2;border-radius:14px;padding:14px;background:#fff}
        .repeat-card-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}
        .repeat-card-title{font-size:.92rem;font-weight:700;color:#111827}
        .repeat-actions{display:flex;justify-content:flex-end;margin-top:12px}
        .nested-chemical-list{display:grid;gap:10px;margin-top:12px}
        .nested-chemical-row{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(180px,.75fr) 120px 84px;gap:10px;align-items:end}
        .nested-chemical-row .field{margin:0}
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
$csrfToken = function_exists('csrf_token') ? (string) csrf_token() : '';
$hasErrors = isset($errors) && method_exists($errors, 'any') && $errors->any();
$firstError = $hasErrors ? (string) $errors->first() : '';
$countryCodes = config('country_codes', []);
$old = static function (string $key, string $default = '') use ($companyFormData) {
    $fallback = array_key_exists($key, $companyFormData) ? (string) $companyFormData[$key] : $default;
    return function_exists('old') ? (string) old($key, $fallback) : $fallback;
};
$backUrl = function_exists('route') ? route('panel.company_list') : '#';
$formAction = ($companyRecord && function_exists('route')) ? route('panel.company.update', ['company' => $companyRecord->company_id]) : '#';
$phoneRaw = trim($old('company_telephone', (string) ($companyRecord->company_telephone ?? '')));
$phoneCode = '+60';
$phoneNumber = $phoneRaw;
if ($phoneRaw !== '' && preg_match('/^(\+\d{1,3})\s*(.*)$/', $phoneRaw, $matches) === 1) {
    $phoneCode = $matches[1];
    $phoneNumber = $matches[2];
}
$phoneCode = $old('company_phone_code', $phoneCode ?: '+60');
$selectedModule = strtolower($old('company_module', (string) ($companyRecord->company_module ?? 'surveillance')));
$workUnits = old('work_unit_name', ['']);
$workUnits = is_array($workUnits) && $workUnits !== [] ? array_values($workUnits) : [''];
$workUnitChemicals = old('work_unit_chemical_name', []);
$workUnitChraReports = old('work_unit_chemical_chra_report_no', []);
$workUnitWorkers = old('work_unit_chemical_total_workers', []);
?>
<div class="overlay">
    <form class="modal" method="post" action="<?php echo $esc($formAction); ?>" id="editCompanyForm" novalidate>
        <input type="hidden" name="_token" value="<?php echo $esc($csrfToken); ?>">
        <input type="hidden" name="_method" value="PUT">
        <h1>Edit Company</h1>
        <?php if ($hasErrors): ?>
            <div class="error"><?php echo $esc($firstError); ?></div>
        <?php endif; ?>
        <div class="panel">
            <div class="grid">
                <label class="field full">
                    Display In <span class="req">*</span>
                    <div class="choice-grid">
                        <label class="choice-card<?php echo $selectedModule === 'surveillance' ? ' is-active' : ''; ?>">
                            <input type="radio" name="company_module" value="surveillance"<?php echo $selectedModule === 'surveillance' ? ' checked' : ''; ?> required>
                            <strong>Surveillance</strong>
                        </label>
                        <label class="choice-card<?php echo $selectedModule === 'audiometry' ? ' is-active' : ''; ?>">
                            <input type="radio" name="company_module" value="audiometry"<?php echo $selectedModule === 'audiometry' ? ' checked' : ''; ?> required>
                            <strong>Audiometry</strong>
                        </label>
                    </div>
                </label>
                <label class="field">
                    Company Name <span class="req">*</span>
                    <input name="company_name" type="text" value="<?php echo $esc($old('company_name', (string) ($companyRecord->company_name ?? ''))); ?>" required>
                </label>
                <label class="field">
                    MYKPP Registration No <span class="req">*</span>
                    <input name="mykpp_registration_no" type="text" value="<?php echo $esc($old('mykpp_registration_no', (string) ($companyRecord->mykpp_registration_no ?? ''))); ?>" required>
                </label>
                <label class="field full">
                    Company Address
                    <input name="company_address" type="text" value="<?php echo $esc($old('company_address', (string) ($companyRecord->company_address ?? ''))); ?>" placeholder="Enter address">
                </label>
                <label class="field">
                    Company Postcode
                    <input name="company_postcode" type="text" value="<?php echo $esc($old('company_postcode', (string) ($companyRecord->company_postcode ?? ''))); ?>" placeholder="Enter postcode" pattern="^[0-9]{4,10}$">
                </label>
                <label class="field">
                    Company District
                    <input name="company_district" type="text" value="<?php echo $esc($old('company_district', (string) ($companyRecord->company_district ?? ''))); ?>" placeholder="Enter district">
                </label>
                <label class="field">
                    Company State
                    <input name="company_state" type="text" value="<?php echo $esc($old('company_state', (string) ($companyRecord->company_state ?? ''))); ?>" placeholder="Enter state">
                </label>
                <label class="field">
                    Company Telephone
                    <div class="phone-group">
                        <select name="company_phone_code">
                            <option value="">Code</option>
                            <?php foreach ($countryCodes as $country): $code = (string) ($country['code'] ?? '+60'); ?>
                                <option value="<?php echo $esc($code); ?>"<?php echo $phoneCode === $code ? ' selected' : ''; ?>><?php echo $esc($code); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="company_telephone" type="tel" value="<?php echo $esc($phoneNumber); ?>" inputmode="numeric" placeholder="Phone number" pattern="^[0-9]{7,12}$">
                    </div>
                </label>
                <label class="field">
                    Company Email
                    <input name="company_email" type="email" value="<?php echo $esc($old('company_email', (string) ($companyRecord->company_email ?? ''))); ?>" placeholder="Enter email">
                </label>
                <label class="field">
                    Company Fax
                    <input name="company_fax" type="text" value="<?php echo $esc($old('company_fax', (string) ($companyRecord->company_fax ?? ''))); ?>" placeholder="Enter fax">
                </label>
            </div>
        </div>
        <div class="subpanel<?php echo $selectedModule === 'surveillance' ? '' : ' hidden'; ?>" id="workUnitSection">
            <h2 class="section-title">Work Unit Information</h2>
            <div class="repeat-list" id="workUnitList">
                <?php foreach ($workUnits as $workUnitIndex => $workUnitName): ?>
                    <?php
                    $chemicalNames = $workUnitChemicals[$workUnitIndex] ?? [''];
                    $chemicalChraReports = $workUnitChraReports[$workUnitIndex] ?? [''];
                    $chemicalWorkers = $workUnitWorkers[$workUnitIndex] ?? [''];
                    $chemicalRowCount = max(count((array) $chemicalNames), count((array) $chemicalChraReports), count((array) $chemicalWorkers), 1);
                    ?>
                    <div class="repeat-card" data-work-unit-card>
                        <div class="repeat-card-head">
                            <div class="repeat-card-title">Work Unit <?php echo $workUnitIndex + 1; ?></div>
                            <button class="btn danger small" type="button" data-remove-work-unit>Delete</button>
                        </div>
                        <div class="grid">
                            <label class="field full">
                                Work Unit Name
                                <input type="text" name="work_unit_name[]" value="<?php echo $esc((string) $workUnitName); ?>" placeholder="Enter work unit name">
                            </label>
                        </div>
                        <div class="nested-chemical-list" data-chemical-list>
                            <?php for ($chemicalIndex = 0; $chemicalIndex < $chemicalRowCount; $chemicalIndex++): ?>
                                <div class="nested-chemical-row" data-chemical-row>
                                    <label class="field">
                                        Chemical Name
                                        <input type="text" name="work_unit_chemical_name[<?php echo $workUnitIndex; ?>][]" value="<?php echo $esc((string) ($chemicalNames[$chemicalIndex] ?? '')); ?>" placeholder="Enter chemical name">
                                    </label>
                                    <label class="field">
                                        CHRA Report No.
                                        <input type="text" name="work_unit_chemical_chra_report_no[<?php echo $workUnitIndex; ?>][]" value="<?php echo $esc((string) ($chemicalChraReports[$chemicalIndex] ?? '')); ?>" placeholder="Enter CHRA report no.">
                                    </label>
                                    <label class="field">
                                        Total Workers
                                        <input type="number" min="0" name="work_unit_chemical_total_workers[<?php echo $workUnitIndex; ?>][]" value="<?php echo $esc((string) ($chemicalWorkers[$chemicalIndex] ?? '')); ?>" placeholder="0">
                                    </label>
                                    <button class="btn danger small" type="button" data-remove-chemical-row style="width:100%;">Delete</button>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <div class="repeat-actions">
                            <button class="btn small" type="button" data-add-chemical-row>+ Add Chemical</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="repeat-actions">
                <button class="btn small" type="button" id="addWorkUnitBtn">+ Add Work Unit</button>
            </div>
        </div>
        <div class="actions">
            <a class="btn" href="<?php echo $esc($backUrl); ?>">Cancel</a>
            <button class="btn primary" type="submit">Update Company</button>
        </div>
    </form>
</div>
<template id="workUnitTemplate">
    <div class="repeat-card" data-work-unit-card>
        <div class="repeat-card-head">
            <div class="repeat-card-title">Work Unit</div>
            <button class="btn danger small" type="button" data-remove-work-unit>Delete</button>
        </div>
        <div class="grid">
            <label class="field full">
                Work Unit Name
                <input type="text" name="work_unit_name[]" placeholder="Enter work unit name">
            </label>
        </div>
        <div class="nested-chemical-list" data-chemical-list></div>
        <div class="repeat-actions">
            <button class="btn small" type="button" data-add-chemical-row>+ Add Chemical</button>
        </div>
    </div>
</template>
<template id="chemicalRowTemplate">
    <div class="nested-chemical-row" data-chemical-row>
        <label class="field">
            Chemical Name
            <input type="text" data-chemical-name placeholder="Enter chemical name">
        </label>
        <label class="field">
            CHRA Report No.
            <input type="text" data-chemical-chra placeholder="Enter CHRA report no.">
        </label>
        <label class="field">
            Total Workers
            <input type="number" min="0" data-chemical-workers placeholder="0">
        </label>
        <button class="btn danger small" type="button" data-remove-chemical-row style="width:100%;">Delete</button>
    </div>
</template>
<script>
(function () {
    var form = document.getElementById('editCompanyForm');
    var choiceCards = Array.prototype.slice.call(document.querySelectorAll('.choice-card'));
    var workUnitSection = document.getElementById('workUnitSection');
    var workUnitList = document.getElementById('workUnitList');
    var addWorkUnitBtn = document.getElementById('addWorkUnitBtn');
    var workUnitTemplate = document.getElementById('workUnitTemplate');
    var chemicalRowTemplate = document.getElementById('chemicalRowTemplate');

    function syncCards() {
        choiceCards.forEach(function (card) {
            var input = card.querySelector('input[name="company_module"]');
            card.classList.toggle('is-active', !!(input && input.checked));
        });
        var selectedInput = form.querySelector('input[name="company_module"]:checked');
        var isSurveillance = selectedInput && selectedInput.value === 'surveillance';
        if (workUnitSection) {
            workUnitSection.classList.toggle('hidden', !isSurveillance);
        }
    }

    function renumberWorkUnits() {
        Array.prototype.slice.call(workUnitList.querySelectorAll('[data-work-unit-card]')).forEach(function (card, workUnitIndex) {
            var title = card.querySelector('.repeat-card-title');
            if (title) {
                title.textContent = 'Work Unit ' + (workUnitIndex + 1);
            }
            Array.prototype.slice.call(card.querySelectorAll('[data-chemical-row]')).forEach(function (row) {
                var nameInput = row.querySelector('[data-chemical-name], input[name^="work_unit_chemical_name["]');
                var chraInput = row.querySelector('[data-chemical-chra], input[name^="work_unit_chemical_chra_report_no["]');
                var workerInput = row.querySelector('[data-chemical-workers], input[name^="work_unit_chemical_total_workers["]');
                if (nameInput) {
                    nameInput.name = 'work_unit_chemical_name[' + workUnitIndex + '][]';
                }
                if (chraInput) {
                    chraInput.name = 'work_unit_chemical_chra_report_no[' + workUnitIndex + '][]';
                }
                if (workerInput) {
                    workerInput.name = 'work_unit_chemical_total_workers[' + workUnitIndex + '][]';
                }
            });
        });
    }

    function buildChemicalRow() {
        return chemicalRowTemplate.content.cloneNode(true);
    }

    function addChemicalRow(card) {
        var list = card.querySelector('[data-chemical-list]');
        if (!list) {
            return;
        }
        list.appendChild(buildChemicalRow());
        renumberWorkUnits();
    }

    function buildWorkUnitCard() {
        var fragment = workUnitTemplate.content.cloneNode(true);
        var card = fragment.querySelector('[data-work-unit-card]');
        if (card) {
            var list = card.querySelector('[data-chemical-list]');
            if (list) {
                list.appendChild(buildChemicalRow());
            }
        }
        return fragment;
    }

    choiceCards.forEach(function (card) {
        card.addEventListener('click', function () {
            var input = card.querySelector('input[name="company_module"]');
            if (input) {
                input.checked = true;
                syncCards();
            }
        });
    });

    if (addWorkUnitBtn) {
        addWorkUnitBtn.addEventListener('click', function () {
            if (!workUnitList) {
                return;
            }
            workUnitList.appendChild(buildWorkUnitCard());
            renumberWorkUnits();
        });
    }

    if (workUnitList) {
        workUnitList.addEventListener('click', function (event) {
            var addChemicalButton = event.target.closest('[data-add-chemical-row]');
            if (addChemicalButton) {
                var card = addChemicalButton.closest('[data-work-unit-card]');
                if (card) {
                    addChemicalRow(card);
                }
                return;
            }

            var removeChemicalButton = event.target.closest('[data-remove-chemical-row]');
            if (removeChemicalButton) {
                var chemicalRow = removeChemicalButton.closest('[data-chemical-row]');
                var chemicalList = chemicalRow ? chemicalRow.parentElement : null;
                if (chemicalRow && chemicalList) {
                    chemicalRow.remove();
                    if (!chemicalList.querySelector('[data-chemical-row]')) {
                        chemicalList.appendChild(buildChemicalRow());
                    }
                    renumberWorkUnits();
                }
                return;
            }

            var removeWorkUnitButton = event.target.closest('[data-remove-work-unit]');
            if (removeWorkUnitButton) {
                var workUnitCard = removeWorkUnitButton.closest('[data-work-unit-card]');
                if (workUnitCard && workUnitList.querySelectorAll('[data-work-unit-card]').length > 1) {
                    workUnitCard.remove();
                    renumberWorkUnits();
                }
            }
        });
    }

    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
        }
    });

    syncCards();
    renumberWorkUnits();
})();
</script>
</body>
</html>
