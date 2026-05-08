<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;

$esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$query = request();
$declarationId = (int) $query->query('declaration_id', 0);
$employeeId = (int) $query->query('employee_id', 0);
$companyId = (int) $query->query('company_id', 0);
$surveillanceId = (int) $query->query('surveillance_id', 0);

$declaration = $declarationId > 0 && DB::getSchemaBuilder()->hasTable('declaration')
    ? DB::table('declaration')->where('declaration_id', $declarationId)->first()
    : null;
if (! $declaration && DB::getSchemaBuilder()->hasTable('declaration')) {
    $declaration = DB::table('declaration')
        ->when($employeeId > 0, fn ($q) => $q->where('employee_id', $employeeId))
        ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
        ->when($surveillanceId > 0, fn ($q) => $q->where('surveillance_id', $surveillanceId))
        ->orderByDesc('declaration_id')
        ->first();
}
$surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);
$employeeId = (int) ($declaration->employee_id ?? $employeeId);
$companyId = (int) ($declaration->company_id ?? $companyId);

$employee = $employeeId > 0 ? DB::table('employee')->where('employee_id', $employeeId)->first() : null;
$company = $companyId > 0 ? DB::table('company')->where('company_id', $companyId)->first() : null;
$chemical = $surveillanceId > 0 ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first() : null;
$removal = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('removal_report')
    ? DB::table('removal_report')->where('surveillance_id', $surveillanceId)->first()
    : null;
$doctor = ! empty($declaration->doctor_id) && DB::getSchemaBuilder()->hasTable('doctor')
    ? DB::table('doctor')->where('doctor_id', $declaration->doctor_id)->first()
    : null;
