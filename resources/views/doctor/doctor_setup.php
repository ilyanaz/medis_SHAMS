<?php
declare(strict_types=1);

require dirname(__DIR__) . '/admin/admin_navigation.php';

$pageMode = isset($pageMode) ? (string) $pageMode : 'create';
$doctorRecord = $doctorRecord ?? null;
$formData = is_array($doctorFormData ?? null) ? $doctorFormData : [];
$pageTitle = $pageMode === 'edit' ? 'Edit Doctor' : ($pageMode === 'view' ? 'Doctor Details' : 'Doctor Setup');
$heading = $pageMode === 'edit' ? 'Edit Doctor' : ($pageMode === 'view' ? 'Doctor Details' : 'Doctor Setup');
$submitLabel = $pageMode === 'edit' ? 'Update Doctor' : 'Save Doctor';
$isReadOnly = $pageMode === 'view';
$csrfToken = function_exists('csrf_token') ? (string) csrf_token() : '';

$old = static function (string $key, string $default = '') use ($formData) {
    $fallback = array_key_exists($key, $formData) ? (string) $formData[$key] : $default;
    return function_exists('old') ? (string) old($key, $fallback) : $fallback;
};

$hasErrors = isset($errors) && method_exists($errors, 'any') && $errors->any();
$firstError = $hasErrors ? (string) $errors->first() : '';
$statusMessage = function_exists('session') ? (string) session('status', '') : '';
$canSaveDoctor = isset($canSaveDoctor) ? (bool) $canSaveDoctor : true;
$countryCodes = ['60' => '+60', '65' => '+65', '62' => '+62', '66' => '+66'];
$genderOptions = ['Male', 'Female'];
$ethnicityOptions = ['Malay', 'Chinese', 'Indian', 'Orang Asli', 'Others'];
$citizenshipOptions = ['Malaysian Citizen', 'Others'];
$maritalStatusOptions = ['Single', 'Married'];
$statusOptions = ['active' => 'Active', 'not active' => 'Not Active'];
$fullName = trim($old('doctor_firstName') . ' ' . $old('doctor_lastName'));
$profileInitial = strtoupper(substr($fullName, 0, 1));
$profileInitial = $profileInitial !== '' ? $profileInitial : 'D';
$profilePicture = trim($old('doctor_picture', ''));
$signaturePath = trim($old('doctor_sign', ''));
$formAction = route(match ($pageMode) {
    'edit' => \Illuminate\Support\Facades\Route::has('admin.doctor.update') ? 'admin.doctor.update' : 'panel.doctor.update',
    default => \Illuminate\Support\Facades\Route::has('admin.doctor_setup.store') ? 'admin.doctor_setup.store' : 'panel.doctor_setup.store',
}, $pageMode === 'edit' ? ['doctor' => $doctorRecord->doctor_id] : []);
$backRoute = route(\Illuminate\Support\Facades\Route::has('admin.doctor_list') ? 'admin.doctor_list' : 'panel.doctor_list');
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
        .field input[readonly],.field textarea[readonly],.field select:disabled{background:#f8fafc;color:#475569}
        .field textarea{min-height:120px;resize:vertical}
        .signature-box{border:1px solid #cbd5e1;border-radius:16px;padding:14px;background:#fff}
        .signature-pad-wrap{border:1px solid #d1d5db;border-radius:12px;overflow:hidden;background:#fff}
        .signature-pad{display:block;width:100%;height:220px;background:#fff}
        .signature-tools{display:flex;justify-content:flex-end;margin-top:10px}
        .signature-clear{border:1px solid #d1d5db;background:#fff;color:#334155;border-radius:10px;padding:10px 14px;font:inherit;cursor:pointer}
        .top-grid{display:grid;grid-template-columns:300px minmax(0,1fr);gap:18px;align-items:start}
        .details-stack{display:grid;gap:14px}
        .media-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(320px,.9fr);gap:18px;margin-top:18px;align-items:start}
        .profile-card{border:1px solid #dbe2ea;border-radius:22px;background:#fff;padding:20px}
        .profile-card h3{margin:0 0 18px;font-size:1.15rem;color:#0f172a}
        .profile-preview{display:grid;justify-items:center;gap:8px;margin-bottom:18px}
        .profile-preview-circle{position:relative;width:132px;height:132px;border-radius:999px;display:grid;place-items:center;cursor:pointer;padding:0;box-shadow:0 10px 26px rgba(15,23,42,.08)}
        .profile-preview-inner{width:100%;height:100%;border-radius:999px;background:linear-gradient(180deg,#dff3e6 0%,#caebd5 100%);color:#217346;display:flex;align-items:center;justify-content:center;font-size:3rem;font-weight:700;overflow:hidden}
        .profile-preview-inner img{width:100%;height:100%;object-fit:cover}
        .profile-camera-btn{position:absolute;right:-2px;bottom:6px;width:42px;height:42px;border-radius:999px;border:4px solid #fff;background:#fff;color:#5f6368;display:flex;align-items:center;justify-content:center;box-shadow:0 10px 22px rgba(15,23,42,.18);pointer-events:none}
        .profile-camera-btn svg{width:22px;height:22px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
        .profile-preview strong{font-size:1.05rem;color:#0f172a}
        .profile-preview span{font-size:.95rem;color:#64748b}
        .profile-card input[type=file]{display:none}
        .phone-row{display:grid;grid-template-columns:160px minmax(0,1fr);gap:12px}
        .grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
        .grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
        .signature-preview{margin-top:14px;border:1px dashed #cbd5e1;border-radius:16px;padding:12px;background:#f8fafc}
        .signature-preview img{display:block;max-width:100%;max-height:170px;border-radius:12px;border:1px solid #dbe2ea;background:#fff}
        .signature-preview span{display:block;margin-top:10px;color:#64748b;font-size:.9rem}
        .actions{display:flex;flex-wrap:wrap;gap:12px;justify-content:flex-end;margin-top:18px}
        .btn{display:inline-flex;align-items:center;justify-content:center;border-radius:12px;padding:12px 20px;font-weight:700;cursor:pointer;border:1px solid transparent;transition:transform .15s ease,background-color .15s ease;text-decoration:none}
        .btn.primary{background:#389B5B;color:#fff;border-color:#389B5B}
        .btn.secondary{background:#fff;color:#334155;border-color:#d1d5db}
        .btn:hover{transform:translateY(-1px)}
        @media (max-width:980px){.grid-2,.grid-3,.media-grid,.top-grid{grid-template-columns:1fr}}
        @media (max-width:760px){.phone-row{grid-template-columns:1fr}}
    </style>
</head>
<body class="admin-shell">
<?php medis_render_admin_navigation_start([
    'clinicName' => 'Admin',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'Admin',
    'active' => 'doctor',
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
    <h2>Doctor Information</h2>
    <form method="POST" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($pageMode === 'edit'): ?>
            <input type="hidden" name="_method" value="PUT">
        <?php endif; ?>
        <input type="hidden" id="doctor_sign_data" name="doctor_sign_data" value="">
        <input type="hidden" name="doctor_sign" value="<?php echo htmlspecialchars($signaturePath, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="top-grid">
            <section class="profile-card">
                <h3>Profile Picture</h3>
                <div class="profile-preview">
                    <label class="profile-preview-circle" id="doctorPicturePreview" for="doctor_picture_upload" <?php echo $isReadOnly ? 'style="cursor:default"' : ''; ?>>
                        <div class="profile-preview-inner">
                            <span id="doctorPictureInitial" <?php echo $profilePicture !== '' ? 'style="display:none;"' : ''; ?>><?php echo htmlspecialchars($profileInitial, ENT_QUOTES, 'UTF-8'); ?></span>
                            <img id="doctorPictureImage" alt="Doctor profile preview" src="<?php echo $profilePicture !== '' ? htmlspecialchars(asset($profilePicture), ENT_QUOTES, 'UTF-8') : ''; ?>" style="<?php echo $profilePicture !== '' ? 'display:block;' : 'display:none;'; ?>">
                        </div>
                        <?php if (! $isReadOnly): ?>
                            <span class="profile-camera-btn" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M4 8h4l1.5-2h5L16 8h4v10H4z"></path><circle cx="12" cy="13" r="3.5"></circle></svg>
                            </span>
                        <?php endif; ?>
                    </label>
                    <strong id="doctorPictureName"><?php echo htmlspecialchars($fullName !== '' ? $fullName : 'Doctor Name', ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span>admin</span>
                </div>
                <?php if (! $isReadOnly): ?>
                    <input id="doctor_picture_upload" name="doctor_picture" type="file" accept="image/*">
                <?php endif; ?>
            </section>

            <div class="details-stack">
                <div class="field">
                    <label for="doctor_firstName">First Name</label>
                    <input id="doctor_firstName" name="doctor_firstName" type="text" value="<?php echo htmlspecialchars($old('doctor_firstName'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ali" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
                </div>
                <div class="field">
                    <label for="doctor_lastName">Last Name</label>
                    <input id="doctor_lastName" name="doctor_lastName" type="text" value="<?php echo htmlspecialchars($old('doctor_lastName'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Bin Abu" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
                </div>
                <div class="field">
                    <label for="doctor_email">Email</label>
                    <input id="doctor_email" name="doctor_email" type="email" value="<?php echo htmlspecialchars($old('doctor_email'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="aliabu@example.com" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
                </div>
            </div>
        </div>

        <div class="grid-3" style="margin-top:18px;">
            <div class="field">
                <label for="doctor_NRIC">NRIC</label>
                <input id="doctor_NRIC" name="doctor_NRIC" type="text" value="<?php echo htmlspecialchars($old('doctor_NRIC'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="900101-10-1234" <?php echo $isReadOnly ? 'readonly' : ''; ?>>
            </div>
            <div class="field">
                <label for="doctor_passportNo">Passport Number</label>
                <input id="doctor_passportNo" name="doctor_passportNo" type="text" value="<?php echo htmlspecialchars($old('doctor_passportNo'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="A12345678" <?php echo $isReadOnly ? 'readonly' : ''; ?>>
            </div>
            <div class="field">
                <label for="doctor_DOB">Date of Birth</label>
                <input id="doctor_DOB" name="doctor_DOB" type="date" value="<?php echo htmlspecialchars($old('doctor_DOB'), ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
        </div>

        <div class="grid-3" style="margin-top:18px;">
            <div class="field">
                <label for="doctor_gender">Gender</label>
                <select id="doctor_gender" name="doctor_gender" <?php echo $isReadOnly ? 'disabled' : 'required'; ?>>
                    <option value="">Select gender</option>
                    <?php foreach ($genderOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $old('doctor_gender') === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="doctor_ethnicity">Ethnicity</label>
                <select id="doctor_ethnicity" name="doctor_ethnicity" <?php echo $isReadOnly ? 'disabled' : 'required'; ?>>
                    <option value="">Select ethnicity</option>
                    <?php foreach ($ethnicityOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $old('doctor_ethnicity') === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="doctor_citizenship">Citizenship</label>
                <select id="doctor_citizenship" name="doctor_citizenship" <?php echo $isReadOnly ? 'disabled' : 'required'; ?>>
                    <option value="">Select citizenship</option>
                    <?php foreach ($citizenshipOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $old('doctor_citizenship') === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid-3" style="margin-top:18px;">
            <div class="field">
                <label for="doctor_martialStatus">Marital Status</label>
                <select id="doctor_martialStatus" name="doctor_martialStatus" <?php echo $isReadOnly ? 'disabled' : 'required'; ?>>
                    <option value="">Select marital status</option>
                    <?php foreach ($maritalStatusOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $old('doctor_martialStatus') === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="MMC_no">MMC Number</label>
                <input id="MMC_no" name="MMC_no" type="text" value="<?php echo htmlspecialchars($old('MMC_no'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="MMC-12345" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
            <div class="field">
                <label for="OHD_registrationNo">OHD Registration Number</label>
                <input id="OHD_registrationNo" name="OHD_registrationNo" type="text" value="<?php echo htmlspecialchars($old('OHD_registrationNo'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="OHD-67890" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
        </div>

        <div class="grid-3" style="margin-top:18px;">
            <div class="field">
                <label for="doctor_phone_number">Telephone</label>
                <div class="phone-row">
                    <select id="doctor_phone_code" name="doctor_phone_code" <?php echo $isReadOnly ? 'disabled' : 'required'; ?>>
                        <?php foreach ($countryCodes as $value => $label): ?>
                            <option value="<?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $old('doctor_phone_code', '60') === (string) $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input id="doctor_phone_number" name="doctor_phone_number" type="tel" value="<?php echo htmlspecialchars($old('doctor_phone_number'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Phone number" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
                </div>
            </div>
            <div class="field">
                <label for="doctor_fax_number">Fax Number</label>
                <div class="phone-row">
                    <select id="doctor_fax_code" name="doctor_fax_code" <?php echo $isReadOnly ? 'disabled' : ''; ?>>
                        <?php foreach ($countryCodes as $value => $label): ?>
                            <option value="<?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $old('doctor_fax_code', '60') === (string) $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input id="doctor_fax_number" name="doctor_fax_number" type="tel" value="<?php echo htmlspecialchars($old('doctor_fax_number'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Fax number" <?php echo $isReadOnly ? 'readonly' : ''; ?>>
                </div>
            </div>
            <div class="field">
                <label for="doctor_status">Status</label>
                <select id="doctor_status" name="doctor_status" <?php echo $isReadOnly ? 'disabled' : 'required'; ?>>
                    <?php foreach ($statusOptions as $value => $label): ?>
                        <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $old('doctor_status', 'active') === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="field" style="margin-top:18px;">
            <label for="doctor_address">Address</label>
            <textarea id="doctor_address" name="doctor_address" placeholder="123 Main St, City, Country" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>><?php echo htmlspecialchars($old('doctor_address'), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="grid-3" style="margin-top:18px;">
            <div class="field">
                <label for="doctor_postcode">Postcode</label>
                <input id="doctor_postcode" name="doctor_postcode" type="text" value="<?php echo htmlspecialchars($old('doctor_postcode'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="43000" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
            <div class="field">
                <label for="doctor_district">District</label>
                <input id="doctor_district" name="doctor_district" type="text" value="<?php echo htmlspecialchars($old('doctor_district'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Kajang" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
            <div class="field">
                <label for="doctor_state">State</label>
                <input id="doctor_state" name="doctor_state" type="text" value="<?php echo htmlspecialchars($old('doctor_state'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Selangor" <?php echo $isReadOnly ? 'readonly' : 'required'; ?>>
            </div>
        </div>

        <div class="media-grid">
            <div class="field">
                <label for="signature-pad">eSign Signature</label>
                <?php if (! $isReadOnly): ?>
                    <div class="signature-box">
                        <div class="signature-pad-wrap">
                            <canvas id="signature-pad" class="signature-pad"></canvas>
                        </div>
                        <div class="signature-tools">
                            <button id="clear-signature" class="signature-clear" type="button">Clear</button>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($signaturePath !== ''): ?>
                    <div class="signature-preview">
                        <img src="<?php echo htmlspecialchars(asset($signaturePath), ENT_QUOTES, 'UTF-8'); ?>" alt="Doctor signature preview">
                        <span>Current saved signature</span>
                    </div>
                <?php endif; ?>
            </div>
            <div></div>
        </div>

        <div class="actions">
            <a class="btn secondary" href="<?php echo htmlspecialchars($backRoute, ENT_QUOTES, 'UTF-8'); ?>">Back to Doctor List</a>
            <?php if (! $isReadOnly && $canSaveDoctor): ?>
                <button type="submit" class="btn primary"><?php echo htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8'); ?></button>
            <?php endif; ?>
        </div>
    </form>
</section>

<?php medis_render_navigation_end(); ?>
<?php if (! $isReadOnly): ?>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
(() => {
    const form = document.querySelector('form');
    const canvas = document.getElementById('signature-pad');
    const hiddenInput = document.getElementById('doctor_sign_data');
    const clearButton = document.getElementById('clear-signature');
    const pictureInput = document.getElementById('doctor_picture_upload');
    const pictureImage = document.getElementById('doctorPictureImage');
    const pictureInitial = document.getElementById('doctorPictureInitial');
    const firstNameInput = document.getElementById('doctor_firstName');
    const lastNameInput = document.getElementById('doctor_lastName');
    const nricInput = document.getElementById('doctor_NRIC');
    const passportInput = document.getElementById('doctor_passportNo');
    const profileName = document.getElementById('doctorPictureName');
    const existingSignature = <?php echo json_encode($signaturePath !== '', JSON_THROW_ON_ERROR); ?>;
    const isEditMode = <?php echo json_encode($pageMode === 'edit', JSON_THROW_ON_ERROR); ?>;

    const syncProfileText = () => {
        const fullName = [firstNameInput?.value || '', lastNameInput?.value || ''].join(' ').trim();
        profileName.textContent = fullName !== '' ? fullName : 'Doctor Name';
        if (pictureInitial) {
            pictureInitial.textContent = fullName !== '' ? fullName.charAt(0).toUpperCase() : 'D';
        }
    };

    if (pictureInput) {
        pictureInput.addEventListener('change', () => {
            const file = pictureInput.files && pictureInput.files[0] ? pictureInput.files[0] : null;
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                if (!pictureImage || !pictureInitial) {
                    return;
                }
                pictureImage.src = String(event.target?.result || '');
                pictureImage.style.display = 'block';
                pictureInitial.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }

    if (firstNameInput) {
        firstNameInput.addEventListener('input', syncProfileText);
    }

    if (lastNameInput) {
        lastNameInput.addEventListener('input', syncProfileText);
    }

    const syncIdentityRequirement = () => {
        const hasNric = (nricInput?.value || '').trim() !== '';
        const hasPassport = (passportInput?.value || '').trim() !== '';

        if (nricInput) {
            nricInput.required = !hasPassport;
        }

        if (passportInput) {
            passportInput.required = !hasNric;
        }
    };

    if (nricInput) {
        nricInput.addEventListener('input', syncIdentityRequirement);
    }

    if (passportInput) {
        passportInput.addEventListener('input', syncIdentityRequirement);
    }

    syncProfileText();
    syncIdentityRequirement();

    if (!form || !canvas || !hiddenInput || typeof SignaturePad === 'undefined') {
        return;
    }

    const resizeCanvas = () => {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const width = canvas.offsetWidth || 400;
        const height = 220;
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        canvas.style.height = height + 'px';
    };

    resizeCanvas();

    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgb(255,255,255)',
        penColor: 'rgb(15,23,42)',
    });

    clearButton?.addEventListener('click', () => {
        signaturePad.clear();
        hiddenInput.value = '';
    });

    form.addEventListener('submit', (event) => {
        hiddenInput.value = signaturePad.isEmpty() ? '' : signaturePad.toDataURL('image/png');

        const hasNric = (nricInput?.value || '').trim() !== '';
        const hasPassport = (passportInput?.value || '').trim() !== '';
        if (!hasNric && !hasPassport) {
            event.preventDefault();
            alert('Please fill in either NRIC or Passport Number before saving.');
            return;
        }

        if (!isEditMode && signaturePad.isEmpty()) {
            event.preventDefault();
            alert('Please provide the doctor eSign signature before saving.');
            return;
        }

        if (isEditMode && signaturePad.isEmpty() && !existingSignature) {
            event.preventDefault();
            alert('Please provide the doctor eSign signature before saving.');
        }
    });

    window.addEventListener('resize', () => {
        const savedData = signaturePad.isEmpty() ? null : signaturePad.toData();
        resizeCanvas();
        signaturePad.clear();
        if (savedData && savedData.length > 0) {
            signaturePad.fromData(savedData);
        }
    });
})();
</script>
<?php endif; ?>
</body>
</html>
