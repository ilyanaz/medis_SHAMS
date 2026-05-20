<?php
require_once __DIR__ . '/../auth/view_bootstrap.php';
$esc = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$pageTitle = 'View Patient';
$formAction = '#';
$formMethod = 'GET';
$readOnly = true;
require __DIR__ . '/partials/surv_patient_form.php';
