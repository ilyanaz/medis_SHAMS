<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $esc($pageTitle ?? 'Patient'); ?></title>
<style>
:root{--line:#e5e7eb;--bg:rgba(15,23,42,.55);--panel:#fff;--text:#111827;--muted:#6b7280;--green:#389B5B;--red:#ef4444}
*{box-sizing:border-box}
body{margin:0;font-family:"Poppins","Segoe UI",Tahoma,Geneva,Verdana,sans-serif;background:#f3f4f6}
.overlay{min-height:100vh;background:var(--bg);display:grid;place-items:center;padding:24px}.modal{width:min(1060px,100%);background:var(--panel);border-radius:20px;box-shadow:0 20px 55px rgba(15,23,42,.25);padding:26px}
h1{margin:0 0 6px;font-size:2.1rem}.muted{margin:0;color:var(--muted)}
.panel{margin-top:18px;border:1px solid #e8ebf2;border-radius:16px;padding:16px}
.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.field{display:block;font-size:.9rem;color:#1f2937}
.field input,.field select,.field textarea,.field .phone-group{margin-top:6px}
input,select,textarea{width:100%;border:1px solid #d7dce7;border-radius:10px;padding:11px 12px;font:inherit;background:#fff}
input[readonly],textarea[readonly],select:disabled{background:#f8fafc;color:#475569}
textarea{min-height:90px;resize:vertical}.full{grid-column:1/-1}.phone-group{display:grid;grid-template-columns:92px 1fr;gap:8px}
.hidden{display:none}.actions{margin-top:18px;display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap}
.field-label-row{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.checkbox-inline{display:inline-flex;align-items:center;gap:8px;font-size:.86rem;color:#475569;margin-top:0;cursor:pointer}
.checkbox-inline input{width:18px;height:18px;margin:0;accent-color:#389B5B;cursor:pointer}
.btn{border:1px solid #d1d5db;border-radius:10px;padding:10px 14px;background:#fff;color:#374151;text-decoration:none;font-size:.92rem;display:inline-flex;align-items:center;gap:6px;cursor:pointer}
.btn.primary{background:var(--green);border-color:var(--green);color:#fff;font-weight:600}.req{color:#dc2626}.error{margin-top:14px;padding:12px 14px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;border-radius:12px}
.section-title{margin:0;font-size:1rem;font-weight:700;color:#111827}.section-header{margin-bottom:12px}
@media (max-width:860px){.grid{grid-template-columns:1fr}.phone-group{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php
$selectedCompany = $selectedCompany ?? null;
$patientRecord = $patientRecord ?? null;
$patientFormData = $patientFormData ?? [];
$readOnly = !empty($readOnly);
$selectedCompanyId = $selectedCompany->company_id ?? ($patientFormData['company_id'] ?? request()->query('company_id') ?? '');
$selectedCompanyAddress = $selectedCompany->company_address ?? '';
$selectedCompanyPostcode = $selectedCompany->company_postcode ?? '';
$selectedCompanyDistrict = $selectedCompany->company_district ?? '';
$selectedCompanyState = $selectedCompany->company_state ?? '';
$hasSelectedCompany = $selectedCompanyId !== '' && $selectedCompanyId !== null;
$countryCodes = config('country_codes', []);
$returnTo = (string) ($returnTo ?? (function_exists('route') ? route('surveillance.patient', array_filter(['company_id' => $selectedCompanyId])) : '#'));
$formAction = (string) ($formAction ?? '#');
$formMethod = strtoupper((string) ($formMethod ?? 'POST'));
$value = static fn ($key, $default = '') => old($key, $patientFormData[$key] ?? $default);
$isOtherEthnicity = $value('employee_ethnicity') === 'Others';
$isOtherCitizenship = $value('employee_citizenship') === 'Others';
$isOtherMarital = $value('employee_martialStatus') === 'Others';
$showMaritalExtra = $value('employee_martialStatus') !== '' && $value('employee_martialStatus') !== 'Single';
$editUrl = $patientRecord && function_exists('route') ? route('surveillance.patient.edit', array_filter(['employee' => $patientRecord->employee_id, 'company_id' => $selectedCompanyId, 'return_to' => $returnTo])) : '#';
?>
<div class="overlay">
<form class="modal" method="post" action="<?php echo $esc($formAction); ?>" id="patientBasicForm" novalidate>
<?php if ($formMethod !== 'GET'): ?><input type="hidden" name="_token" value="<?php echo $esc(csrf_token()); ?>"><?php endif; ?>
<?php if (!in_array($formMethod, ['GET', 'POST'], true)): ?><input type="hidden" name="_method" value="<?php echo $esc($formMethod); ?>"><?php endif; ?>
<input type="hidden" name="company_id" value="<?php echo $esc($selectedCompanyId); ?>">
<input type="hidden" name="return_to" value="<?php echo $esc($returnTo); ?>">
<h1><?php echo $esc($pageTitle ?? 'Patient'); ?></h1>
<?php if (isset($errors) && $errors->any()): ?><div class="error"><?php echo $esc($errors->first()); ?></div><?php endif; ?>

<div class="panel">
<div class="section-header"><div class="section-title">Patient Details</div></div>
<div class="grid">
<label class="field">First Name<?php if (!$readOnly): ?> <span class="req">*</span><?php endif; ?><input name="employee_firstName" type="text" value="<?php echo $esc($value('employee_firstName')); ?>" placeholder="Enter first name" <?php echo $readOnly ? 'readonly' : 'required'; ?>></label>
<label class="field">Last Name<?php if (!$readOnly): ?> <span class="req">*</span><?php endif; ?><input name="employee_lastName" type="text" value="<?php echo $esc($value('employee_lastName')); ?>" placeholder="Enter last name" <?php echo $readOnly ? 'readonly' : 'required'; ?>></label>
<label class="field">NRIC<input id="employee_NRIC" name="employee_NRIC" type="text" value="<?php echo $esc($value('employee_NRIC')); ?>" placeholder="Enter NRIC" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Passport No<input id="employee_passportNo" name="employee_passportNo" type="text" value="<?php echo $esc($value('employee_passportNo')); ?>" placeholder="Enter passport number" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Date of Birth<input name="employee_DOB" type="date" value="<?php echo $esc($value('employee_DOB')); ?>" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Gender<select name="employee_gender" <?php echo $readOnly ? 'disabled' : ''; ?>><option value="">Select</option><option value="Male"<?php echo $value('employee_gender') === 'Male' ? ' selected' : ''; ?>>Male</option><option value="Female"<?php echo $value('employee_gender') === 'Female' ? ' selected' : ''; ?>>Female</option></select></label>
<label class="field full"><span class="field-label-row"><span>Address</span><?php if ($hasSelectedCompany): ?><span class="checkbox-inline"><input type="checkbox" id="sameCompanyAddress" <?php echo $readOnly ? 'disabled' : ''; ?>> Same as company address</span><?php endif; ?></span><input id="employee_address" name="employee_address" type="text" value="<?php echo $esc($value('employee_address')); ?>" placeholder="Enter address" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Postcode<input id="employee_postcode" name="employee_postcode" type="text" value="<?php echo $esc($value('employee_postcode')); ?>" placeholder="Enter postcode" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">District<input id="employee_district" name="employee_district" type="text" value="<?php echo $esc($value('employee_district')); ?>" placeholder="Enter district" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">State<input id="employee_state" name="employee_state" type="text" value="<?php echo $esc($value('employee_state')); ?>" placeholder="Enter state" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Telephone<div class="phone-group"><select name="employee_phone_code" <?php echo $readOnly ? 'disabled' : ''; ?>><option value="">Code</option><?php foreach ($countryCodes as $country): $code = (string) ($country['code'] ?? '+60'); ?><option value="<?php echo $esc($code); ?>"<?php echo $value('employee_phone_code', '+60') === $code ? ' selected' : ''; ?>><?php echo $esc($code); ?></option><?php endforeach; ?></select><input name="employee_telephone" type="tel" value="<?php echo $esc($value('employee_telephone')); ?>" inputmode="numeric" placeholder="Phone number" <?php echo $readOnly ? 'readonly' : ''; ?>></div></label>
<label class="field">Email<input name="employee_email" type="email" value="<?php echo $esc($value('employee_email')); ?>" placeholder="Enter email" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Ethnicity<select name="employee_ethnicity" id="employee_ethnicity" <?php echo $readOnly ? 'disabled' : ''; ?>><option value="">Select</option><?php foreach(['Malay','Chinese','Indian','Orang Asli','Others'] as $option): ?><option value="<?php echo $esc($option); ?>"<?php echo $value('employee_ethnicity') === $option ? ' selected' : ''; ?>><?php echo $esc($option); ?></option><?php endforeach; ?></select></label>
<label class="field<?php echo $isOtherEthnicity ? '' : ' hidden'; ?>" id="employee_ethnicity_other_wrap">Ethnicity (Please justify)<textarea id="employee_ethnicity_other" name="employee_ethnicity_other" placeholder="Write your justification" <?php echo $readOnly ? 'readonly' : ''; ?>><?php echo $esc($value('employee_ethnicity_other')); ?></textarea></label>
<label class="field">Citizenship<select name="employee_citizenship" id="employee_citizenship" <?php echo $readOnly ? 'disabled' : ''; ?>><option value="">Select</option><?php foreach(['Malaysian Citizen','Others'] as $option): ?><option value="<?php echo $esc($option); ?>"<?php echo $value('employee_citizenship') === $option ? ' selected' : ''; ?>><?php echo $esc($option); ?></option><?php endforeach; ?></select></label>
<label class="field<?php echo $isOtherCitizenship ? '' : ' hidden'; ?>" id="employee_citizenship_other_wrap">Citizenship (Please justify)<textarea id="employee_citizenship_other" name="employee_citizenship_other" placeholder="Write your justification" <?php echo $readOnly ? 'readonly' : ''; ?>><?php echo $esc($value('employee_citizenship_other')); ?></textarea></label>
<label class="field">Marital Status<select name="employee_martialStatus" id="employee_martialStatus" <?php echo $readOnly ? 'disabled' : ''; ?>><option value="">Select</option><?php foreach(['Single','Married','Others'] as $option): ?><option value="<?php echo $esc($option); ?>"<?php echo $value('employee_martialStatus') === $option ? ' selected' : ''; ?>><?php echo $esc($option); ?></option><?php endforeach; ?></select></label>
<label class="field<?php echo $isOtherMarital ? '' : ' hidden'; ?>" id="employee_martial_other_wrap">Marital Status (Please justify)<textarea id="employee_martial_other" name="employee_martial_other" placeholder="Write your justification" <?php echo $readOnly ? 'readonly' : ''; ?>><?php echo $esc($value('employee_martial_other')); ?></textarea></label>
<label class="field<?php echo $showMaritalExtra ? '' : ' hidden'; ?>" id="no_of_children_wrap">No. of Children<input id="no_of_children" name="no_of_children" type="number" min="0" value="<?php echo $esc($value('no_of_children')); ?>" placeholder="Enter number of children" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field<?php echo $showMaritalExtra ? '' : ' hidden'; ?>" id="years_married_wrap">Years Married<input id="years_married" name="years_married" type="number" min="0" value="<?php echo $esc($value('years_married')); ?>" placeholder="Enter years married" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
</div>
</div>

<div class="actions">
<a class="btn" href="<?php echo $esc($returnTo); ?>"><?php echo $readOnly ? 'Back' : 'Cancel'; ?></a>
<div style="display:flex;gap:8px;flex-wrap:wrap;">
<?php if ($readOnly): ?><a class="btn primary" href="<?php echo $esc($editUrl); ?>">Edit Patient</a><?php else: ?><button class="btn primary" type="submit"><?php echo $esc($submitLabel ?? 'Save'); ?></button><?php endif; ?>
</div>
</div>
</form>
</div>

<script>
(function(){
const form=document.getElementById('patientBasicForm');
const marital=document.getElementById('employee_martialStatus');
const children=document.getElementById('no_of_children');
const years=document.getElementById('years_married');
const childrenWrap=document.getElementById('no_of_children_wrap');
const yearsWrap=document.getElementById('years_married_wrap');
const sameCompanyAddress=document.getElementById('sameCompanyAddress');
const addressInput=document.getElementById('employee_address');
const postcodeInput=document.getElementById('employee_postcode');
const districtInput=document.getElementById('employee_district');
const stateInput=document.getElementById('employee_state');
const companyAddress=<?php echo json_encode((string) $selectedCompanyAddress, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const companyPostcode=<?php echo json_encode((string) $selectedCompanyPostcode, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const companyDistrict=<?php echo json_encode((string) $selectedCompanyDistrict, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const companyState=<?php echo json_encode((string) $selectedCompanyState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const setupOthers=function(selectId,wrapId,inputId){const select=document.getElementById(selectId);const wrap=document.getElementById(wrapId);const input=document.getElementById(inputId);if(!select||!wrap||!input){return;}const sync=function(){const isOther=select.value==='Others';wrap.classList.toggle('hidden',!isOther);if(!isOther){input.value='';}};select.addEventListener('change',sync);sync();};
setupOthers('employee_ethnicity','employee_ethnicity_other_wrap','employee_ethnicity_other');
setupOthers('employee_citizenship','employee_citizenship_other_wrap','employee_citizenship_other');
setupOthers('employee_martialStatus','employee_martial_other_wrap','employee_martial_other');
const syncMarital=function(){const isSingle=marital.value==='Single';const hasMaritalValue=marital.value!=='';childrenWrap.classList.toggle('hidden',isSingle||!hasMaritalValue);yearsWrap.classList.toggle('hidden',isSingle||!hasMaritalValue);if(isSingle||!hasMaritalValue){children.value='';years.value='';}};
const syncCompanyAddress=function(){if(!sameCompanyAddress||!sameCompanyAddress.checked){return;}if(addressInput){addressInput.value=companyAddress||'';}if(postcodeInput){postcodeInput.value=companyPostcode||'';}if(districtInput){districtInput.value=companyDistrict||'';}if(stateInput){stateInput.value=companyState||'';}};
if(marital){marital.addEventListener('change',syncMarital);}
if(sameCompanyAddress){sameCompanyAddress.addEventListener('change',syncCompanyAddress);}
syncMarital();
if(form){form.addEventListener('submit',function(event){if(!form.checkValidity()){event.preventDefault();form.reportValidity();}});}
})();
</script>
</body>
</html>
