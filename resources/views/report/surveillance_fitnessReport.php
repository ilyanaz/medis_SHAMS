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
$chemical = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('chemical_information')
    ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first()
    : null;
$fitnessReport = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('fitness_report')
    ? DB::table('fitness_report')->where('surveillance_id', $surveillanceId)->first()
    : null;
$recommendation = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('recommendation')
    ? DB::table('recommendation')->where('surveillance_id', $surveillanceId)->first()
    : null;
$doctor = ! empty($declaration->doctor_id) && DB::getSchemaBuilder()->hasTable('doctor')
    ? DB::table('doctor')->where('doctor_id', $declaration->doctor_id)->first()
    : null;

$employeeName = trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? '')));
$doctorName = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
$doctorName = $doctorName !== '' ? $doctorName : trim((string) ($doctor->doctor_username ?? 'Doctor'));
$fitnessResult = trim((string) ($fitnessReport->result ?? 'Pending review'));
$savedRemarks = trim((string) ($fitnessReport->remarks ?? ''));
$remarksValue = old('remarks', $savedRemarks);
$remarksPrint = trim((string) ($savedRemarks !== '' ? $savedRemarks : 'NA'));
$doctorSignature = trim((string) ($doctor->doctor_sign ?? $declaration->doctor_signature ?? ''));
$statusMessage = (string) session('status', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>USECHH 3 Certificate of Fitness</title>
</head>
<body>
<style>
@page{size:A4 portrait;margin:12mm}
body{margin:0;padding:18px;background:#fff;color:#0f172a;font-family:"Poppins","Segoe UI",Tahoma,Geneva,Verdana,sans-serif}
.sheet{display:grid;gap:18px}
.clinic-report-header{padding:0 0 8px}
.clinic-report-header img{display:block;width:100%;max-width:100%;max-height:none;height:auto;object-fit:contain}
.report-card{background:#fff;overflow:hidden}
.report-head{padding:6px 0 14px;border-bottom:2px solid #dce8de}
.report-head-top{position:relative;display:block;text-align:center}
.report-code{position:absolute;right:0;top:0;font-size:14px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#0f172a}
.report-head-act{font-size:14px;font-weight:700;line-height:1.35}
.report-head-regulation{margin-top:4px;font-size:15px;font-weight:700;line-height:1.35}
.report-title{margin:12px 0 0;text-align:center;font-size:18px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.section{padding:18px 0;border-top:1px solid #edf2ee}
.section:first-of-type{border-top:0}
.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 24px}
.detail-line{display:grid;grid-template-columns:170px 1fr;gap:12px;align-items:start;padding:6px 0}
.detail-line.full{grid-column:1 / -1}
.detail-label{font-size:.82rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#6b7d71}
.detail-value{font-size:1rem;font-weight:500}
.narrative{line-height:1.8}
.narrative strong{font-weight:700}
.signature-grid{display:grid;grid-template-columns:1.1fr 1fr;gap:22px;align-items:start}
.sign-box{padding:8px 0 0;min-height:150px;text-align:center}
.sign-box img{display:block;max-width:220px;max-height:72px;object-fit:contain;margin:0 auto 12px}
.doctor-meta{display:grid;gap:10px}
.meta-row{padding:6px 0}
.meta-row strong{display:block;font-size:.8rem;letter-spacing:.04em;text-transform:uppercase;color:#6b7d71;margin-bottom:4px}
.muted{color:#64756b}
.flash{margin:0 0 8px;padding:10px 14px;border:1px solid #cfe7d4;border-radius:12px;background:#f3fbf4;color:#1f5f35;font-size:.9rem}
.remarks-form{display:grid;gap:12px}
.remarks-textarea{width:100%;min-height:120px;padding:12px 14px;border:1px solid #cbd5e1;border-radius:14px;font:inherit;resize:vertical;background:#fff;color:#0f172a}
.remarks-actions{display:flex;justify-content:flex-end}
.save-btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 18px;border:1px solid #2f9e44;border-radius:999px;background:#2f9e44;color:#fff;font:inherit;font-weight:700;cursor:pointer}
.print-only{display:none}
@media print{body{padding:0}.report-card{break-inside:avoid}.clinic-report-header{padding-bottom:4px}.report-head{padding-top:0}.screen-only{display:none!important}.print-only{display:block}.remarks-print{white-space:pre-wrap}}
</style>
<div class="sheet">
    <?php require __DIR__ . '/partials/clinic_header.php'; ?>

    <section class="report-card">
        <div class="report-head">
            <div class="report-head-top">
                <div class="report-code">USECHH 3</div>
                <div class="report-head-act">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="report-head-regulation">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                <h1 class="report-title">Certificate of Fitness</h1>
            </div>
        </div>

        <?php if ($statusMessage !== ''): ?>
            <div class="flash screen-only"><?php echo $esc($statusMessage); ?></div>
        <?php endif; ?>

        <div class="section">
            <div class="detail-grid">
                <div class="detail-line"><div class="detail-label">Person Examined</div><div class="detail-value"><?php echo $esc($employeeName !== '' ? $employeeName : 'Not recorded'); ?></div></div>
                <div class="detail-line"><div class="detail-label">NRIC / Passport</div><div class="detail-value"><?php echo $esc((string) (($employee->employee_NRIC ?? '') !== '' ? ($employee->employee_NRIC ?? '') : ($employee->employee_passportNo ?? '-'))); ?></div></div>
                <div class="detail-line"><div class="detail-label">Date of Birth</div><div class="detail-value"><?php echo $esc((string) ($employee->employee_DOB ?? '-')); ?></div></div>
                <div class="detail-line"><div class="detail-label">Sex</div><div class="detail-value"><?php echo $esc((string) ($employee->employee_gender ?? '-')); ?></div></div>
                <div class="detail-line full"><div class="detail-label">Employer</div><div class="detail-value"><?php echo $esc((string) ($company->company_name ?? '-')); ?></div></div>
                <div class="detail-line full"><div class="detail-label">Employer Address</div><div class="detail-value"><?php echo $esc(trim((string) (($company->company_address ?? '') . ', ' . ($company->company_postcode ?? '') . ' ' . ($company->company_district ?? '') . ', ' . ($company->company_state ?? '')))); ?></div></div>
            </div>
        </div>

        <div class="section">
            <div class="narrative">
                I hereby certify that I have examined the above-named person on
                <strong><?php echo $esc((string) ($chemical->examination_date ?? $declaration->doctor_date ?? '-')); ?></strong>
                and that he is <strong><?php echo $esc(strtolower($fitnessResult)); ?></strong>
                for work which may expose him to <strong><?php echo $esc((string) ($chemical->chemicals ?? 'the stated chemical hazard')); ?></strong>.
            </div>
        </div>

        <div class="section">
            <div class="detail-line full print-only">
                <div class="detail-label">Remarks</div>
                <div class="detail-value remarks-print"><?php echo nl2br($esc($remarksPrint)); ?></div>
            </div>
            <form class="remarks-form screen-only" method="post" action="<?php echo $esc(route('surveillance.report.fitness.save')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="declaration_id" value="<?php echo $esc((string) ($declaration->declaration_id ?? $declarationId)); ?>">
                <input type="hidden" name="employee_id" value="<?php echo $esc((string) $employeeId); ?>">
                <input type="hidden" name="company_id" value="<?php echo $esc((string) $companyId); ?>">
                <input type="hidden" name="surveillance_id" value="<?php echo $esc((string) $surveillanceId); ?>">
                <div class="detail-label">Remarks</div>
                <textarea class="remarks-textarea" name="remarks" placeholder="Enter remarks for USECHH 3 before printing..."><?php echo $esc((string) $remarksValue); ?></textarea>
                <div class="remarks-actions">
                    <button class="save-btn" type="submit">Save Remarks</button>
                </div>
            </form>
        </div>

        <div class="section">
            <div class="signature-grid">
                <div class="sign-box">
                    <?php if ($doctorSignature !== ''): ?>
                        <img src="<?php echo $esc($doctorSignature); ?>" alt="Doctor signature">
                    <?php endif; ?>
                    <div><strong><?php echo $esc($doctorName); ?></strong></div>
                    <div class="muted">Occupational Health Doctor</div>
                    <div class="muted"><?php echo $esc((string) ($doctor->OHD_registrationNo ?? $doctor->MMC_no ?? '-')); ?></div>
                </div>
                <div class="doctor-meta">
                    <div class="meta-row"><strong>Signature Date</strong><?php echo $esc((string) ($declaration->doctor_date ?? '-')); ?></div>
                    <div class="meta-row"><strong>Address of Practice</strong><?php echo $esc(trim((string) (($doctor->doctor_address ?? '') . ', ' . ($doctor->doctor_postcode ?? '') . ' ' . ($doctor->doctor_district ?? '') . ', ' . ($doctor->doctor_state ?? '')))); ?></div>
                    <div class="meta-row"><strong>Telephone</strong><?php echo $esc((string) ($doctor->doctor_telephone ?? '-')); ?></div>
                    <div class="meta-row"><strong>Email</strong><?php echo $esc((string) ($doctor->doctor_email ?? '-')); ?></div>
                </div>
            </div>
        </div>
    </section>
</div>
</body>
</html>
