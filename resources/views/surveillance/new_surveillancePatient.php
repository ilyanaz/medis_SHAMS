<?php
require_once __DIR__ . '/../auth/view_bootstrap.php';
$esc = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$pageTitle = 'New Patient';
$formAction = function_exists('route') ? route('surveillance.employee.store') : '#';
$formMethod = 'POST';
$submitLabel = 'Save';
$readOnly = false;
$patientRecord = null;
$patientFormData = array_merge([
    'company_id' => $selectedCompany->company_id ?? request()->query('company_id') ?? '',
], old());
$returnTo = function_exists('route') ? route('surveillance.patient', array_filter(['company_id' => $selectedCompany->company_id ?? request()->query('company_id') ?? null])) : '#';
require __DIR__ . '/partials/surv_patient_basic_form.php';
