<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Surveillance - Patient</title></head><body>
<?php
require dirname(__DIR__) . '/panel/navigation.php';
$pdfMode = !empty($pdfMode);
$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$backUrl = function_exists('route') ? route('surveillance.company') : '#';
$request = request();
$activeClinicId = (int) $request->session()->get('active_clinic_id', 0);
$selectedCompanyId = (int) $request->query('company_id', 0);
$selectedCompany = null;

if ($selectedCompanyId > 0 && \Illuminate\Support\Facades\Schema::hasTable('company')) {
    $selectedCompanyQuery = \Illuminate\Support\Facades\DB::table('company')->where('company_id', $selectedCompanyId);
    if ($activeClinicId > 0 && \Illuminate\Support\Facades\Schema::hasColumn('company', 'clinic_id')) {
        $selectedCompanyQuery->where('clinic_id', $activeClinicId);
    }
    if (\Illuminate\Support\Facades\Schema::hasColumn('company', 'company_module')) {
        $selectedCompanyQuery->whereRaw('LOWER(company_module) = ?', ['surveillance']);
    }
    $selectedCompany = $selectedCompanyQuery->first(['company_id','company_name','company_module']);
}

$newPatientUrl = function_exists('route') ? route('surveillance.patient.new', array_filter(['company_id' => $selectedCompanyId])) : '#';
$listReturnUrl = function_exists('route') ? route('surveillance.patient', array_filter(['company_id' => $selectedCompanyId])) : '#';
$employees = collect();

if (\Illuminate\Support\Facades\Schema::hasTable('employee')) {
    $employeeQuery = \Illuminate\Support\Facades\DB::table('employee')
        ->select(['employee.employee_id','employee.employee_firstName','employee.employee_lastName','employee.employee_NRIC','employee.employee_passportNo','employee.employee_telephone','employee.employee_email'])
        ->orderBy('employee.employee_firstName')
        ->orderBy('employee.employee_lastName')
        ->orderBy('employee.employee_id');

    if ($activeClinicId > 0 && \Illuminate\Support\Facades\Schema::hasColumn('employee', 'clinic_id')) {
        $employeeQuery->where('employee.clinic_id', $activeClinicId);
    }

    if ($selectedCompany) {
        if (\Illuminate\Support\Facades\Schema::hasColumn('employee', 'company_id')) {
            $employeeQuery->where(function ($scoped) use ($selectedCompany): void {
                $scoped->where('employee.company_id', $selectedCompany->company_id);
                if (\Illuminate\Support\Facades\Schema::hasTable('occupational_history')) {
                    $scoped->orWhereExists(function ($subQuery) use ($selectedCompany): void {
                        $subQuery->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('occupational_history')
                            ->whereColumn('occupational_history.employee_id', 'employee.employee_id')
                            ->where('occupational_history.company_name', (string) $selectedCompany->company_name);
                    });
                }
            });
        } elseif (\Illuminate\Support\Facades\Schema::hasTable('occupational_history')) {
            $employeeQuery->whereExists(function ($subQuery) use ($selectedCompany): void {
                $subQuery->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('occupational_history')
                    ->whereColumn('occupational_history.employee_id', 'employee.employee_id')
                    ->where('occupational_history.company_name', (string) $selectedCompany->company_name);
            });
        } else {
            $employeeQuery->whereRaw('1 = 0');
        }
    } elseif ($selectedCompanyId > 0) {
        $employeeQuery->whereRaw('1 = 0');
    }

    $employees = $employeeQuery->get();
}

