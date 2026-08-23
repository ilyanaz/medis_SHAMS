<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class IsolatedDoctorProvisioner
{
    /**
     * @return array{user_id:int,doctor_id:int,clinic_id:int,company_id:int|null,username:string,password:string,email:string,clinic_name:string,company_name:string|null}
     */
    public function provision(string $username, string $password, array $options = []): array
    {
        foreach (['users', 'doctor', 'clinic'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required table [{$table}] is missing.");
            }
        }

        if (! Schema::hasColumn('clinic', 'doctor_id')) {
            throw new RuntimeException('Column [clinic.doctor_id] is missing. Run the latest migration first.');
        }

        $normalizedUsername = trim(strtolower($username));
        if ($normalizedUsername === '') {
            throw new RuntimeException('Username cannot be empty.');
        }

        $email = trim((string) ($options['email'] ?? ($normalizedUsername . '@medisshams.test')));
        $clinicName = trim((string) ($options['clinic_name'] ?? (ucfirst($normalizedUsername) . ' Test Clinic')));
        $companyName = trim((string) ($options['company_name'] ?? (ucfirst($normalizedUsername) . ' Test Company')));

        return DB::transaction(function () use ($normalizedUsername, $password, $email, $clinicName, $companyName): array {
            $userId = $this->upsertUser($normalizedUsername, $password, $email);
            $doctorId = $this->upsertDoctor($normalizedUsername, $password, $email);
            $clinicId = $this->upsertClinic($doctorId, $normalizedUsername, $clinicName, $email);
            $companyId = $this->upsertCompany($clinicId, $companyName);

            return [
                'user_id' => $userId,
                'doctor_id' => $doctorId,
                'clinic_id' => $clinicId,
                'company_id' => $companyId,
                'username' => $normalizedUsername,
                'password' => $password,
                'email' => $email,
                'clinic_name' => $clinicName,
                'company_name' => $companyId !== null ? $companyName : null,
            ];
        });
    }

    protected function upsertUser(string $username, string $password, string $email): int
    {
        $existing = DB::table('users')
            ->where('username', $username)
            ->orWhere('email', $email)
            ->first();

        $payload = [
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'Doctor',
        ];

        if ($existing) {
            DB::table('users')
                ->where('user_id', $existing->user_id)
                ->update($payload);

            return (int) $existing->user_id;
        }

        return (int) DB::table('users')->insertGetId($payload);
    }

    protected function upsertDoctor(string $username, string $password, string $email): int
    {
        $query = DB::table('doctor');

        if (Schema::hasColumn('doctor', 'doctor_email')) {
            $query->where('doctor_email', $email);
        }
        if (Schema::hasColumn('doctor', 'doctor_username')) {
            if (Schema::hasColumn('doctor', 'doctor_email')) {
                $query->orWhere('doctor_username', $username);
            } else {
                $query->where('doctor_username', $username);
            }
        }

        $existing = $query->first();

        $payload = [
            'doctor_firstName' => ucfirst($username),
            'doctor_lastName' => 'Testing',
            'doctor_email' => $email,
            'doctor_username' => $username,
            'doctor_password' => Hash::make($password),
            'doctor_telephone' => '+60120000000',
            'doctor_fax' => '+60300000000',
            'doctor_address' => 'Testing Clinic Address',
            'doctor_postcode' => '50000',
            'doctor_district' => 'Kuala Lumpur',
            'doctor_state' => 'Wilayah Persekutuan',
            'MMC_no' => 'MMC-' . strtoupper($username),
            'OHD_registrationNo' => 'OHD-' . strtoupper($username),
        ];

        if (Schema::hasColumn('doctor', 'doctor_status')) {
            $payload['doctor_status'] = 'active';
        }
        if (Schema::hasColumn('doctor', 'doctor_sign')) {
            $payload['doctor_sign'] = '';
        }
        if (Schema::hasColumn('doctor', 'doctor_picture')) {
            $payload['doctor_picture'] = '';
        }

        if ($existing) {
            DB::table('doctor')
                ->where('doctor_id', $existing->doctor_id)
                ->update($payload);

            return (int) $existing->doctor_id;
        }

        return (int) DB::table('doctor')->insertGetId($payload);
    }

    protected function upsertClinic(int $doctorId, string $username, string $clinicName, string $email): int
    {
        $existing = DB::table('clinic')
            ->where('doctor_id', $doctorId)
            ->orWhere('clinic_name', $clinicName)
            ->first();

        $payload = [
            'clinic_name' => $clinicName,
            'clinic_address' => 'Testing Clinic Address',
            'clinic_postcode' => '50000',
            'clinic_district' => 'Kuala Lumpur',
            'clinic_state' => 'Wilayah Persekutuan',
            'clinic_telephone' => '+60300000001',
            'clinic_fax' => '+60300000002',
            'clinic_email' => $email,
            'clinic_username' => $username . '_clinic',
            'clinic_password' => Hash::make('clinic-' . $username),
            'doctor_id' => $doctorId,
        ];

        if (Schema::hasColumn('clinic', 'clinic_status')) {
            $payload['clinic_status'] = 'active';
        }
        if (Schema::hasColumn('clinic', 'clinic_registration')) {
            $payload['clinic_registration'] = 'CLINIC-' . strtoupper($username);
        }
        if (Schema::hasColumn('clinic', 'clinic_header_path')) {
            $payload['clinic_header_path'] = null;
        }

        if ($existing) {
            DB::table('clinic')
                ->where('clinic_id', $existing->clinic_id)
                ->update($payload);

            return (int) $existing->clinic_id;
        }

        return (int) DB::table('clinic')->insertGetId($payload);
    }

    protected function upsertCompany(int $clinicId, string $companyName): ?int
    {
        if (! Schema::hasTable('company')) {
            return null;
        }

        $existing = DB::table('company')
            ->where('company_name', $companyName)
            ->first();

        $payload = [
            'company_name' => $companyName,
            'mykpp_registration_no' => 'MYKPP-' . strtoupper(preg_replace('/[^a-z0-9]+/i', '', $companyName) ?: 'TEST'),
            'company_address' => 'Testing Company Address',
            'company_postcode' => '50000',
            'company_district' => 'Kuala Lumpur',
            'company_state' => 'Wilayah Persekutuan',
            'company_telephone' => '+60300000003',
            'company_email' => strtolower(str_replace(' ', '.', $companyName)) . '@example.test',
            'company_fax' => '+60300000004',
            'total_workers' => 0,
        ];

        if (Schema::hasColumn('company', 'clinic_id')) {
            $payload['clinic_id'] = $clinicId;
        }
        if (Schema::hasColumn('company', 'company_module')) {
            $payload['company_module'] = null;
        }

        if ($existing) {
            DB::table('company')
                ->where('company_id', $existing->company_id)
                ->update($payload);

            return (int) $existing->company_id;
        }

        return (int) DB::table('company')->insertGetId($payload);
    }
}
