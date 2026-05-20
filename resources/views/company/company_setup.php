<?php
declare(strict_types=1);

require dirname(__DIR__) . '/panel/navigation.php';

$pageMode = isset($pageMode) ? (string) $pageMode : 'create';
$companyRecord = $companyRecord ?? null;
$formData = is_array($companyFormData ?? null) ? $companyFormData : [];
$pageTitle = $pageMode === 'edit' ? 'Edit Company' : ($pageMode === 'view' ? 'Company Details' : 'Company Setup');
$heading = $pageMode === 'edit' ? 'Edit Company' : ($pageMode === 'view' ? 'Company Details' : 'Company Setup');
$submitLabel = $pageMode === 'edit' ? 'Update Company' : 'Save Company';
$isReadOnly = $pageMode === 'view';
$csrfToken = function_exists('csrf_token') ? (string) csrf_token() : '';
$statusMessage = function_exists('session') ? (string) session('status', '') : '';

$old = static function (string $key, string $default = '') use ($formData) {
    $fallback = array_key_exists($key, $formData) ? (string) $formData[$key] : $default;
    return function_exists('old') ? (string) old($key, $fallback) : $fallback;
};

$hasErrors = isset($errors) && method_exists($errors, 'any') && $errors->any();
$firstError = $hasErrors ? (string) $errors->first() : '';
$countryCodes = config('country_codes', []);
$normalizeCountryCode = static function (?string $value, string $default = '+60'): string {
    $digits = preg_replace('/\D/', '', (string) $value) ?? '';
    if ($digits === '') {
        return $default;
    }

    return '+' . $digits;
};

$phoneRaw = trim($old('company_telephone', (string) ($companyRecord->company_telephone ?? '')));
$phoneCode = $normalizeCountryCode($old('company_phone_code', '+60'));
$phoneNumber = $phoneRaw;

if ($phoneRaw !== '' && preg_match('/^(\+\d{1,3})\s*(.*)$/', $phoneRaw, $matches) === 1) {
    $phoneCode = $normalizeCountryCode($old('company_phone_code', $matches[1]));
    $phoneNumber = $old('company_telephone', $matches[2]);
}