$doctorSignature = trim((string) ($doctor->doctor_sign ?? $declaration->doctor_signature ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>USECHH 5i Medical Removal Protection</title>
</head>
<body>
<style>
@page{size:A4 portrait;margin:12mm}
body{margin:0;padding:18px;background:#fff;color:#0f172a;font-family:"Poppins","Segoe UI",Tahoma,Geneva,Verdana,sans-serif}
.sheet{display:grid;gap:18px}
.report-card{border:1px solid #d9e6dd;border-radius:22px;background:#fff;overflow:hidden}
.report-head{padding:18px 22px;border-bottom:1px solid #e5efe7}
.eyebrow{font-size:.82rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#2f7a45}
.report-title{margin:6px 0 0;font-size:1.7rem}
.report-subtitle{margin:6px 0 0;color:#5f6f65}
.section{padding:20px 22px;border-top:1px solid #edf2ee}
.toggle-group{display:flex;gap:14px;flex-wrap:wrap}
.toggle{display:inline-flex;align-items:center;gap:10px;padding:12px 16px;border:1px solid #dbe7de;border-radius:16px;background:#fbfdfb;font-weight:600}
.toggle.active{border-color:#2f9e44;background:#edf8f0;color:#1f5f35}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.field{border:1px solid #dbe7de;border-radius:16px;padding:14px 16px;background:#fbfdfb}
.field-label{display:block;margin-bottom:6px;font-size:.76rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6b7d71}
.field-value{font-size:1rem;font-weight:600}
.field.full{grid-column:1 / -1}
.reason-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.reason{border:1px solid #dbe7de;border-radius:16px;padding:14px 16px;background:#fff;display:flex;gap:12px;align-items:flex-start}
.tick{width:22px;height:22px;border:2px solid #2f9e44;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-weight:800;color:#2f9e44;flex:0 0 auto}
.sign-shell{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.sign-box{border:1px dashed #b8ccb9;border-radius:18px;padding:18px;min-height:160px;text-align:center}
.sign-box img{display:block;max-width:220px;max-height:72px;object-fit:contain;margin:0 auto 12px}
.muted{color:#64756b}
@media print{body{padding:0}.report-card{break-inside:avoid}}
</style>
<div class="sheet">
    <?php require __DIR__ . '/partials/clinic_header.php'; ?>
    <section class="report-card">
        <div class="report-head">
            <div class="eyebrow">USECHH 5i</div>
            <h1 class="report-title">Medical Removal Protection</h1>
            <p class="report-subtitle">Protective action plan for employees requiring temporary or permanent removal from exposure.</p>
        </div>

        <div class="section">
            <div class="toggle-group">
                <div class="toggle<?php echo ($removal->removal_type ?? '') === 'Temporary' ? ' active' : ''; ?>">Temporary</div>
                <div class="toggle<?php echo ($removal->removal_type ?? '') === 'Permanent' ? ' active' : ''; ?>">Permanent</div>
            </div>
        </div>

        <div class="section">
            <div class="grid">
                <div class="field"><span class="field-label">Worker Name</span><div class="field-value"><?php echo $esc(trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? '')))); ?></div></div>
                <div class="field"><span class="field-label">NRIC / Passport</span><div class="field-value"><?php echo $esc((string) (($employee->employee_NRIC ?? '') !== '' ? ($employee->employee_NRIC ?? '') : ($employee->employee_passportNo ?? '-'))); ?></div></div>
                <div class="field"><span class="field-label">Date of Birth</span><div class="field-value"><?php echo $esc((string) ($employee->employee_DOB ?? '-')); ?></div></div>
                <div class="field"><span class="field-label">Sex</span><div class="field-value"><?php echo $esc((string) ($employee->employee_gender ?? '-')); ?></div></div>
                <div class="field full"><span class="field-label">Workplace</span><div class="field-value"><?php echo $esc((string) ($company->company_name ?? '-')); ?></div></div>
                <div class="field"><span class="field-label">Start of Employment</span><div class="field-value"><?php echo $esc((string) ($employee->employee_DOB ?? '-')); ?></div></div>
                <div class="field"><span class="field-label">Duration of Employment</span><div class="field-value"><?php echo $esc((string) ($recommendation->MRPdate_end ?? 'Not recorded')); ?></div></div>
                <div class="field full"><span class="field-label">Hazard / Chemical</span><div class="field-value"><?php echo $esc((string) ($chemical->chemicals ?? '-')); ?></div></div>
            </div>
        </div>

        <div class="section">
            <div class="field full"><span class="field-label">Recommendation Narrative</span><div class="field-value"><?php echo nl2br($esc((string) ($removal->reasons_recommendations ?? 'No recommendations recorded.'))); ?></div></div>
        </div>

        <div class="section">
            <div class="reason-grid">
                <?php
                $reasons = [
                    'Pregnancy',
                    'Breastfeeding',
                    'Abnormal BM/BEM result',
                    'Adverse health effects based on clinical findings',
                    'Target organ function test abnormality',
                    'Other medical surveillance concern',
                ];
                foreach ($reasons as $reason):
                    $isActive = stripos((string) ($removal->reasons_recommendations ?? ''), $reason) !== false;
                ?>
                    <div class="reason">
                        <span class="tick"><?php echo $isActive ? '✓' : ''; ?></span>
                        <div><?php echo $esc($reason); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section">
            <div class="sign-shell">
                <div class="grid">
                    <div class="field"><span class="field-label">OHD Name</span><div class="field-value"><?php echo $esc(trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')))); ?></div></div>
                    <div class="field"><span class="field-label">Address of Practice</span><div class="field-value"><?php echo $esc(trim((string) (($doctor->doctor_address ?? '') . ', ' . ($doctor->doctor_postcode ?? '') . ' ' . ($doctor->doctor_district ?? '') . ', ' . ($doctor->doctor_state ?? '')))); ?></div></div>
                    <div class="field"><span class="field-label">Email</span><div class="field-value"><?php echo $esc((string) ($doctor->doctor_email ?? '-')); ?></div></div>
                    <div class="field"><span class="field-label">Telephone / Fax</span><div class="field-value"><?php echo $esc(trim((string) (($doctor->doctor_telephone ?? '-') . ' / ' . ($doctor->doctor_fax ?? '-')))); ?></div></div>
                </div>
                <div class="sign-box">
                    <?php if ($doctorSignature !== ''): ?>
                        <img src="<?php echo $esc($doctorSignature); ?>" alt="Doctor signature">
                    <?php endif; ?>
                    <div><strong>OHD Signature</strong></div>
                    <div class="muted"><?php echo $esc((string) ($declaration->doctor_date ?? '-')); ?></div>
                </div>
            </div>
        </div>
    </section>
</div>
</body>
</html>
