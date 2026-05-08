<?php
declare(strict_types=1);

use Illuminate\Support\Carbon;
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
        ->when($employeeId > 0, fn ($builder) => $builder->where('employee_id', $employeeId))
        ->when($companyId > 0, fn ($builder) => $builder->where('company_id', $companyId))
        ->when($surveillanceId > 0, fn ($builder) => $builder->where('surveillance_id', $surveillanceId))
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
$recommendation = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('recommendation')
    ? DB::table('recommendation')->where('surveillance_id', $surveillanceId)->first()
    : null;
$summary = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('summary_report')
    ? DB::table('summary_report')->where('surveillance_id', $surveillanceId)->first()
    : null;
$findings = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('ms_findings')
    ? DB::table('ms_findings')->where('surveillance_id', $surveillanceId)->first()
    : null;
$clinicalFindings = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('clinical_findings')
    ? DB::table('clinical_findings')->where('surveillance_id', $surveillanceId)->first()
    : null;
$targetOrgan = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('target_organ')
    ? DB::table('target_organ')->where('surveillance_id', $surveillanceId)->first()
    : null;
$biological = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('biological_monitoring')
    ? DB::table('biological_monitoring')->where('surveillance_id', $surveillanceId)->first()
    : null;
$removal = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('removal_report')
    ? DB::table('removal_report')->where('surveillance_id', $surveillanceId)->first()
    : null;
$doctor = ! empty($declaration->doctor_id) && DB::getSchemaBuilder()->hasTable('doctor')
    ? DB::table('doctor')->where('doctor_id', $declaration->doctor_id)->first()
    : null;

$occupationalRows = $employeeId > 0 && DB::getSchemaBuilder()->hasTable('occupational_history')
    ? DB::table('occupational_history')->where('employee_id', $employeeId)->orderBy('occupHistory_id')->get()
    : collect();
$occupationalCurrent = $occupationalRows->first(function ($row) use ($company) {
    return strcasecmp(trim((string) ($row->company_name ?? '')), trim((string) ($company->company_name ?? ''))) === 0;
}) ?: $occupationalRows->first();

$showValue = static function ($value, string $fallback = '-'): string {
    $value = trim((string) ($value ?? ''));
    return $value !== '' ? $value : $fallback;
};

$formatDate = static function ($value): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return '-';
    }
    try {
        return Carbon::parse($value)->format('d/m/Y');
    } catch (\Throwable) {
        return $value;
    }
};

$companyAddress = collect([
    trim((string) ($company->company_address ?? '')),
    trim((string) ($company->company_postcode ?? '')),
    trim((string) ($company->company_district ?? '')),
    trim((string) ($company->company_state ?? '')),
])->filter(static fn ($value) => $value !== '')->implode(', ');

$doctorName = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
$doctorName = $doctorName !== '' ? $doctorName : trim((string) ($doctor->doctor_username ?? 'Doctor'));
$doctorAddress = collect([
    trim((string) ($doctor->doctor_address ?? '')),
    trim((string) ($doctor->doctor_postcode ?? '')),
    trim((string) ($doctor->doctor_district ?? '')),
    trim((string) ($doctor->doctor_state ?? '')),
])->filter(static fn ($value) => $value !== '')->implode(', ');

$workerName = trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? '')));
$identityNo = trim((string) (($employee->employee_NRIC ?? '') !== '' ? ($employee->employee_NRIC ?? '') : ($employee->employee_passportNo ?? '')));
$examDateRaw = trim((string) ($chemical->examination_date ?? $declaration->doctor_date ?? ''));
$chemicalName = trim((string) ($chemical->chemicals ?? ''));
$jobTitle = trim((string) ($occupationalCurrent->job_title ?? ''));
$workUnit = trim((string) ($summary->name_of_workUnit ?? ''));
$employmentDuration = trim((string) ($occupationalCurrent->employment_duration ?? ''));
$reviewDateRaw = trim((string) ($recommendation->nextReview_date ?? $recommendation->MRPdate_end ?? ''));
$doctorSignature = trim((string) ($doctor->doctor_sign ?? $declaration->doctor_signature ?? ''));
$statusMessage = (string) session('status', '');

