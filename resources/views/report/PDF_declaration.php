<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__) . '/panel/navigation.php';

$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$query = request();
$declarationId = (int) $query->query('declaration_id', 0);
$employeeId = (int) $query->query('employee_id', 0);
$companyId = (int) $query->query('company_id', 0);
$surveillanceId = (int) $query->query('surveillance_id', 0);

$declaration = $declarationId > 0 && Schema::hasTable('declaration')
    ? DB::table('declaration')->where('declaration_id', $declarationId)->first()
    : null;

if (! $declaration && Schema::hasTable('declaration')) {
    $declaration = DB::table('declaration')
        ->when($employeeId > 0, fn ($row) => $row->where('employee_id', $employeeId))
        ->when($companyId > 0, fn ($row) => $row->where('company_id', $companyId))
        ->when($surveillanceId > 0, fn ($row) => $row->where('surveillance_id', $surveillanceId))
        ->orderByDesc('declaration_id')
        ->first();
}

$employeeId = (int) ($declaration->employee_id ?? $employeeId);
$companyId = (int) ($declaration->company_id ?? $companyId);

$employee = $employeeId > 0 && Schema::hasTable('employee')
    ? DB::table('employee')->where('employee_id', $employeeId)->first()
    : null;
$company = $companyId > 0 && Schema::hasTable('company')
    ? DB::table('company')->where('company_id', $companyId)->first()
    : null;
$doctor = ! empty($declaration->doctor_id) && Schema::hasTable('doctor')
    ? DB::table('doctor')->where('doctor_id', (int) $declaration->doctor_id)->first()
    : null;

$showValue = static function ($value, string $fallback = '-'): string {
    $value = trim((string) ($value ?? ''));
    return $value !== '' ? $value : $fallback;
};
$formatDate = static function ($value) use ($showValue): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return '-';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y', $timestamp) : $showValue($value);
};
$toSignatureDataUrl = static function ($value): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return '';
    }
    if (str_starts_with($value, 'data:image')) {
        return $value;
    }
    return 'data:image/png;base64,' . base64_encode($value);
};

$workerName = trim((string) (($employee->employee_firstName ?? $declaration->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? $declaration->employee_lastName ?? '')));
$doctorName = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
$doctorName = $doctorName !== '' ? $doctorName : trim((string) ($doctor->doctor_username ?? 'Doctor'));
$employeeSignature = $toSignatureDataUrl($declaration->employee_signature ?? '');
$doctorSignature = $toSignatureDataUrl($declaration->doctor_signature ?? ($doctor->doctor_sign ?? ''));

