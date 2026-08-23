<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Support\IsolatedDoctorProvisioner;
use App\Support\SystemSampleDataBuilder;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('medis:seed-samples {--records=4 : Total surveillance and audiometry records to prepare}', function () {
    $records = max(4, (int) $this->option('records'));

    $summary = app(SystemSampleDataBuilder::class)->seed($records);

    $this->info("Prepared {$summary['surveillance_records']} surveillance records and {$summary['audiometry_records']} audiometry records.");
    $this->line("Doctor login kept for testing: {$summary['doctor_username']}");

    foreach ($summary['clinics'] as $clinic) {
        $this->newLine();
        $this->line("Clinic: {$clinic['name']}");
        $this->line("Company: {$clinic['company']}");
        foreach ($clinic['employees'] as $employeeName) {
            $this->line(" - {$employeeName}");
        }
    }

    foreach ($summary['warnings'] as $warning) {
        $this->warn($warning);
    }
})->purpose('Create repeatable clinic-scoped sample surveillance and audiometry data for QA and PDF testing');

Artisan::command('medis:seed-surveillance-employee {employee_id : Employee ID to prepare surveillance sample data for}', function () {
    $employeeId = (int) $this->argument('employee_id');

    $summary = app(SystemSampleDataBuilder::class)->seedSurveillanceForEmployee($employeeId);

    $this->info("Prepared surveillance sample data for employee {$summary['employee_id']}.");
    $this->line("Company: {$summary['company_name']} (#{$summary['company_id']})");
    $this->line('Doctor login kept for testing: ' . $summary['doctor_username']);
    $this->line('Surveillance ID: ' . ($summary['surveillance_id'] ?? 'n/a'));
    $this->line('Declaration ID: ' . ($summary['declaration_id'] ?? 'n/a'));
})->purpose('Create or refresh a complete sample surveillance record set for one employee');

Artisan::command('medis:seed-audiometry-employee {employee_id : Employee ID to prepare audiometry sample data for}', function () {
    $employeeId = (int) $this->argument('employee_id');

    $summary = app(SystemSampleDataBuilder::class)->seedAudiometryForEmployee($employeeId);

    $this->info("Prepared audiometry sample data for employee {$summary['employee_id']}.");
    $this->line("Company: {$summary['company_name']} (#{$summary['company_id']})");
    $this->line('Doctor login kept for testing: ' . $summary['doctor_username']);
    $this->line('Audiometry ID: ' . ($summary['audiometry_id'] ?? 'n/a'));
})->purpose('Create or refresh a complete sample audiometry questionnaire and examination record set for one employee');

Artisan::command('medis:provision-doctor {username : Login username for the doctor test account} {password : Login password for the doctor test account} {--email= : Optional login email} {--clinic= : Optional clinic name} {--company= : Optional company name}', function () {
    $summary = app(IsolatedDoctorProvisioner::class)->provision(
        (string) $this->argument('username'),
        (string) $this->argument('password'),
        array_filter([
            'email' => $this->option('email'),
            'clinic_name' => $this->option('clinic'),
            'company_name' => $this->option('company'),
        ], static fn ($value) => is_string($value) && trim($value) !== '')
    );

    $this->info('Doctor test account is ready.');
    $this->line("Username: {$summary['username']}");
    $this->line("Email: {$summary['email']}");
    $this->line("Clinic: {$summary['clinic_name']} (#{$summary['clinic_id']})");
    if ($summary['company_id'] !== null) {
        $this->line("Company: {$summary['company_name']} (#{$summary['company_id']})");
    }
})->purpose('Create or refresh an isolated doctor login with its own clinic and company scope');
