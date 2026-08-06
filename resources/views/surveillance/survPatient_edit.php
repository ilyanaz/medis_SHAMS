<?php
require_once __DIR__ . '/../auth/view_bootstrap.php';
$esc = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$pageTitle = 'Edit Patient';
$formAction = ($patientRecord && function_exists('route')) ? route('surveillance.patient.update', ['employee' => $patientRecord->employee_id]) : '#';
$formMethod = 'PUT';
$submitLabel = 'Update Patient';
$readOnly = false;
require __DIR__ . '/partials/surv_patient_basic_form.php';