$startEmployment = '-';
if ($employmentDuration !== '' && $examDateRaw !== '') {
    try {
        $examDate = Carbon::parse($examDateRaw);
        if (preg_match('/(\d+)\s*year/i', $employmentDuration, $match) === 1) {
            $startEmployment = $examDate->copy()->subYears((int) $match[1])->format('d/m/Y');
        } elseif (preg_match('/(\d+)\s*month/i', $employmentDuration, $match) === 1) {
            $startEmployment = $examDate->copy()->subMonths((int) $match[1])->format('d/m/Y');
        }
    } catch (\Throwable) {
        $startEmployment = '-';
    }
}

$mrpMonths = '-';
if (! empty($recommendation->MRPdate_start) && ! empty($recommendation->MRPdate_end)) {
    try {
        $mrpMonthsValue = Carbon::parse((string) $recommendation->MRPdate_start)
            ->diffInMonths(Carbon::parse((string) $recommendation->MRPdate_end));
        $mrpMonths = $mrpMonthsValue > 0 ? (string) $mrpMonthsValue : '0';
    } catch (\Throwable) {
        $mrpMonths = '-';
    }
}

$targetOrganAbnormal = false;
$targetOrganNotes = [];
foreach ([
    'blood_count' => 'Blood count',
    'renal_function' => 'Renal function',
    'liver_function' => 'Liver function',
    'chest_xray' => 'Chest X-ray',
] as $field => $label) {
    if (($targetOrgan->{$field} ?? null) === 'Abnormal') {
        $targetOrganAbnormal = true;
        $targetOrganNotes[] = $label;
    }
}
if (! empty($targetOrgan->spirometry_comments) || (! empty($targetOrgan->spirometry_FEV1) || ! empty($targetOrgan->spirometry_FVC) || ! empty($targetOrgan->spirometry_FEV_FVC))) {
    $targetOrganNotes[] = 'Spirometry';
}

$recommendationReasons = [];
if (($findings->pregnancy_breastFeding ?? null) === 'Yes') {
    $recommendationReasons[] = ['label' => 'Pregnancy / Breastfeeding concern', 'detail' => 'Recorded during MS findings.'];
}
if (($findings->biological_monitoring ?? null) === 'Yes') {
    $recommendationReasons[] = ['label' => 'Abnormal BM/BEM result', 'detail' => $showValue($biological->baseline_annual ?? $biological->baseline_results ?? null)];
}
if (($findings->clinical_findings ?? null) === 'Yes' || ($clinicalFindings->result_clinical_findings ?? null) === 'Yes') {
    $recommendationReasons[] = ['label' => 'Adverse health effects based on clinical findings', 'detail' => $showValue($clinicalFindings->elaboration ?? null, 'Recorded in examination findings')];
}
if (($findings->target_organ ?? null) === 'Yes' || $targetOrganAbnormal) {
    $recommendationReasons[] = ['label' => 'Target organ function test abnormality', 'detail' => $targetOrganNotes !== [] ? implode(', ', $targetOrganNotes) : 'Recorded in target organ assessment'];
}
$otherReason = trim((string) ($recommendation->notes ?? $removal->reasons_recommendations ?? ''));
if ($otherReason !== '') {
    $recommendationReasons[] = ['label' => 'Other follow-up note', 'detail' => $otherReason];
}

$removalType = trim((string) ($removal->removal_type ?? ''));
$screenRemovalType = old('removal_type', $removalType);

