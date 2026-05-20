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
.overlay{min-height:100vh;background:var(--bg);display:grid;place-items:center;padding:24px}
.modal{width:min(1060px,100%);background:var(--panel);border-radius:20px;box-shadow:0 20px 55px rgba(15,23,42,.25);padding:26px}
h1{margin:0 0 6px;font-size:2.1rem}.muted{margin:0;color:var(--muted)}
.panel,.subpanel{margin-top:18px;border:1px solid #e8ebf2;border-radius:16px;padding:16px}
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
.btn.primary{background:var(--green);border-color:var(--green);color:#fff;font-weight:600}.btn.danger{color:var(--red);border-color:#fecaca;background:#fff}.btn.small{padding:8px 12px;font-size:.85rem}
.req{color:#dc2626}.error{margin-top:14px;padding:12px 14px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;border-radius:12px}
.section-title{margin:0;font-size:1rem;font-weight:700;color:#111827}.repeat-list{display:grid;gap:12px}.repeat-card{border:1px solid #e8ebf2;border-radius:14px;padding:14px;background:#fbfcfd}
.repeat-card-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}.repeat-card-title{font-size:.92rem;font-weight:700;color:#111827}
.repeat-actions{display:flex;justify-content:flex-end;margin-top:12px}.history-stack{display:grid;gap:12px}.history-row{display:grid;grid-template-columns:minmax(300px,1.45fr) minmax(160px,0.9fr) minmax(160px,0.9fr);gap:14px;align-items:start}.history-row.two-inputs{grid-template-columns:minmax(300px,1.45fr) minmax(220px,1fr)}
.history-choice{display:grid;gap:8px;align-self:start;padding-right:8px}.history-choice-label{font-size:.9rem;font-weight:600;color:#111827}.history-radio-group{display:flex;gap:22px;align-items:center;justify-content:flex-start;flex-wrap:wrap;min-height:34px;padding:0;border:none;border-radius:0}.history-radio{display:inline-flex;align-items:center;gap:8px;font-size:.9rem;font-weight:500;color:#111827}.history-radio input{width:20px;height:20px;margin:0;accent-color:#389B5B;cursor:pointer;flex-shrink:0}
.training-table{width:100%;border-collapse:separate;border-spacing:0;overflow:hidden;border:1px solid #e5e7eb;border-radius:14px}.training-table th,.training-table td{padding:12px;vertical-align:top;border-bottom:1px solid #e5e7eb}.training-table tr:last-child th,.training-table tr:last-child td{border-bottom:none}.training-table th{width:28%;background:#f8fafc;text-align:left;font-size:.92rem;color:#111827;font-weight:600}.training-table td{background:#fff}.training-table .comment-field textarea{min-height:78px}.training-radio-group{display:flex;gap:22px;align-items:center;justify-content:center;flex-wrap:wrap;min-height:44px;padding-left:18px}.training-radio{display:inline-flex;align-items:center;gap:8px;font-size:.9rem;font-weight:500;color:#111827}.training-radio input{width:20px;height:20px;margin:0;accent-color:#389B5B;cursor:pointer;flex-shrink:0}
@media (max-width:860px){.history-row,.history-row.two-inputs{grid-template-columns:1fr}.training-table,.training-table tbody,.training-table tr,.training-table th,.training-table td{display:block;width:100%}.training-table th{border-bottom:none;padding-bottom:6px}.training-table td{padding-top:0}}
@media (max-width:860px){.grid{grid-template-columns:1fr}.phone-group{grid-template-columns:1fr}.repeat-card-head{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<?php
$selectedCompany = $selectedCompany ?? null;
$patientRecord = $patientRecord ?? null;
$patientFormData = $patientFormData ?? [];
$readOnly = !empty($readOnly);
$selectedCompanyId = $selectedCompany->company_id ?? ($patientFormData['company_id'] ?? request()->query('company_id') ?? '');
$selectedCompanyName = $selectedCompany->company_name ?? ($patientFormData['current_company_name'] ?? '');
$selectedCompanyAddress = $selectedCompany->company_address ?? '';
$selectedCompanyPostcode = $selectedCompany->company_postcode ?? '';
$selectedCompanyDistrict = $selectedCompany->company_district ?? '';
$selectedCompanyState = $selectedCompany->company_state ?? '';
$hasSelectedCompany = $selectedCompanyId !== '' && $selectedCompanyId !== null;
$countryCodes = config('country_codes', []);
$returnTo = (string) ($returnTo ?? (function_exists('route') ? route('surveillance.patient', array_filter(['company_id' => $selectedCompanyId])) : '#'));
$formAction = (string) ($formAction ?? '#');
$formMethod = strtoupper((string) ($formMethod ?? 'POST'));
$occupJobTitles = old('occup_job_title', $patientFormData['occup_job_title'] ?? []);
$occupCompanyNames = old('occup_company_name', $patientFormData['occup_company_name'] ?? []);
$employmentDurations = old('employment_duration', $patientFormData['employment_duration'] ?? []);
$chemicalExposureDurations = old('chemical_exposure_duration', $patientFormData['chemical_exposure_duration'] ?? []);
$chemicalExposureIncidents = old('chemical_exposure_incidents', $patientFormData['chemical_exposure_incidents'] ?? []);
$pastRows = max(count((array) $occupJobTitles), count((array) $occupCompanyNames), count((array) $employmentDurations), count((array) $chemicalExposureDurations), count((array) $chemicalExposureIncidents));
$pastRows = $readOnly ? $pastRows : max(1, $pastRows);
$value = static fn ($key, $default = '') => old($key, $patientFormData[$key] ?? $default);
$isOtherEthnicity = $value('employee_ethnicity') === 'Others';
$isOtherCitizenship = $value('employee_citizenship') === 'Others';
$isOtherMarital = $value('employee_martialStatus') === 'Others';
$showMaritalExtra = $value('employee_martialStatus') !== '' && $value('employee_martialStatus') !== 'Single';
$editUrl = $patientRecord && function_exists('route') ? route('surveillance.patient.edit', array_filter(['employee' => $patientRecord->employee_id, 'company_id' => $selectedCompanyId, 'return_to' => $returnTo])) : '#';
?>
<div class="overlay">
<form class="modal" method="post" action="<?php echo $esc($formAction); ?>" id="survPatientForm" novalidate>
<?php if ($formMethod !== 'GET'): ?><input type="hidden" name="_token" value="<?php echo $esc(csrf_token()); ?>"><?php endif; ?>
<?php if (!in_array($formMethod, ['GET', 'POST'], true)): ?><input type="hidden" name="_method" value="<?php echo $esc($formMethod); ?>"><?php endif; ?>
<input type="hidden" name="company_id" value="<?php echo $esc($selectedCompanyId); ?>">
<input type="hidden" name="return_to" value="<?php echo $esc($returnTo); ?>">
<h1><?php echo $esc($pageTitle ?? 'Patient'); ?></h1>
<?php if (isset($errors) && $errors->any()): ?><div class="error"><?php echo $esc($errors->first()); ?></div><?php endif; ?>

<div class="panel">
<div class="section-title">Patient Details</div>
<div class="grid">
<label class="field">First Name<?php if (!$readOnly): ?> <span class="req">*</span><?php endif; ?><input name="employee_firstName" type="text" value="<?php echo $esc($value('employee_firstName')); ?>" placeholder="Enter first name" <?php echo $readOnly ? 'readonly' : 'required'; ?>></label>
<label class="field">Last Name<?php if (!$readOnly): ?> <span class="req">*</span><?php endif; ?><input name="employee_lastName" type="text" value="<?php echo $esc($value('employee_lastName')); ?>" placeholder="Enter last name" <?php echo $readOnly ? 'readonly' : 'required'; ?>></label>
<label class="field">NRIC<?php if (!$readOnly): ?> <span class="req">*</span><?php endif; ?><input id="employee_NRIC" name="employee_NRIC" type="text" value="<?php echo $esc($value('employee_NRIC')); ?>" placeholder="Enter NRIC" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Passport No<?php if (!$readOnly): ?> <span class="req">*</span><?php endif; ?><input id="employee_passportNo" name="employee_passportNo" type="text" value="<?php echo $esc($value('employee_passportNo')); ?>" placeholder="Enter passport number" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Date of Birth<input name="employee_DOB" type="date" value="<?php echo $esc($value('employee_DOB')); ?>" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Gender<select name="employee_gender" <?php echo $readOnly ? 'disabled' : ''; ?>><option value="">Select</option><option value="Male"<?php echo $value('employee_gender') === 'Male' ? ' selected' : ''; ?>>Male</option><option value="Female"<?php echo $value('employee_gender') === 'Female' ? ' selected' : ''; ?>>Female</option></select></label>
<label class="field full"><span class="field-label-row"><span>Address</span><?php if ($hasSelectedCompany): ?><span class="checkbox-inline"><input type="checkbox" id="sameCompanyAddress" <?php echo $readOnly ? 'disabled' : ''; ?>> Same as company address</span><?php endif; ?></span><input id="employee_address" name="employee_address" type="text" value="<?php echo $esc($value('employee_address')); ?>" placeholder="Enter address" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Postcode<input id="employee_postcode" name="employee_postcode" type="text" value="<?php echo $esc($value('employee_postcode')); ?>" placeholder="Enter postcode" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">District<input id="employee_district" name="employee_district" type="text" value="<?php echo $esc($value('employee_district')); ?>" placeholder="Enter district" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">State<input id="employee_state" name="employee_state" type="text" value="<?php echo $esc($value('employee_state')); ?>" placeholder="Enter state" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Telephone<?php if (!$readOnly): ?> <span class="req">*</span><?php endif; ?><div class="phone-group"><select name="employee_phone_code" <?php echo $readOnly ? 'disabled' : 'required'; ?>><option value="">Code</option><?php foreach ($countryCodes as $country): $code = (string) ($country['code'] ?? '+60'); ?><option value="<?php echo $esc($code); ?>"<?php echo $value('employee_phone_code', '+60') === $code ? ' selected' : ''; ?>><?php echo $esc($code); ?></option><?php endforeach; ?></select><input name="employee_telephone" type="tel" value="<?php echo $esc($value('employee_telephone')); ?>" inputmode="numeric" placeholder="Phone number" <?php echo $readOnly ? 'readonly' : 'required'; ?>></div></label>
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

<div class="subpanel"><div class="section-title">Medical History</div><div class="grid">
<label class="field full">Diagnosed History<textarea name="diagnosed_history" placeholder="Insert diagnosed history" <?php echo $readOnly ? 'readonly' : ''; ?>><?php echo $esc($value('diagnosed_history')); ?></textarea></label>
<label class="field full">Medication History<textarea name="medication_history" placeholder="Insert medication history" <?php echo $readOnly ? 'readonly' : ''; ?>><?php echo $esc($value('medication_history')); ?></textarea></label>
<label class="field full">Admitted History<textarea name="admitted_history" placeholder="Insert admitted history" <?php echo $readOnly ? 'readonly' : ''; ?>><?php echo $esc($value('admitted_history')); ?></textarea></label>
<label class="field full">Family History<textarea name="family_history" placeholder="Insert family history" <?php echo $readOnly ? 'readonly' : ''; ?>><?php echo $esc($value('family_history')); ?></textarea></label>
<label class="field full">Other History<textarea name="others_history" placeholder="Insert other history" <?php echo $readOnly ? 'readonly' : ''; ?>><?php echo $esc($value('others_history')); ?></textarea></label>
</div></div>

<div class="subpanel"><div class="section-title">Occupational &amp; Company History</div>
<div class="repeat-card"><div class="repeat-card-head"><div class="repeat-card-title">Current Company Record</div></div><div class="grid">
<label class="field">Job Title<input type="text" name="current_job_title" value="<?php echo $esc($value('current_job_title')); ?>" placeholder="Enter current job title" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Company Name<input type="text" name="current_company_name" value="<?php echo $esc($value('current_company_name', $selectedCompanyName)); ?>" placeholder="Enter current company name" <?php echo ($hasSelectedCompany || $readOnly) ? 'readonly' : ''; ?>></label>
<label class="field">Employment Duration<input type="text" name="current_employment_duration" value="<?php echo $esc($value('current_employment_duration')); ?>" placeholder="Enter employment duration" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Chemical Exposure Duration<input type="text" name="current_chemical_exposure_duration" value="<?php echo $esc($value('current_chemical_exposure_duration')); ?>" placeholder="Enter exposure duration" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field full">Chemical Exposure Incidents<textarea name="current_chemical_exposure_incidents" placeholder="Insert chemical exposure incidents" <?php echo $readOnly ? 'readonly' : ''; ?>><?php echo $esc($value('current_chemical_exposure_incidents')); ?></textarea></label>
</div></div>
<div class="repeat-list" id="occupationalHistoryList">
<?php for ($index = 0; $index < $pastRows; $index++): ?>
<div class="repeat-card" data-occup-row><div class="repeat-card-head"><div class="repeat-card-title">Past Company Record <?php echo $index + 1; ?></div><?php if (!$readOnly): ?><button class="btn danger small" type="button" data-remove-occup-row>Delete</button><?php endif; ?></div><div class="grid">
<label class="field">Job Title<input type="text" name="occup_job_title[]" value="<?php echo $esc((string) ($occupJobTitles[$index] ?? '')); ?>" placeholder="Enter job title" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Company Name<input type="text" name="occup_company_name[]" value="<?php echo $esc((string) ($occupCompanyNames[$index] ?? '')); ?>" placeholder="Enter company name" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Employment Duration<input type="text" name="employment_duration[]" value="<?php echo $esc((string) ($employmentDurations[$index] ?? '')); ?>" placeholder="Enter employment duration" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field">Chemical Exposure Duration<input type="text" name="chemical_exposure_duration[]" value="<?php echo $esc((string) ($chemicalExposureDurations[$index] ?? '')); ?>" placeholder="Enter exposure duration" <?php echo $readOnly ? 'readonly' : ''; ?>></label>
<label class="field full">Chemical Exposure Incidents<textarea name="chemical_exposure_incidents[]" placeholder="Insert chemical exposure incidents" <?php echo $readOnly ? 'readonly' : ''; ?>><?php echo $esc((string) ($chemicalExposureIncidents[$index] ?? '')); ?></textarea></label>
</div></div>
<?php endfor; ?>
</div>
<?php if (!$readOnly): ?><div class="repeat-actions"><button class="btn small" type="button" id="addOccupationalRow">+ Add Past Company</button></div><?php endif; ?>
</div>

<div class="subpanel"><div class="section-title">Personal &amp; Social History</div><div class="history-stack">
<div class="history-row"><div class="history-choice"><div class="history-choice-label">Smoking History</div><div class="history-radio-group">
<?php foreach(['Current','Ex-smoker','Non-smoker'] as $option): ?><label class="history-radio"><input type="radio" name="smoking_history" value="<?php echo $esc($option); ?>"<?php echo $value('smoking_history') === $option ? ' checked' : ''; ?> <?php echo $readOnly ? 'disabled' : ''; ?>><?php echo $esc($option); ?></label><?php endforeach; ?>
</div></div><label class="field">Years of Smoking<input type="number" min="0" name="years_of_smoking" value="<?php echo $esc($value('years_of_smoking')); ?>" placeholder="Enter years" <?php echo $readOnly ? 'readonly' : ''; ?>></label><label class="field">No. of Cigarettes<input type="number" min="0" name="no_of_cigarettes" value="<?php echo $esc($value('no_of_cigarettes')); ?>" placeholder="Enter count" <?php echo $readOnly ? 'readonly' : ''; ?>></label></div>
<div class="history-row two-inputs"><div class="history-choice"><div class="history-choice-label">Vaping History</div><div class="history-radio-group">
<?php foreach(['Yes','No'] as $option): ?><label class="history-radio"><input type="radio" name="vaping_history" value="<?php echo $esc($option); ?>"<?php echo $value('vaping_history') === $option ? ' checked' : ''; ?> <?php echo $readOnly ? 'disabled' : ''; ?>><?php echo $esc($option); ?></label><?php endforeach; ?>
</div></div><label class="field">Years of Vaping<input type="number" min="0" name="years_of_vaping" value="<?php echo $esc($value('years_of_vaping')); ?>" placeholder="Enter years" <?php echo $readOnly ? 'readonly' : ''; ?>></label></div>
<label class="field full">Hobby<textarea name="hobby" placeholder="Insert hobby details" <?php echo $readOnly ? 'readonly' : ''; ?>><?php echo $esc($value('hobby')); ?></textarea></label>
</div></div>

<div class="subpanel"><div class="section-title">Training History</div><table class="training-table"><tbody>
<?php foreach ([
['Handling of Chemical','handling_of_chemical','chemical_comments','Insert handling comments'],
['Sign & Symptoms Knowledge','sign_symptoms','sign_comments','Insert sign and symptoms comments'],
['Chemical Poisoning Knowledge','chemical_poisoning','poisoning_comments','Insert poisoning comments'],
['Proper PPE Knowledge','proper_PPE','proper_comments','Insert proper PPE comments'],
['PPE Usage','PPE_usage','usage_comments','Insert PPE usage comments'],
] as $trainingRow): ?>
<tr><th><?php echo $esc($trainingRow[0]); ?></th><td><div class="training-radio-group"><?php foreach(['Yes','No'] as $option): ?><label class="training-radio"><input type="radio" name="<?php echo $esc($trainingRow[1]); ?>" value="<?php echo $esc($option); ?>"<?php echo $value($trainingRow[1]) === $option ? ' checked' : ''; ?> <?php echo $readOnly ? 'disabled' : ''; ?>><?php echo $esc($option); ?></label><?php endforeach; ?></div></td><td class="comment-field"><textarea name="<?php echo $esc($trainingRow[2]); ?>" placeholder="<?php echo $esc($trainingRow[3]); ?>" <?php echo $readOnly ? 'readonly' : ''; ?>><?php echo $esc($value($trainingRow[2])); ?></textarea></td></tr>
<?php endforeach; ?>
</tbody></table></div>

<div class="actions">
<a class="btn" href="<?php echo $esc($returnTo); ?>">Back</a>
<div style="display:flex;gap:8px;flex-wrap:wrap;">
<?php if ($readOnly): ?><a class="btn primary" href="<?php echo $esc($editUrl); ?>">Edit Patient</a><?php else: ?><button class="btn primary" type="submit"><?php echo $esc($submitLabel ?? 'Save'); ?></button><?php endif; ?>
</div>
</div>
</form>
</div>
<?php if (!$readOnly): ?>
<template id="occupationalRowTemplate"><div class="repeat-card" data-occup-row><div class="repeat-card-head"><div class="repeat-card-title">Past Company Record</div><button class="btn danger small" type="button" data-remove-occup-row>Delete</button></div><div class="grid"><label class="field">Job Title<input type="text" name="occup_job_title[]" placeholder="Enter job title"></label><label class="field">Company Name<input type="text" name="occup_company_name[]" placeholder="Enter company name"></label><label class="field">Employment Duration<input type="text" name="employment_duration[]" placeholder="Enter employment duration"></label><label class="field">Chemical Exposure Duration<input type="text" name="chemical_exposure_duration[]" placeholder="Enter exposure duration"></label><label class="field full">Chemical Exposure Incidents<textarea name="chemical_exposure_incidents[]" placeholder="Insert chemical exposure incidents"></textarea></label></div></div></template>
<script>
(function(){
const form=document.getElementById('survPatientForm');
const nric=document.getElementById('employee_NRIC');
const passport=document.getElementById('employee_passportNo');
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
const occupationalList=document.getElementById('occupationalHistoryList');
const occupationalTemplate=document.getElementById('occupationalRowTemplate');
const addOccupationalRow=document.getElementById('addOccupationalRow');
const companyAddress=<?php echo json_encode((string) $selectedCompanyAddress, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const companyPostcode=<?php echo json_encode((string) $selectedCompanyPostcode, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const companyDistrict=<?php echo json_encode((string) $selectedCompanyDistrict, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const companyState=<?php echo json_encode((string) $selectedCompanyState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const setupOthers=function(selectId,wrapId,inputId){const select=document.getElementById(selectId);const wrap=document.getElementById(wrapId);const input=document.getElementById(inputId);if(!select||!wrap||!input){return;}const sync=function(){const isOther=select.value==='Others';wrap.classList.toggle('hidden',!isOther);if(!isOther){input.value='';}};select.addEventListener('change',sync);sync();};
const syncOccupationalTitles=function(){occupationalList.querySelectorAll('[data-occup-row]').forEach(function(row,index){const title=row.querySelector('.repeat-card-title');if(title){title.textContent='Past Company Record '+(index+1);}const remove=row.querySelector('[data-remove-occup-row]');if(remove){remove.disabled=occupationalList.querySelectorAll('[data-occup-row]').length===1;remove.style.opacity=remove.disabled?'0.5':'1';}});};
const bindOccupationalRow=function(row){const remove=row.querySelector('[data-remove-occup-row]');if(remove){remove.addEventListener('click',function(){if(occupationalList.querySelectorAll('[data-occup-row]').length>1){row.remove();syncOccupationalTitles();}});}};
if(addOccupationalRow){addOccupationalRow.addEventListener('click',function(){const fragment=occupationalTemplate.content.cloneNode(true);occupationalList.appendChild(fragment);bindOccupationalRow(occupationalList.lastElementChild);syncOccupationalTitles();});}
occupationalList.querySelectorAll('[data-occup-row]').forEach(bindOccupationalRow);syncOccupationalTitles();
setupOthers('employee_ethnicity','employee_ethnicity_other_wrap','employee_ethnicity_other');
setupOthers('employee_citizenship','employee_citizenship_other_wrap','employee_citizenship_other');
setupOthers('employee_martialStatus','employee_martial_other_wrap','employee_martial_other');
const syncIdRequirement=function(){const hasNric=nric.value.trim()!=='';const hasPassport=passport.value.trim()!=='';nric.required=!hasPassport;passport.required=!hasNric;nric.setCustomValidity('');passport.setCustomValidity('');if(!hasNric&&!hasPassport){nric.setCustomValidity('NRIC or Passport No is required.');passport.setCustomValidity('NRIC or Passport No is required.');}};
const syncMarital=function(){const isSingle=marital.value==='Single';const hasMaritalValue=marital.value!=='';childrenWrap.classList.toggle('hidden',isSingle||!hasMaritalValue);yearsWrap.classList.toggle('hidden',isSingle||!hasMaritalValue);if(isSingle||!hasMaritalValue){children.value='';years.value='';}};
const syncCompanyAddress=function(){if(!sameCompanyAddress||!sameCompanyAddress.checked){return;}if(addressInput){addressInput.value=companyAddress||'';}if(postcodeInput){postcodeInput.value=companyPostcode||'';}if(districtInput){districtInput.value=companyDistrict||'';}if(stateInput){stateInput.value=companyState||'';}};
nric.addEventListener('input',syncIdRequirement);passport.addEventListener('input',syncIdRequirement);marital.addEventListener('change',syncMarital);if(sameCompanyAddress){sameCompanyAddress.addEventListener('change',syncCompanyAddress);}
syncIdRequirement();syncMarital();
form.addEventListener('submit',function(event){syncIdRequirement();syncMarital();if(!form.checkValidity()){event.preventDefault();form.reportValidity();}});
})();
</script>
<?php endif; ?>
</body>
</html>