$totalEmployees = $employees->count();
medis_render_navigation_start(['clinicName'=>$clinicName ?? 'Medis SHAMS','clinicLogoUrl'=>$clinicLogoUrl ?? null,'username'=>$username ?? 'User','active' => 'surveillance','showSurveillanceSubnav' => true,'surveillanceSubActive' => 'patient','pdfMode' => $pdfMode,]);
?>
<style>.flow{min-height:calc(100dvh - 204px)}.content{padding:4px 6px;overflow:auto;min-height:clamp(500px,calc(100dvh - 314px),780px);margin-top:0;border:0;background:transparent;border-radius:0;display:flex;flex-direction:column}.head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.head h2{margin:0 0 12px;font-size:1.8rem}.top-actions{display:flex;gap:10px;flex-wrap:wrap}.btn,.next{display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;background:#fff;color:#374151}.next{background:#389B5B;border-color:#389B5B;color:#fff}.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px}.toolbar input{border:1px solid #d1d5db;border-radius:12px;padding:10px 12px;min-width:280px}.selected-note{margin-top:10px;color:#4b5563;font-size:.92rem}.table{width:100%;border-collapse:collapse;margin-top:14px}.table th,.table td{padding:14px 10px;text-align:left;border-top:1px solid #edf0f2}.table th{font-size:.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em}.table-name-link{color:#0f172a;text-decoration:none;font-weight:600}.table-name-link:hover{color:#389B5B;text-decoration:underline}.empty{padding:22px 10px;color:#6b7280;text-align:center}.action-icons{display:flex;gap:10px}.icon-btn svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.8}.icon-btn{color:#111827}.icon-btn.delete{color:#ef4444}.bottom{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:auto;padding-top:18px}.pager{color:#6b7280;font-size:.84rem}@media(max-width:1180px){.flow{min-height:auto}.content{min-height:auto}}@media(max-width:760px){.content{padding:0}.toolbar input{min-width:100%}}</style>
<div class="flow"><section class="content"><div class="head"><div><h2>Patient List</h2><?php if ($selectedCompany): ?><div class="selected-note">Current company: <strong><?php echo $esc($selectedCompany->company_name); ?></strong></div><?php endif; ?></div><div class="top-actions"><a class="next" href="<?php echo $esc($newPatientUrl); ?>">+ Add Patient</a></div></div><div class="toolbar"><input type="text" placeholder="Search patient"></div><table class="table"><thead><tr><th>Patient Name</th><th>NRIC/Passport No</th><th>Telephone No</th><th>Email</th><th>Action</th></tr></thead><tbody><?php if($totalEmployees > 0): ?><?php foreach($employees as $employee): ?><?php $employeeName = trim(((string) $employee->employee_firstName) . ' ' . ((string) $employee->employee_lastName)); $identity = $employee->employee_NRIC ?: ($employee->employee_passportNo ?: '-'); $telephone = $employee->employee_telephone ?: '-'; $email = $employee->employee_email ?: '-'; $patientViewUrl = function_exists('route') ? route('surveillance.patient.view', array_filter(['employee' => $employee->employee_id, 'company_id' => $selectedCompany->company_id ?? '', 'return_to' => $listReturnUrl])) : '#'; $patientEditUrl = function_exists('route') ? route('surveillance.patient.edit', array_filter(['employee' => $employee->employee_id, 'company_id' => $selectedCompany->company_id ?? '', 'return_to' => $listReturnUrl])) : '#'; $patientDeleteUrl = function_exists('route') ? route('surveillance.patient.delete', array_filter(['employee' => $employee->employee_id, 'company_id' => $selectedCompany->company_id ?? '', 'return_to' => $listReturnUrl])) : '#'; ?><tr><td><a class="table-name-link" href="<?php echo $esc(route('surveillance.list', ['employee_id' => $employee->employee_id, 'company_id' => $selectedCompany->company_id ?? ''])); ?>"><?php echo $esc($employeeName !== '' ? $employeeName : 'Not set'); ?></a></td><td><?php echo $esc($identity); ?></td><td><?php echo $esc($telephone); ?></td><td><?php echo $esc($email); ?></td><td><div class="action-icons"><a class="icon-btn" href="<?php echo $esc($patientViewUrl); ?>" title="View Patient"><svg viewBox="0 0 24 24"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"></path><circle cx="12" cy="12" r="3"></circle></svg></a><a class="icon-btn" href="<?php echo $esc($patientEditUrl); ?>" title="Edit Patient"><svg viewBox="0 0 24 24"><path d="M4 20h4l10-10-4-4L4 16v4z"></path><path d="M13 7l4 4"></path></svg></a><a class="icon-btn delete" href="<?php echo $esc($patientDeleteUrl); ?>" title="Delete Patient"><svg viewBox="0 0 24 24"><path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 13h10l1-13"></path><path d="M9 7V4h6v3"></path></svg></a></div></td></tr><?php endforeach; ?><?php else: ?><tr><td class="empty" colspan="5">No patient records found for the selected company.</td></tr><?php endif; ?></tbody></table><div class="bottom"><span class="pager"><?php echo $totalEmployees > 0 ? 'Showing 1-' . number_format($totalEmployees) . ' of ' . number_format($totalEmployees) . ' records' : 'Showing 0 of 0 records'; ?></span><div><a class="btn" href="<?php echo $esc($backUrl); ?>">Back</a></div></div></section></div><?php medis_render_navigation_end(); ?></body></html>
