<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Surveillance Declaration</title></head>
<body>
<?php
require dirname(__DIR__) . '/panel/navigation.php';
$esc = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$toSignatureDataUrl = static function ($value) {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return '';
    }
    if (strpos($value, 'data:image') === 0) {
        return $value;
    }
    return 'data:image/png;base64,' . base64_encode($value);
};
$statusMessage = session('status');
$request = request();
$activeClinicId = (int) $request->session()->get('active_clinic_id', 0);
$selectedCompanyId = $selectedCompany->company_id ?? $declaration->company_id ?? $request->query('company_id') ?? '';
$selectedEmployeeId = $selectedEmployee->employee_id ?? $declaration->employee_id ?? $request->query('employee_id') ?? '';
$declarationId = $declarationId ?? $declaration->declaration_id ?? request()->query('declaration_id') ?? '';

if ((!isset($selectedCompany) || !$selectedCompany) && (int) $selectedCompanyId > 0 && \Illuminate\Support\Facades\Schema::hasTable('company')) {
    $selectedCompanyQuery = \Illuminate\Support\Facades\DB::table('company')
        ->where('company_id', (int) $selectedCompanyId);

    if ($activeClinicId > 0 && \Illuminate\Support\Facades\Schema::hasColumn('company', 'clinic_id')) {
        $selectedCompanyQuery->where('clinic_id', $activeClinicId);
    }

    $selectedCompany = $selectedCompanyQuery->first([
        'company_id',
        'company_name',
    ]);
}

if ((!isset($selectedEmployee) || !$selectedEmployee) && (int) $selectedEmployeeId > 0 && \Illuminate\Support\Facades\Schema::hasTable('employee')) {
    $selectedEmployeeQuery = \Illuminate\Support\Facades\DB::table('employee')
        ->where('employee_id', (int) $selectedEmployeeId);

    if ($activeClinicId > 0 && \Illuminate\Support\Facades\Schema::hasColumn('employee', 'clinic_id')) {
        $selectedEmployeeQuery->where('clinic_id', $activeClinicId);
    }

    $selectedEmployee = $selectedEmployeeQuery->first([
        'employee_id',
        'employee_firstName',
        'employee_lastName',
    ]);
}
$stepHistory = function_exists('route') ? route('surveillance.list', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId]) : '#';
$stepExam = function_exists('route') ? route('surveillance.examination', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId, 'declaration_id' => $declarationId]) : '#';
$saveDeclarationUrl = function_exists('route') ? route('surveillance.declaration.save') : '#';
$steps = [
    ['label' => 'Company', 'url' => function_exists('route') ? route('surveillance.company') : '#'],
    ['label' => 'Patient', 'url' => function_exists('route') ? route('surveillance.patient', ['company_id' => $selectedCompanyId]) : '#'],
    ['label' => 'Surveillance List', 'url' => function_exists('route') ? route('surveillance.list', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId]) : '#'],
    ['label' => 'Declaration', 'url' => function_exists('route') ? route('surveillance.declaration', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId, 'declaration_id' => $declarationId]) : '#', 'active' => true],
    ['label' => 'Examination', 'url' => function_exists('route') ? route('surveillance.examination', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId, 'declaration_id' => $declarationId]) : '#'],
    ['label' => 'Report', 'url' => function_exists('route') ? route('surveillance.report', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId, 'declaration_id' => $declarationId]) : '#'],
];
$employeeSignatureValue = old('employee_signature', $toSignatureDataUrl($declaration->employee_signature ?? ''));
$storedDoctorSignature = trim((string) ($declaration->doctor_signature ?? ''));
$doctorSetupSignature = trim((string) ($doctor->doctor_sign ?? ''));
$doctorSignaturePath = trim((string) old('doctor_signature', $storedDoctorSignature !== '' ? $storedDoctorSignature : $doctorSetupSignature));
$doctorSignatureValue = $doctorSignaturePath;
$doctorSignaturePreviewUrl = $doctorSignatureUrl ?? ($doctorSignaturePath !== '' && strpos($doctorSignaturePath, 'data:image') !== 0 ? asset($doctorSignaturePath) : $doctorSignaturePath);
$showRecordTabs = !empty($declarationId) || (string) request()->query('record_mode', '') !== '';
$recordExaminationUrl = !empty($declarationId) && function_exists('route')
    ? route('surveillance.record.edit', ['declaration' => $declarationId])
    : $stepExam;
