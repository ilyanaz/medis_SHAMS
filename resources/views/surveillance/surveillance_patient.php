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
<style>.flow{height:calc(100dvh - 204px);min-height:calc(100dvh - 204px);display:flex}.content{padding:4px 6px;height:100%;width:100%;margin-top:0;border:0;background:transparent;border-radius:0;display:flex;flex-direction:column;overflow:hidden}.head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.head h2{margin:0 0 12px;font-size:1.8rem}.top-actions{display:flex;gap:10px;flex-wrap:wrap}.btn,.next,.page-btn{display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;background:#fff;color:#374151}.next{background:#389B5B;border-color:#389B5B;color:#fff}.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px}.toolbar input{border:1px solid #d1d5db;border-radius:12px;padding:10px 12px;min-width:280px}.selected-note{margin-top:10px;color:#4b5563;font-size:.92rem}.table-wrap{margin-top:14px;flex:1;min-height:0;display:flex;align-items:flex-start}.table{width:100%;border-collapse:collapse}.table th,.table td{padding:14px 10px;text-align:left;border-top:0}.table thead tr{border-top:1px solid #edf0f2;border-bottom:1px solid #edf0f2}.table th{font-size:.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;background:#fff}.table-name-link{color:#0f172a;text-decoration:none;font-weight:600}.table-name-link:hover{color:#389B5B;text-decoration:underline}.filler-row td{height:56px;color:transparent;user-select:none}.empty{padding:22px 10px;color:#6b7280;text-align:center}.action-icons{display:flex;gap:10px}.icon-btn svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.8}.icon-btn{color:#111827}.icon-btn.delete{color:#ef4444}.bottom{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:auto;padding-top:18px}.pager{color:#6b7280;font-size:.84rem}.pager-group{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.page-btn{cursor:pointer;padding:8px 12px}.page-btn[disabled]{opacity:.45;cursor:not-allowed}.page-btn.is-active{background:#389B5B;border-color:#389B5B;color:#fff}.page-numbers{display:flex;gap:8px;flex-wrap:wrap}@media(max-width:1180px){.flow{height:auto;min-height:auto}.content{height:auto;min-height:auto}}@media(max-width:760px){.content{padding:0}.toolbar input{min-width:100%}.bottom{align-items:flex-start;flex-direction:column}}</style>
<div class="flow"><section class="content"><div class="head"><div><h2>Patient List</h2><?php if ($selectedCompany): ?><div class="selected-note">Current company: <strong><?php echo $esc($selectedCompany->company_name); ?></strong></div><?php endif; ?></div><div class="top-actions"><a class="next" href="<?php echo $esc($newPatientUrl); ?>">+ Add Patient</a></div></div><div class="toolbar"><input id="patientSearch" type="text" placeholder="Search patient"></div><div class="table-wrap"><table class="table"><thead><tr><th>Patient Name</th><th>NRIC/Passport No</th><th>Telephone No</th><th>Email</th><th>Action</th></tr></thead><tbody id="patientTableBody"><?php if($totalEmployees > 0): ?><?php foreach($employees as $employee): ?><?php $employeeName = trim(((string) $employee->employee_firstName) . ' ' . ((string) $employee->employee_lastName)); $identity = $employee->employee_NRIC ?: ($employee->employee_passportNo ?: '-'); $telephone = $employee->employee_telephone ?: '-'; $email = $employee->employee_email ?: '-'; $patientViewUrl = function_exists('route') ? route('surveillance.patient.view', array_filter(['employee' => $employee->employee_id, 'company_id' => $selectedCompany->company_id ?? '', 'return_to' => $listReturnUrl])) : '#'; $patientEditUrl = function_exists('route') ? route('surveillance.patient.edit', array_filter(['employee' => $employee->employee_id, 'company_id' => $selectedCompany->company_id ?? '', 'return_to' => $listReturnUrl])) : '#'; $patientDeleteUrl = function_exists('route') ? route('surveillance.patient.delete', array_filter(['employee' => $employee->employee_id, 'company_id' => $selectedCompany->company_id ?? '', 'return_to' => $listReturnUrl])) : '#'; ?><tr data-patient-row="1"><td><a class="table-name-link" href="<?php echo $esc(route('surveillance.list', ['employee_id' => $employee->employee_id, 'company_id' => $selectedCompany->company_id ?? ''])); ?>"><?php echo $esc($employeeName !== '' ? $employeeName : 'Not set'); ?></a></td><td><?php echo $esc($identity); ?></td><td><?php echo $esc($telephone); ?></td><td><?php echo $esc($email); ?></td><td><div class="action-icons"><a class="icon-btn" href="<?php echo $esc($patientViewUrl); ?>" title="View Patient"><svg viewBox="0 0 24 24"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"></path><circle cx="12" cy="12" r="3"></circle></svg></a><a class="icon-btn" href="<?php echo $esc($patientEditUrl); ?>" title="Edit Patient"><svg viewBox="0 0 24 24"><path d="M4 20h4l10-10-4-4L4 16v4z"></path><path d="M13 7l4 4"></path></svg></a><a class="icon-btn delete" href="<?php echo $esc($patientDeleteUrl); ?>" title="Delete Patient"><svg viewBox="0 0 24 24"><path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 13h10l1-13"></path><path d="M9 7V4h6v3"></path></svg></a></div></td></tr><?php endforeach; ?><?php else: ?><tr id="patientEmptyRow"><td class="empty" colspan="5">No patient records found for the selected company.</td></tr><?php endif; ?></tbody></table></div><div class="bottom"><span class="pager" id="patientPager"><?php echo $totalEmployees > 0 ? 'Showing 1-' . number_format($totalEmployees) . ' of ' . number_format($totalEmployees) . ' records' : 'Showing 0 of 0 records'; ?></span><div class="pager-group"><?php if($totalEmployees > 0): ?><button class="page-btn" id="patientPrevBtn" type="button">Previous</button><div class="page-numbers" id="patientPageNumbers"></div><button class="page-btn" id="patientNextBtn" type="button">Next</button><?php endif; ?><a class="btn" href="<?php echo $esc($backUrl); ?>">Back</a></div></div></section></div><script>(function(){const search=document.getElementById('patientSearch');const body=document.getElementById('patientTableBody');const rows=Array.prototype.slice.call(document.querySelectorAll('tr[data-patient-row]'));const emptyRow=document.getElementById('patientEmptyRow');const pager=document.getElementById('patientPager');const prevBtn=document.getElementById('patientPrevBtn');const nextBtn=document.getElementById('patientNextBtn');const pageNumbers=document.getElementById('patientPageNumbers');if(!body||!pager||!rows.length){return;}const perPage=10;let currentPage=1;const fillerClass='filler-row';const clearFillers=function(){Array.prototype.slice.call(body.querySelectorAll('.'+fillerClass)).forEach(function(row){row.remove();});};const appendFillers=function(count){for(let i=0;i<count;i+=1){const filler=document.createElement('tr');filler.className=fillerClass;for(let col=0;col<5;col+=1){const cell=document.createElement('td');cell.innerHTML='&nbsp;';filler.appendChild(cell);}body.appendChild(filler);}};const getFilteredRows=function(){const term=(search&&search.value?search.value:'').trim().toLowerCase();if(!term){return rows;}return rows.filter(function(row){return (row.textContent||'').toLowerCase().indexOf(term)!==-1;});};const render=function(){clearFillers();const filtered=getFilteredRows();const total=filtered.length;const totalPages=Math.max(1,Math.ceil(total/perPage));if(currentPage>totalPages){currentPage=totalPages;}if(currentPage<1){currentPage=1;}rows.forEach(function(row){row.style.display='none';});const start=(currentPage-1)*perPage;const end=Math.min(start+perPage,total);const visibleRows=filtered.slice(start,end);visibleRows.forEach(function(row){row.style.display='';});if(emptyRow){emptyRow.style.display=total?'none':'';}if(total>0&&visibleRows.length<perPage){appendFillers(perPage-visibleRows.length);}pager.textContent=total?('Showing '+(start+1)+'-'+end+' of '+total+' records'):'Showing 0 of 0 records';if(prevBtn){prevBtn.disabled=currentPage===1||total===0;}if(nextBtn){nextBtn.disabled=currentPage===totalPages||total===0;}if(pageNumbers){pageNumbers.innerHTML='';for(let page=1;page<=totalPages;page++){const button=document.createElement('button');button.type='button';button.className='page-btn'+(page===currentPage?' is-active':'');button.textContent=String(page);button.addEventListener('click',function(){currentPage=page;render();});pageNumbers.appendChild(button);}}};if(search){search.addEventListener('input',function(){currentPage=1;render();});}if(prevBtn){prevBtn.addEventListener('click',function(){if(currentPage>1){currentPage-=1;render();}});}if(nextBtn){nextBtn.addEventListener('click',function(){if(currentPage<Math.max(1,Math.ceil(getFilteredRows().length/perPage))){currentPage+=1;render();}});}render();}());</script><?php medis_render_navigation_end(); ?></body></html>