$certificationSentence = sprintf(
    'I certify that the above named person examined by me on %s should not continue to work as %s in %s%s for %s months, subject to a review on %s.',
    $showValue($formatDate($examDateRaw)),
    $showValue($jobTitle, 'the current assigned role'),
    $showValue($company->company_name ?? null, 'the workplace'),
    $workUnit !== '' ? ' (' . $workUnit . ')' : '',
    $showValue($mrpMonths),
    $showValue($formatDate($reviewDateRaw))
);
$alternativeSentence = sprintf(
    'In the meantime, the worker should be given alternative work in another department or section which does not expose the worker to %s.',
    $showValue($chemicalName, 'the identified chemical hazard')
);
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
@page{size:A4 portrait;margin:8mm}
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
.toggle-form{display:grid;gap:12px}
.toggle-label{font-size:.82rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#6b7d71}
.toggle-row{display:flex;gap:24px;flex-wrap:wrap}
.toggle-chip{display:inline-flex;align-items:center;gap:10px;padding:0;border:0;background:transparent;font-weight:600}
.toggle-chip input{margin:0}
.toggle-chip.active{color:#0f172a}
.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 24px}
.detail-line{display:grid;grid-template-columns:170px 1fr;gap:12px;align-items:start;padding:6px 0}
.detail-line.full{grid-column:1 / -1}
.detail-label{font-size:.82rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#6b7d71}
.detail-value{font-size:1rem;font-weight:500}
.narrative{line-height:1.8}
.narrative strong{font-weight:700}
.reason-table{width:100%;border-collapse:collapse}
.reason-table td{padding:8px 0;border-top:1px solid #edf2ee;vertical-align:top;font-size:1rem}
.reason-table tr:first-child td{border-top:0}
.reason-status{width:120px;font-weight:700;color:#1f5f35}
.reason-status.no{color:#64756b}
.signature-grid{display:grid;grid-template-columns:1.1fr 1fr;gap:22px;align-items:start}
.sign-box{padding:8px 0 0;min-height:150px;text-align:center}
.sign-box img{display:block;max-width:220px;max-height:72px;object-fit:contain;margin:0 auto 12px}
.doctor-meta{display:grid;gap:10px}
.meta-row{padding:6px 0}
.meta-row strong{display:block;font-size:.8rem;letter-spacing:.04em;text-transform:uppercase;color:#6b7d71;margin-bottom:4px}
.muted{color:#64756b}
.flash{margin:0 0 8px;padding:10px 14px;border:1px solid #cfe7d4;border-radius:12px;background:#f3fbf4;color:#1f5f35;font-size:.9rem}
.save-actions{display:flex;justify-content:flex-end}
.save-btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 18px;border:1px solid #2f9e44;border-radius:999px;background:#2f9e44;color:#fff;font:inherit;font-weight:700;cursor:pointer}
.screen-only{display:block}
@media print{
body{padding:0;font-size:12px}
.sheet{gap:8px}
.clinic-report-header{padding-bottom:4px}
.report-head{padding:2px 0 8px}
.report-code{font-size:12px}
.report-head-act{font-size:12px}
.report-head-regulation{font-size:13px}
.report-title{margin-top:8px;font-size:16px}
.section{padding:8px 0}
.detail-grid{gap:6px 16px}
.detail-line{grid-template-columns:160px 1fr;gap:8px;padding:2px 0}
.detail-label{font-size:.72rem}
.detail-value{font-size:.86rem}
.narrative{font-size:.86rem;line-height:1.45}
.reason-table td{padding:3px 0;font-size:.84rem}
.signature-grid{gap:14px}
.sign-box{min-height:96px}
.sign-box img{max-width:165px;max-height:48px;margin-bottom:6px}
.meta-row{padding:2px 0}
.meta-row strong{font-size:.72rem;margin-bottom:2px}
.report-card{break-inside:avoid}
.screen-only{display:none!important}
}
</style>
<div class="sheet">
    <?php require __DIR__ . '/partials/clinic_header.php'; ?>

    <section class="report-card">
        <div class="report-head">
            <div class="report-head-top">
                <div class="report-code">USECHH 5i</div>
                <div class="report-head-act">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="report-head-regulation">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                <h1 class="report-title">Medical Removal Protection</h1>
            </div>
        </div>

        <?php if ($statusMessage !== ''): ?>
            <div class="flash screen-only"><?php echo $esc($statusMessage); ?></div>
        <?php endif; ?>

        <div class="section screen-only">
            <form class="toggle-form" method="post" action="<?php echo $esc(route('surveillance.report.removal.save')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="declaration_id" value="<?php echo $esc((string) ($declaration->declaration_id ?? $declarationId)); ?>">
                <input type="hidden" name="employee_id" value="<?php echo $esc((string) $employeeId); ?>">
                <input type="hidden" name="company_id" value="<?php echo $esc((string) $companyId); ?>">
                <input type="hidden" name="surveillance_id" value="<?php echo $esc((string) $surveillanceId); ?>">
                <div class="toggle-label">Removal Type</div>
                <div class="toggle-row">
                    <label class="toggle-chip<?php echo $screenRemovalType === 'Temporary' ? ' active' : ''; ?>">
                        <input type="radio" name="removal_type" value="Temporary" <?php echo $screenRemovalType === 'Temporary' ? 'checked' : ''; ?>>
                        <span>Temporary</span>
                    </label>
                    <label class="toggle-chip<?php echo $screenRemovalType === 'Permanent' ? ' active' : ''; ?>">
                        <input type="radio" name="removal_type" value="Permanent" <?php echo $screenRemovalType === 'Permanent' ? 'checked' : ''; ?>>
                        <span>Permanent</span>
                    </label>
                </div>
                <div class="save-actions">
                    <button class="save-btn" type="submit">Save Removal Type</button>
                </div>
            </form>
        </div>

        <div class="section">
            <div class="detail-grid">
                <div class="detail-line"><div class="detail-label">Removal Type</div><div class="detail-value"><?php echo $esc($showValue($removalType, 'Not selected')); ?></div></div>
                <div class="detail-line"><div class="detail-label">Examination Date</div><div class="detail-value"><?php echo $esc($formatDate($examDateRaw)); ?></div></div>
                <div class="detail-line"><div class="detail-label">Name of Worker</div><div class="detail-value"><?php echo $esc($showValue($workerName)); ?></div></div>
                <div class="detail-line"><div class="detail-label">NRIC / Passport No.</div><div class="detail-value"><?php echo $esc($showValue($identityNo)); ?></div></div>
                <div class="detail-line"><div class="detail-label">Date of Birth</div><div class="detail-value"><?php echo $esc($formatDate((string) ($employee->employee_DOB ?? ''))); ?></div></div>
                <div class="detail-line"><div class="detail-label">Sex</div><div class="detail-value"><?php echo $esc($showValue($employee->employee_gender ?? null)); ?></div></div>
                <div class="detail-line full"><div class="detail-label">Name and Address of Workplace</div><div class="detail-value"><?php echo $esc($showValue($company->company_name ?? null) . ($companyAddress !== '' ? ', ' . $companyAddress : '')); ?></div></div>
                <div class="detail-line"><div class="detail-label">Date of Starting Employment</div><div class="detail-value"><?php echo $esc($startEmployment); ?></div></div>
                <div class="detail-line"><div class="detail-label">Duration of Employment</div><div class="detail-value"><?php echo $esc($showValue($employmentDuration, 'Not recorded')); ?></div></div>
                <div class="detail-line full"><div class="detail-label">Health Hazard Present</div><div class="detail-value"><?php echo $esc($showValue($chemicalName, 'Not recorded')); ?></div></div>
            </div>
        </div>

        <div class="section">
            <div class="narrative"><?php echo $esc($certificationSentence); ?></div>
            <div class="narrative" style="margin-top:12px;"><?php echo $esc($alternativeSentence); ?></div>
        </div>

        <div class="section">
            <div class="detail-line full">
                <div class="detail-label">Reasons for Recommendation</div>
                <div class="detail-value" style="width:100%;">
                    <table class="reason-table">
                        <tbody>
                            <?php foreach ($recommendationReasons as $reason): ?>
                                <tr>
                                    <td><?php echo $esc($reason['label']); ?></td>
                                    <td class="reason-status">Yes</td>
                                    <td><?php echo $esc($reason['detail']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($recommendationReasons === []): ?>
                                <tr>
                                    <td>No abnormal recommendation trigger recorded</td>
                                    <td class="reason-status no">No</td>
                                    <td>Review the examination record if a manual follow-up note is required.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="signature-grid">
                <div class="doctor-meta">
                    <div class="meta-row"><strong>Name of OHD</strong><?php echo $esc($doctorName); ?></div>
                    <div class="meta-row"><strong>Address of Practice</strong><?php echo $esc($showValue($doctorAddress, '-')); ?></div>
                    <div class="meta-row"><strong>Email Address</strong><?php echo $esc($showValue($doctor->doctor_email ?? null)); ?></div>
                    <div class="meta-row"><strong>H/P / Tel / Fax</strong><?php echo $esc(trim((string) (($doctor->doctor_telephone ?? '-') . ' / ' . ($doctor->doctor_fax ?? '-')))); ?></div>
                </div>
                <div class="sign-box">
                    <?php if ($doctorSignature !== ''): ?>
                        <img src="<?php echo $esc($doctorSignature); ?>" alt="Doctor signature">
                    <?php endif; ?>
                    <div><strong>OHD Signature</strong></div>
                    <div class="muted"><?php echo $esc($formatDate((string) ($declaration->doctor_date ?? $examDateRaw))); ?></div>
                    <div class="muted" style="margin-top:10px;"><?php echo $esc($doctorName); ?></div>
                </div>
            </div>
        </div>
    </section>
</div>
</body>
</html>