medis_render_navigation_start([
    'clinicName' => $clinicName ?? 'Medis SHAMS',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'User',
    'active' => 'surveillance',
    'showSurveillanceSubnav' => true,
    'surveillanceSubActive' => 'declaration',
    'pageSubtitle' => 'Review declaration and collect signatures',
]);
?>
<style>
.app-page,.app-card{overflow:auto}
.content{padding:4px 6px;overflow:auto;min-height:0;margin-top:0;border:0;background:transparent;border-radius:0}
.head h2{margin:0 0 12px;font-size:1.8rem}
.head p{margin:6px 0 0;color:#6b7280}
.status{margin-top:14px;padding:10px 12px;border:1px solid #a7f3d0;background:#ecfdf3;color:#065f46;border-radius:12px}
.error{margin-top:14px;padding:10px 12px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;border-radius:12px}
.statement{margin-top:16px;display:grid;gap:16px}
.statement-copy{margin:0;color:#4b5563;line-height:1.7}
.meta-grid,.sign-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.meta-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
.field{display:grid;gap:6px}
.field input{border:1px solid #d1d5db;border-radius:12px;padding:10px 12px}
.field input[readonly]{background:#f9fafb;color:#4b5563}
.sign-card{border:1px solid #e5e7eb;border-radius:16px;padding:14px;display:grid;gap:12px}
.sign-card strong{font-size:1rem;color:#111827}
.signature-pad{height:180px;border:1px dashed #cbd5e1;border-radius:12px;background:#fcfcfd}
.signature-pad canvas{width:100%;height:100%;display:block}
.signature-preview{height:180px;border:1px dashed #cbd5e1;border-radius:12px;background:#fcfcfd;display:flex;align-items:center;justify-content:center;padding:12px}
.signature-preview img{max-width:100%;max-height:100%;object-fit:contain}
.signature-preview-note{font-size:.9rem;color:#6b7280;text-align:center;line-height:1.6}
.signature-actions{display:flex;justify-content:flex-end}
.btn.small{padding:7px 12px;font-size:.84rem;border-radius:10px}
.actions{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px}
.actions-right{display:flex;gap:10px;flex-wrap:wrap}
.btn,.next{display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;background:#fff;color:#374151;cursor:pointer;font:inherit}
.next{background:#389B5B;border-color:#389B5B;color:#fff}
.record-tabs{display:flex;gap:18px;align-items:center;padding:0 6px 8px;border-bottom:1px solid #edf0f2;flex-wrap:wrap}
.record-tab{appearance:none;border:0;background:transparent;padding:14px 0 12px;font:inherit;font-weight:600;color:#4b5563;cursor:pointer;position:relative;text-decoration:none}
.record-tab.is-active{color:#166534}
.record-tab.is-active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:#389B5B;border-radius:999px}
@media(max-width:1100px){.stepper{padding:14px}.step-list{grid-template-columns:repeat(3,minmax(0,1fr))}.step-label{max-width:none}.meta-grid,.sign-grid{grid-template-columns:1fr}}
</style>
<style>
.flow{min-height:auto}
.content{min-height:auto;display:flex;flex-direction:column;overflow:visible;padding-bottom:32px}
.actions{margin-top:24px;padding-top:24px}
@media(max-width:1180px){.flow{min-height:auto}.content{min-height:auto}}
@media(max-width:760px){.content{padding:0}}
</style>
<div class="flow">
    <section class="content">
        <?php if ($showRecordTabs): ?>
            <div class="record-tabs">
                <a class="record-tab is-active" href="<?php echo $esc(route('surveillance.declaration', array_filter(['company_id' => $selectedCompanyId ?: null, 'employee_id' => $selectedEmployeeId ?: null, 'declaration_id' => $declarationId ?: null, 'record_mode' => 1]))); ?>">Declaration</a>
                <a class="record-tab" href="<?php echo $esc($recordExaminationUrl); ?>">Examination</a>
            </div>
        <?php endif; ?>
        <div class="head">
            <h2>Declaration</h2>
        </div>
        <?php if (!empty($statusMessage)): ?><div class="status"><?php echo $esc($statusMessage); ?></div><?php endif; ?>
        <?php if (isset($errors) && $errors->any()): ?><div class="error"><?php echo $esc($errors->first()); ?></div><?php endif; ?>
        <form method="POST" action="<?php echo $esc($saveDeclarationUrl); ?>" id="declarationForm">
            <input type="hidden" name="_token" value="<?php echo $esc(csrf_token()); ?>">
            <input type="hidden" name="company_id" value="<?php echo $esc(old('company_id', $selectedCompanyId)); ?>">
            <input type="hidden" name="employee_id" value="<?php echo $esc(old('employee_id', $selectedEmployeeId)); ?>">
            <input type="hidden" name="declaration_id" value="<?php echo $esc(old('declaration_id', $declarationId)); ?>">
            <input type="hidden" name="employee_signature" id="employee_signature" value="<?php echo $esc($employeeSignatureValue); ?>">
            <input type="hidden" name="doctor_signature" id="doctor_signature" value="<?php echo $esc($doctorSignatureValue); ?>">
            <div class="statement">
                <strong>Declaration</strong>
                <p class="statement-copy">This is to certify that the above statement is true. I hereby give consent to the Occupational Health Doctor (OHD) to perform medical examination, necessary tests and communicate with the employer the results of my medical examination and work capability.</p>
                <div class="meta-grid">
                    <label class="field">Company Name<input type="text" name="company_name" value="<?php echo $esc(old('company_name', $selectedCompany->company_name ?? $declaration->company_name ?? '')); ?>" readonly></label>
                    <label class="field">First Name<input type="text" name="employee_firstName" value="<?php echo $esc(old('employee_firstName', $selectedEmployee->employee_firstName ?? $declaration->employee_firstName ?? '')); ?>" readonly></label>
                    <label class="field">Last Name<input type="text" name="employee_lastName" value="<?php echo $esc(old('employee_lastName', $selectedEmployee->employee_lastName ?? $declaration->employee_lastName ?? '')); ?>" readonly></label>
                </div>
                <div class="sign-grid">
                    <div class="sign-card">
                        <strong>Signed by Patient</strong>
                        <div class="signature-pad"><canvas id="signerPad"></canvas></div>
                        <div class="signature-actions">
                            <button class="btn small" type="button" data-clear="signerPad">Clear</button>
                        </div>
                        <label class="field">Date<input type="date" name="employee_date" value="<?php echo $esc(old('employee_date', $declaration->employee_date ?? date('Y-m-d'))); ?>" required></label>
                    </div>
                    <div class="sign-card">
                        <strong>Witnessed by Doctor</strong>
                        <div class="signature-preview">
                            <?php if ($doctorSignaturePreviewUrl !== ''): ?>
                                <img src="<?php echo $esc($doctorSignaturePreviewUrl); ?>" alt="Doctor signature">
                            <?php else: ?>
                                <div class="signature-preview-note">Doctor signature will be retrieved from Doctor Setup after the doctor uploads an e-sign file there.</div>
                            <?php endif; ?>
                        </div>
                        <label class="field">Date<input type="date" name="doctor_date" value="<?php echo $esc(old('doctor_date', $declaration->doctor_date ?? date('Y-m-d'))); ?>" required></label>
                    </div>
                </div>
            </div>
            <div class="actions">
                <a class="btn" href="<?php echo $esc($stepHistory); ?>">Back</a>
                <div class="actions-right">
                    <button class="next" type="submit">Save &amp; Continue</button>
                </div>
            </div>
        </form>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
(function () {
    function setupPad(canvasId, inputId) {
        var canvas = document.getElementById(canvasId);
        var input = document.getElementById(inputId);
        if (!canvas || !input || typeof SignaturePad === 'undefined') {
            return null;
        }
        var pad = new SignaturePad(canvas, { minWidth: 1.5, maxWidth: 2.5, penColor: '#111827' });
        function resize() {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            var rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * ratio;
            canvas.height = rect.height * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            pad.clear();
            if (input.value && input.value.indexOf('data:image') === 0) {
                pad.fromDataURL(input.value);
            }
        }
        window.addEventListener('resize', resize);
        resize();
        return {
            clear: function () {
                pad.clear();
                input.value = '';
            },
            save: function () {
                input.value = pad.toDataURL('image/png');
            },
            isEmpty: function () {
                return pad.isEmpty();
            }
        };
    }

    var signer = setupPad('signerPad', 'employee_signature');
    var form = document.getElementById('declarationForm');
    if (!form) {
        return;
    }

    Array.prototype.slice.call(document.querySelectorAll('[data-clear]')).forEach(function (button) {
        button.addEventListener('click', function () {
            var target = button.getAttribute('data-clear');
            if (target === 'signerPad' && signer) signer.clear();
        });
    });

    form.addEventListener('submit', function (event) {
        var doctorSignatureInput = document.getElementById('doctor_signature');
        if (!signer || signer.isEmpty()) {
            event.preventDefault();
            alert('Please provide the patient signature before saving.');
            return;
        }
        if (!doctorSignatureInput || !doctorSignatureInput.value.trim()) {
            event.preventDefault();
            alert('Doctor signature is not available yet. Please upload the doctor e-sign in Doctor Setup first.');
            return;
        }
        signer.save();
    });
})();
</script>
<?php medis_render_navigation_end(); ?>
</body>
</html>
