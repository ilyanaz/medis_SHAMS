<?php

namespace App\Http\Controllers;

use App\Mail\TestNotificationMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SystemController extends Controller
{
    public function dashboard(): View|RedirectResponse
    {
        if (! session()->has('developer_user_id')) {
            return redirect()->route('developer.login');
        }

        $summary = [
            'companies' => DB::table('company')->count(),
            'employees' => DB::table('employee')->count(),
            'doctors' => DB::table('doctor')->count(),
            'surveillance' => DB::table('chemical_information')->count(),
        ];

        $recentEmployees = DB::table('employee')
            ->select('employee_id', 'employee_firstName', 'employee_lastName', 'employee_email', 'employee_telephone')
            ->orderByDesc('employee_id')
            ->limit(5)
            ->get();

        $recentCompanies = DB::table('company')
            ->select('company_id', 'company_name', 'company_state', 'company_email', 'total_workers')
            ->orderByDesc('company_id')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'pageTitle' => 'Dashboard',
            'summary' => $summary,
            'recentEmployees' => $recentEmployees,
            'recentCompanies' => $recentCompanies,
        ]);
    }

    public function companies(): View|RedirectResponse
    {
        if (! session()->has('developer_user_id')) {
            return redirect()->route('developer.login');
        }

        $companies = DB::table('company')
            ->select('company_id', 'company_name', 'company_state', 'company_email', 'company_telephone', 'total_workers')
            ->orderBy('company_name')
            ->get();

        return view('companies.index', [
            'pageTitle' => 'Companies',
            'companies' => $companies,
        ]);
    }

    public function employees(): View|RedirectResponse
    {
        if (! session()->has('developer_user_id')) {
            return redirect()->route('developer.login');
        }

        $employees = DB::table('employee')
            ->select(
                'employee_id',
                'employee_firstName',
                'employee_lastName',
                'employee_gender',
                'employee_email',
                'employee_telephone',
                'employee_state'
            )
            ->orderBy('employee_firstName')
            ->orderBy('employee_lastName')
            ->get();

        return view('employees.index', [
            'pageTitle' => 'Employees',
            'employees' => $employees,
        ]);
    }

    public function doctors(): View|RedirectResponse
    {
        if (! session()->has('developer_user_id')) {
            return redirect()->route('developer.login');
        }

        $doctors = DB::table('doctor')
            ->select('doctor_id', 'doctor_firstName', 'doctor_lastName', 'doctor_email', 'doctor_telephone', 'doctor_username')
            ->orderBy('doctor_firstName')
            ->orderBy('doctor_lastName')
            ->get();

        return view('doctors.index', [
            'pageTitle' => 'Doctors',
            'doctors' => $doctors,
        ]);
    }

    public function emailSetup(): View|RedirectResponse
    {
        if (! session()->has('developer_user_id')) {
            return redirect()->route('developer.login');
        }

        $mailConfig = [
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'scheme' => config('mail.mailers.smtp.scheme') ?: 'tls',
            'username' => config('mail.mailers.smtp.username'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];

        $providerPresets = [
            [
                'name' => 'Gmail',
                'host' => 'smtp.gmail.com',
                'port' => '587',
                'scheme' => 'tls',
                'note' => 'Use your full Gmail address and a Google App Password after enabling 2-Step Verification.',
            ],
            [
                'name' => 'Microsoft / Outlook / Hotmail',
                'host' => 'smtp.office365.com',
                'port' => '587',
                'scheme' => 'tls',
                'note' => 'Use the full Outlook or Microsoft 365 email address. Some accounts need SMTP AUTH or an app password.',
            ],
            [
                'name' => 'Yahoo Mail',
                'host' => 'smtp.mail.yahoo.com',
                'port' => '587',
                'scheme' => 'tls',
                'note' => 'Use your full Yahoo address and generate a Yahoo app password for the system.',
            ],
        ];

        return view('email.setup', [
            'pageTitle' => 'Email Setup',
            'mailConfig' => $mailConfig,
            'providerPresets' => $providerPresets,
        ]);
    }

    public function sendTestEmail(Request $request): RedirectResponse
    {
        if (! session()->has('developer_user_id')) {
            return redirect()->route('developer.login');
        }

        $validated = $request->validate([
            'recipient_email' => ['required', 'email'],
            'provider_name' => ['nullable', 'string', 'max:100'],
        ]);

        Mail::to($validated['recipient_email'])->send(
            new TestNotificationMail(
                recipientEmail: $validated['recipient_email'],
                providerName: $validated['provider_name'] ?: 'Custom SMTP',
                appName: config('app.name', 'Medis SHAMS'),
            )
        );

        return back()->with('status', 'Test email sent to '.$validated['recipient_email'].'. If it does not arrive, check spam and verify your SMTP credentials.');
    }
}