medis_render_navigation_start([
    'clinicName' => $clinicName ?? 'Medis SHAMS',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'User',
    'active' => 'report',
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PDF Declaration</title>
</head>
<body>
<style>
@page{size:A4 portrait;margin:10mm}
html,body{margin:0;padding:0;background:#fff;color:#0f172a;font-family:Arial,Helvetica,sans-serif;font-size:11pt}
.report-page{width:100%;max-width:none;margin:0;color:#0f172a;font-family:Arial,Helvetica,sans-serif;font-size:11pt}
.sheet{width:186mm;min-height:273mm;margin:0 auto;padding:0;background:#fff}
.sheet-top{position:relative;display:block;text-align:center;margin-bottom:10px}
.center-title{display:block;width:100%;text-align:center;font-size:11pt;font-weight:700;line-height:1.35;color:#0f172a}
.right-code{position:absolute;right:0;top:0;font-size:11pt;font-weight:700;line-height:1.35;color:#0f172a}
.sheet-title{text-align:center;margin-bottom:18px}
.sheet-title .line{font-size:11pt;font-weight:700;line-height:1.35}
.sheet-title .main{font-size:13pt;font-weight:700;line-height:1.35;margin-top:8px;letter-spacing:.02em}
.document-table{width:100%;border-collapse:collapse;margin-bottom:16px;table-layout:fixed}
.document-table th,.document-table td{border:1px solid #c9d8ea;padding:8px 10px;font-size:11pt;vertical-align:top;text-align:left;word-wrap:break-word}
.document-table th{font-weight:700;color:#0f172a;background:#fff}
.section-block{margin-top:22px}
.section-heading{display:flex;align-items:center;gap:14px;margin:0 0 14px}
.section-heading::after{content:"";flex:1;height:1px;background:#c9d8ea}
.section-heading span{font-size:11pt;font-weight:700;letter-spacing:.02em;text-transform:uppercase;white-space:nowrap}
.statement{border:1px solid #c9d8ea;padding:14px 16px;font-size:11pt;line-height:1.8}
.signature-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.signature-card{border:1px solid #c9d8ea;padding:14px;min-height:170px;display:grid;gap:12px}
.signature-card strong{font-size:11pt}
.signature-box{border:1px dashed #c9d8ea;min-height:90px;display:flex;align-items:center;justify-content:center;padding:10px;background:#fff}
.signature-box img{max-width:100%;max-height:80px;object-fit:contain}
.signature-meta{display:grid;gap:6px;font-size:11pt}
@media print{.app-topbar,.app-sidebar{display:none !important}.app-shell,.app-main,.app-page,.app-card{display:block;height:auto;overflow:visible;padding:0!important;border:0!important;background:#fff!important}.sheet{width:auto;min-height:auto;padding:0;border:0;box-shadow:none}}
</style>
<div class="report-page">
    <section class="sheet">
        <div class="sheet-top">
            <span class="center-title">Occupational Safety and Health Act 1994 (Act 514)</span>
            <span class="right-code">DECLARATION</span>
        </div>
        <div class="sheet-title">
            <div class="line">Medical Surveillance Declaration</div>
            <div class="main">DECLARATION</div>
        </div>

        <table class="document-table">
            <tbody>
                <tr>
                    <th>Company Name</th>
                    <td><?php echo $esc($showValue($company->company_name ?? null)); ?></td>
                    <th>Patient Name</th>
                    <td><?php echo $esc($showValue($workerName)); ?></td>
                </tr>
                <tr>
                    <th>NRIC / Passport</th>
                    <td><?php echo $esc($showValue(($employee->employee_NRIC ?? '') !== '' ? ($employee->employee_NRIC ?? '') : ($employee->employee_passportNo ?? ''))); ?></td>
                    <th>Doctor</th>
                    <td><?php echo $esc($showValue($doctorName)); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="section-block">
            <div class="section-heading"><span>Declaration Statement</span></div>
            <div class="statement">
                This is to certify that the above statement is true. I hereby give consent to the Occupational Health Doctor (OHD)
                to perform medical examination, necessary tests and communicate with the employer the results of my medical
                examination and work capability.
            </div>
        </div>

        <div class="section-block">
            <div class="section-heading"><span>Signatures</span></div>
            <div class="signature-grid">
                <div class="signature-card">
                    <strong>Signed by Patient</strong>
                    <div class="signature-box">
                        <?php if ($employeeSignature !== ''): ?>
                            <img src="<?php echo $esc($employeeSignature); ?>" alt="Patient signature">
                        <?php else: ?>
                            <span><?php echo $esc('No signature'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="signature-meta">
                        <div><strong>Name:</strong> <?php echo $esc($showValue($workerName)); ?></div>
                        <div><strong>Date:</strong> <?php echo $esc($formatDate($declaration->employee_date ?? null)); ?></div>
                    </div>
                </div>

                <div class="signature-card">
                    <strong>Witnessed by Doctor</strong>
                    <div class="signature-box">
                        <?php if ($doctorSignature !== ''): ?>
                            <img src="<?php echo $esc($doctorSignature); ?>" alt="Doctor signature">
                        <?php else: ?>
                            <span><?php echo $esc('No signature'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="signature-meta">
                        <div><strong>Name:</strong> <?php echo $esc($showValue($doctorName)); ?></div>
                        <div><strong>Date:</strong> <?php echo $esc($formatDate($declaration->doctor_date ?? null)); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?php medis_render_navigation_end(); ?>
</body>
</html>
