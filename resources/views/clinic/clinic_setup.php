<?php
declare(strict_types=1);

require dirname(__DIR__) . '/admin/admin_navigation.php';

$pageMode = isset($pageMode) ? (string) $pageMode : 'create';
$clinicRecord = $clinicRecord ?? null;
$formData = is_array($clinicFormData ?? null) ? $clinicFormData : [];
$pageTitle = $pageMode === 'edit' ? 'Edit Clinic' : ($pageMode === 'view' ? 'Clinic Details' : 'Clinic Setup');
$heading = $pageMode === 'edit' ? 'Edit Clinic' : ($pageMode === 'view' ? 'Clinic Details' : 'Clinic Setup');
$submitLabel = $pageMode === 'edit' ? 'Update Clinic' : 'Save Clinic';
$isReadOnly = $pageMode === 'view';
$csrfToken = function_exists('csrf_token') ? (string) csrf_token() : '';
$statusMessage = function_exists('session') ? (string) session('status', '') : '';

$old = static function (string $key, string $default = '') use ($formData) {
    $fallback = array_key_exists($key, $formData) ? (string) $formData[$key] : $default;
    return function_exists('old') ? (string) old($key, $fallback) : $fallback;
};

$hasErrors = isset($errors) && method_exists($errors, 'any') && $errors->any();
$firstError = $hasErrors ? (string) $errors->first() : '';
$countryCodes = ['60' => '+60', '65' => '+65', '62' => '+62', '66' => '+66'];
$statusOptions = ['active' => 'Active', 'not active' => 'Not Active'];
$formAction = route(match ($pageMode) {
    'edit' => \Illuminate\Support\Facades\Route::has('admin.clinic.update') ? 'admin.clinic.update' : 'panel.clinic.update',
    default => \Illuminate\Support\Facades\Route::has('admin.clinic_setup.store') ? 'admin.clinic_setup.store' : 'panel.clinic_setup.store',
}, $pageMode === 'edit' ? ['clinic' => $clinicRecord->clinic_id] : []);
$headerPreview = trim($old('clinic_header_path', ''));
$backRoute = route(\Illuminate\Support\Facades\Route::has('admin.clinic_list') ? 'admin.clinic_list' : 'panel.clinic_list');
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
        .phone-row{display:grid;grid-template-columns:160px minmax(0,1fr);gap:12px}
        .grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
        .grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
        .preview-card{margin-top:18px;border:1px dashed #cbd5e1;border-radius:18px;padding:16px;background:#f8fafc}
        .preview-card img{display:block;max-width:100%;max-height:180px;border-radius:14px;border:1px solid #dbe2ea;background:#fff}
        .preview-card span{display:block;margin-top:10px;color:#64748b;font-size:.92rem}
        .actions{display:flex;flex-wrap:wrap;gap:12px;justify-content:flex-end;margin-top:18px}
        .btn{display:inline-flex;align-items:center;justify-content:center;border-radius:12px;padding:12px 20px;font-weight:700;cursor:pointer;border:1px solid transparent;transition:transform .15s ease,background-color .15s ease;text-decoration:none}
        .btn.primary{background:#389B5B;color:#fff;border-color:#389B5B}
        .btn.secondary{background:#fff;color:#334155;border-color:#d1d5db}
        .btn:hover{transform:translateY(-1px)}
        @media (max-width:980px){.grid-2,.grid-3{grid-template-columns:1fr}}
        @media (max-width:760px){.phone-row{grid-template-columns:1fr}}
    </style>
</head>
<body class="admin-shell">
<?php medis_render_admin_navigation_start([
    'clinicName' => 'Admin',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'User',
    'active' => 'clinic',
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
    <h2>Clinic Information</h2>
    <form method="POST" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($pageMode === 'edit'): ?>
            <input type="hidden" name="_method" value="PUT">
        <?php endif; ?>
        <input type="hidden" name="clinic_header_path" value="<?php echo htmlspecialchars($headerPreview, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="grid-2">
            <div class="field">
                <label for="clinic_name">Clinic Name</label>
                <input id="clinic_name" name="clinic_name" type="text" value="<?php echo htmlspecialchars($old('clinic_name'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Medis Health Center" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
            <div class="field">
                <label for="registration">Registration Number</label>
                <input id="registration" name="registration" type="text" value="<?php echo htmlspecialchars($old('registration'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="CLN-789012" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
        </div>

        <div class="grid-3" style="margin-top:18px;">
            <div class="field">
                <label for="clinic_email">Clinic Email</label>
                <input id="clinic_email" name="clinic_email" type="email" value="<?php echo htmlspecialchars($old('clinic_email'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="info@medisclinic.com" <?php echo $isReadOnly ? 'readonly' : ''; ?>>
            </div>
            <div class="field">
                <label for="clinic_phone_number">Telephone</label>
                <div class="phone-row">
                    <select id="clinic_phone_code" name="clinic_phone_code" <?php echo $isReadOnly ? 'disabled' : 'required'; ?>>
                        <?php foreach ($countryCodes as $value => $label): ?>
                            <option value="<?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $old('clinic_phone_code', '60') === (string) $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input id="clinic_phone_number" name="clinic_phone_number" type="tel" value="<?php echo htmlspecialchars($old('clinic_phone_number'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Phone number" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
                </div>
            </div>
            <div class="field">
                <label for="clinic_fax_number">Fax Number</label>
                <div class="phone-row">
                    <select id="clinic_fax_code" name="clinic_fax_code" <?php echo $isReadOnly ? 'disabled' : ''; ?>>
                        <?php foreach ($countryCodes as $value => $label): ?>
                            <option value="<?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $old('clinic_fax_code', '60') === (string) $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input id="clinic_fax_number" name="clinic_fax_number" type="tel" value="<?php echo htmlspecialchars($old('clinic_fax_number'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Fax number" <?php echo $isReadOnly ? 'readonly' : ''; ?>>
                </div>
            </div>
        </div>

        <div class="field" style="margin-top:18px;">
            <label for="clinic_address">Clinic Address</label>
            <textarea id="clinic_address" name="clinic_address" placeholder="123 Main St, City, Country" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>><?php echo htmlspecialchars($old('clinic_address'), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="grid-3" style="margin-top:18px;">
            <div class="field">
                <label for="clinic_postcode">Postcode</label>
                <input id="clinic_postcode" name="clinic_postcode" type="text" value="<?php echo htmlspecialchars($old('clinic_postcode'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="43000" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
            <div class="field">
                <label for="clinic_district">District</label>
                <input id="clinic_district" name="clinic_district" type="text" value="<?php echo htmlspecialchars($old('clinic_district'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Kajang" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
            <div class="field">
                <label for="clinic_state">State</label>
                <input id="clinic_state" name="clinic_state" type="text" value="<?php echo htmlspecialchars($old('clinic_state'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Selangor" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
        </div>

        <div class="grid-2" style="margin-top:18px;">
            <div class="field">
                <label for="clinic_status">Status</label>
                <select id="clinic_status" name="clinic_status" <?php echo $isReadOnly ? 'disabled' : 'required'; ?>>
                    <?php foreach ($statusOptions as $value => $label): ?>
                        <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $old('clinic_status', 'active') === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="header_upload">Upload Header Setup</label>
                <input id="header_upload" name="header_upload" type="file" accept="image/*" <?php echo $pageMode === 'create' ? 'required' : ''; ?> <?php echo $isReadOnly ? 'disabled' : ''; ?>>
            </div>
        </div>

        <?php if ($headerPreview !== ''): ?>
            <div class="preview-card">
                <img src="<?php echo htmlspecialchars(asset($headerPreview), ENT_QUOTES, 'UTF-8'); ?>" alt="Clinic header preview">
                <span>Current header image</span>
            </div>
        <?php endif; ?>

        <div class="actions">
            <a class="btn secondary" href="<?php echo htmlspecialchars($backRoute, ENT_QUOTES, 'UTF-8'); ?>">Back to Clinic List</a>
            <?php if (! $isReadOnly): ?>
                <button type="submit" class="btn primary"><?php echo htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8'); ?></button>
            <?php endif; ?>
        </div>
    </form>
</section>

<?php medis_render_navigation_end(); ?>
</body>
</html>