$routeFacade = \Illuminate\Support\Facades\Route::class;
$formAction = route(match ($pageMode) {
    'edit' => $routeFacade::has('panel.company.update') ? 'panel.company.update' : 'surveillance.company.update',
    default => $routeFacade::has('panel.company_setup.store') ? 'panel.company_setup.store' : 'surveillance.company.store',
});
$backRoute = route($routeFacade::has('panel.company_list') ? 'panel.company_list' : 'surveillance.company');
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        .page-head h1{margin:0;font-size:1.9rem}
        .notice-box,.error-box{margin-top:18px;padding:12px 14px;border-radius:14px}
        .notice-box{border:1px solid #a7f3d0;background:#ecfdf3;color:#065f46}
        .error-box{border:1px solid #fecaca;background:#fef2f2;color:#991b1b}
        .card{border:1px solid #e5e7eb;border-radius:22px;background:#fff;padding:24px}
        .form-card h2{margin:0 0 18px;font-size:1.25rem;font-weight:700}
        .field{display:grid;gap:8px}
        .field label{font-weight:600;color:#334155}
        .field input,.field textarea,.field select{border:1px solid #cbd5e1;border-radius:12px;padding:12px 14px;background:#fff;color:#0f172a;font-size:.98rem;outline:none}
        .field textarea{min-height:120px;resize:vertical}
        .field input[readonly],.field textarea[readonly],.field select:disabled{background:#f8fafc;color:#475569}
        .grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
        .grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
        .phone-row{display:grid;grid-template-columns:92px minmax(0,1fr);gap:12px}
        .summary-strip{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:18px}
        .summary-item{border:1px solid #e2e8f0;border-radius:18px;padding:16px 18px;background:#f8fafc}
        .summary-item span{display:block;color:#64748b;font-size:.88rem}
        .summary-item strong{display:block;margin-top:6px;color:#0f172a;font-size:1.1rem}
        .actions{display:flex;flex-wrap:wrap;gap:12px;justify-content:flex-end;margin-top:18px}
        .btn{display:inline-flex;align-items:center;justify-content:center;border-radius:12px;padding:12px 20px;font-weight:700;cursor:pointer;border:1px solid transparent;transition:transform .15s ease,background-color .15s ease;text-decoration:none}
        .btn.primary{background:#389B5B;color:#fff;border-color:#389B5B}
        .btn.secondary{background:#fff;color:#334155;border-color:#d1d5db}
        .btn:hover{transform:translateY(-1px)}
        @media (max-width:980px){.grid-2,.grid-3,.summary-strip{grid-template-columns:1fr}}
        @media (max-width:760px){.phone-row{grid-template-columns:1fr}}
    </style>
</head>
<body>
<?php medis_render_navigation_start([
    'clinicName' => $clinicName ?? 'Medis SHAMS',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'User',
    'active' => 'company',
]); ?>

<div class="page-head">
    <h1><?php echo htmlspecialchars($heading, ENT_QUOTES, 'UTF-8'); ?></h1>
</div>

<?php if ($statusMessage !== ''): ?>
    <div class="notice-box"><?php echo htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php if ($hasErrors): ?>
    <div class="error-box"><?php echo htmlspecialchars($firstError, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<section class="card form-card" style="margin-top:18px;">
    <h2>Company Information</h2>

    <div class="summary-strip">
        <div class="summary-item">
            <span>Registration</span>
            <strong><?php echo htmlspecialchars($old('mykpp_registration_no', (string) ($companyRecord->mykpp_registration_no ?? 'Pending')), ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
        <div class="summary-item">
            <span>Total Workers</span>
            <strong><?php echo htmlspecialchars($old('total_workers', (string) ($companyRecord->total_workers ?? '0')), ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
        <div class="summary-item">
            <span>Contact Email</span>
            <strong><?php echo htmlspecialchars($old('company_email', (string) ($companyRecord->company_email ?? 'Not set')), ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
    </div>

    <form method="POST" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($pageMode !== 'create'): ?>
            <input type="hidden" name="company_id" value="<?php echo htmlspecialchars((string) ($companyRecord->company_id ?? $old('company_id')), ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <div class="grid-2">
            <div class="field">
                <label for="company_name">Company Name</label>
                <input id="company_name" name="company_name" type="text" value="<?php echo htmlspecialchars($old('company_name', (string) ($companyRecord->company_name ?? '')), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Medis Manufacturing Sdn Bhd" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
            <div class="field">
                <label for="mykpp_registration_no">MYKPP Registration Number</label>
                <input id="mykpp_registration_no" name="mykpp_registration_no" type="text" value="<?php echo htmlspecialchars($old('mykpp_registration_no', (string) ($companyRecord->mykpp_registration_no ?? '')), ENT_QUOTES, 'UTF-8'); ?>" placeholder="MYKPP-2026-001" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
        </div>

        <div class="field" style="margin-top:18px;">
            <label for="company_address">Company Address</label>
            <textarea id="company_address" name="company_address" placeholder="No. 12, Jalan Industri 3, Bandar Baru Bangi" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>><?php echo htmlspecialchars($old('company_address', (string) ($companyRecord->company_address ?? '')), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="grid-3" style="margin-top:18px;">
            <div class="field">
                <label for="company_postcode">Postcode</label>
                <input id="company_postcode" name="company_postcode" type="text" value="<?php echo htmlspecialchars($old('company_postcode', (string) ($companyRecord->company_postcode ?? '')), ENT_QUOTES, 'UTF-8'); ?>" placeholder="43000" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
            <div class="field">
                <label for="company_district">District</label>
                <input id="company_district" name="company_district" type="text" value="<?php echo htmlspecialchars($old('company_district', (string) ($companyRecord->company_district ?? '')), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Kajang" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
            <div class="field">
                <label for="company_state">State</label>
                <input id="company_state" name="company_state" type="text" value="<?php echo htmlspecialchars($old('company_state', (string) ($companyRecord->company_state ?? '')), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Selangor" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
        </div>

        <div class="grid-3" style="margin-top:18px;">
            <div class="field">
                <label for="company_phone_code">Telephone</label>
                <div class="phone-row">
                    <select id="company_phone_code" name="company_phone_code" <?php echo $isReadOnly ? 'disabled' : 'required'; ?>>
                        <?php foreach ($countryCodes as $country): ?>
                            <?php $code = (string) ($country['code'] ?? '+60'); ?>
                            <option value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $phoneCode === $code ? 'selected' : ''; ?>><?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input id="company_telephone" name="company_telephone" type="tel" value="<?php echo htmlspecialchars($phoneNumber, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Phone number" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
                </div>
            </div>
            <div class="field">
                <label for="company_email">Company Email</label>
                <input id="company_email" name="company_email" type="email" value="<?php echo htmlspecialchars($old('company_email', (string) ($companyRecord->company_email ?? '')), ENT_QUOTES, 'UTF-8'); ?>" placeholder="hr@company.com" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
            <div class="field">
                <label for="company_fax">Company Fax</label>
                <input id="company_fax" name="company_fax" type="text" value="<?php echo htmlspecialchars($old('company_fax', (string) ($companyRecord->company_fax ?? '')), ENT_QUOTES, 'UTF-8'); ?>" placeholder="+60 3-1234 5678" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
        </div>

        <div class="grid-2" style="margin-top:18px;">
            <div class="field">
                <label for="total_workers">Total Workers</label>
                <input id="total_workers" name="total_workers" type="number" min="0" value="<?php echo htmlspecialchars($old('total_workers', (string) ($companyRecord->total_workers ?? '0')), ENT_QUOTES, 'UTF-8'); ?>" placeholder="0" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
            <div class="field">
                <label for="company_reference">Reference</label>
                <input id="company_reference" type="text" value="<?php echo htmlspecialchars($pageMode === 'create' ? 'New company record' : '#COM' . (string) ($companyRecord->company_id ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </div>
        </div>

        <div class="actions">
            <a class="btn secondary" href="<?php echo htmlspecialchars($backRoute, ENT_QUOTES, 'UTF-8'); ?>">Back to Company List</a>
            <?php if (! $isReadOnly): ?>
                <button type="submit" class="btn primary"><?php echo htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8'); ?></button>
            <?php endif; ?>
        </div>
    </form>
</section>

<?php medis_render_navigation_end(); ?>
</body>
</html>
