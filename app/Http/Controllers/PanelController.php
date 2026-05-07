<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('panel_user_id')) {
            return $this->redirectToHome($request);
        }

        return view('panel.login', $this->buildViewData($request));
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('username', $credentials['username'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['username' => 'The provided login details are incorrect.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put([
            'panel_user_id' => $user->getKey(),
            'panel_user_email' => $user->email,
            'panel_user_role' => (string) $user->role,
            'panel_user_username' => (string) $user->username,
            'panel_user_original_role' => (string) $user->role,
            'panel_mode' => $this->isAdmin($user) ? 'admin' : 'clinic',
        ]);

        if (! $this->isAdmin($user)) {
            $defaultClinicId = $this->firstClinicId();
            if ($defaultClinicId !== null) {
                $request->session()->put('active_clinic_id', $defaultClinicId);
            }
        } else {
            $request->session()->forget('active_clinic_id');
        }

        return $this->redirectToHome($request);
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        return view('panel.dashboard', $this->buildViewData($request, $user));
    }

    public function adminDashboard(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canAccessAdminDashboard($request, $user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['adminEmail'] = (string) $user->email;
        $viewData['adminRole'] = (string) $user->role;
        $viewData['displayName'] = $this->displayName($user);
        $viewData['initials'] = $this->initials($viewData['displayName']);

        return view('admin.admin_dashboard', $viewData);
    }

    public function clinicSetup(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['pageMode'] = 'create';
        $viewData['clinicFormData'] = $this->clinicFormDefaults();

        return view('clinic.clinic_setup', $viewData);
    }

    public function doctorSetup(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['pageMode'] = 'create';
        $viewData['doctorFormData'] = $this->doctorFormDefaults();
        $viewData['canSaveDoctor'] = $this->canUseAdminMode($user);

        return view('doctor.doctor_setup', $viewData);
    }

    public function clinicShow(Request $request, int $clinic): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findClinic($clinic);
        if ($record === null) {
            return redirect()->route('admin.clinic_list')->withErrors(['clinic' => 'The selected clinic could not be found.']);
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['pageMode'] = 'view';
        $viewData['clinicRecord'] = $record;
        $viewData['clinicFormData'] = $this->clinicFormDefaults($record);

        return view('clinic.clinic_setup', $viewData);
    }

    public function clinicEdit(Request $request, int $clinic): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findClinic($clinic);
        if ($record === null) {
            return redirect()->route('admin.clinic_list')->withErrors(['clinic' => 'The selected clinic could not be found.']);
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['pageMode'] = 'edit';
        $viewData['clinicRecord'] = $record;
        $viewData['clinicFormData'] = $this->clinicFormDefaults($record);

        return view('clinic.clinic_setup', $viewData);
    }

    public function doctorShow(Request $request, int $doctor): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findDoctor($doctor);
        if ($record === null) {
            return redirect()->route('admin.doctor_list')->withErrors(['doctor' => 'The selected doctor could not be found.']);
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['pageMode'] = 'view';
        $viewData['doctorRecord'] = $record;
        $viewData['doctorFormData'] = $this->doctorFormDefaults($record);
        $viewData['canSaveDoctor'] = false;

        return view('doctor.doctor_setup', $viewData);
    }

    public function doctorEdit(Request $request, int $doctor): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findDoctor($doctor);
        if ($record === null) {
            return redirect()->route('admin.doctor_list')->withErrors(['doctor' => 'The selected doctor could not be found.']);
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['pageMode'] = 'edit';
        $viewData['doctorRecord'] = $record;
        $viewData['doctorFormData'] = $this->doctorFormDefaults($record);
        $viewData['canSaveDoctor'] = $this->canUseAdminMode($user);

        return view('doctor.doctor_setup', $viewData);
    }

    public function doctorList(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        $doctorQuery = DB::table('doctor')->select($this->doctorListColumns());
        if (Schema::hasColumn('doctor', 'doctor_status')) {
            $doctorQuery->orderByDesc('doctor_status');
        }

        $viewData = $this->buildViewData($request, $user);
        $viewData['doctors'] = $doctorQuery
            ->orderBy('doctor_firstName')
            ->orderBy('doctor_lastName')
            ->get();
        $viewData['canAddDoctor'] = $this->canUseAdminMode($user);

        return view('doctor.doctor_list', $viewData);
    }

    public function clinicList(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->isAdmin($user) && ! $this->isDoctor($user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        $clinicQuery = DB::table('clinic')->select($this->clinicListColumns());
        if (Schema::hasColumn('clinic', 'clinic_status')) {
            $clinicQuery->orderByDesc('clinic_status');
        }

        $viewData = $this->buildViewData($request, $user);
        $viewData['clinics'] = $clinicQuery
            ->orderBy('clinic_name')
            ->get();
        $viewData['canAddClinic'] = $this->isAdmin($user);

        return view('clinic.clinic_list', $viewData);
    }

    public function adminSettings(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['accountUser'] = $user;
        $viewData['doctorRecord'] = $this->linkedDoctorRecord($user);

        return view('admin.admin_setting', $viewData);
    }

    public function storeClinic(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $validated = $request->validate([
            'clinic_name' => ['required', 'string', 'max:150'],
            'clinic_email' => ['nullable', 'email', 'max:150'],
            'clinic_phone_code' => ['required', 'string', 'max:10'],
            'clinic_phone_number' => ['required', 'string', 'max:30'],
            'clinic_fax_code' => ['nullable', 'string', 'max:10'],
            'clinic_fax_number' => ['nullable', 'string', 'max:30'],
            'clinic_postcode' => ['required', 'string', 'max:10'],
            'clinic_district' => ['required', 'string', 'max:100'],
            'clinic_state' => ['required', 'string', 'max:100'],
            'registration' => ['required', 'string', 'max:100'],
            'clinic_status' => ['required', 'in:active,not active'],
            'clinic_address' => ['required', 'string', 'max:255'],
            'header_upload' => ['required', 'image', 'max:3072'],
        ]);

        $headerPath = null;
        if ($request->hasFile('header_upload')) {
            $file = $request->file('header_upload');
            $filename = 'clinic-header-' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/clinic-headers');
            if (! is_dir($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $filename);
            $headerPath = 'uploads/clinic-headers/' . $filename;
        }

        $usernameBase = Str::slug($validated['clinic_name'], '');
        $candidateUsername = $usernameBase !== '' ? $usernameBase : 'clinic' . now()->timestamp;
        $uniqueUsername = $candidateUsername;
        $suffix = 1;

        while (DB::table('clinic')->where('clinic_username', $uniqueUsername)->exists()) {
            $suffix++;
            $uniqueUsername = $candidateUsername . $suffix;
        }

        $payload = [
            'clinic_name' => $validated['clinic_name'],
            'clinic_address' => $validated['clinic_address'] ?: null,
            'clinic_postcode' => $validated['clinic_postcode'] ?: null,
            'clinic_district' => $validated['clinic_district'] ?: null,
            'clinic_state' => $validated['clinic_state'] ?: null,
            'clinic_telephone' => $this->buildCountryCodeNumber(
                $validated['clinic_phone_code'] ?? null,
                $validated['clinic_phone_number'] ?? null
            ),
            'clinic_fax' => $this->buildCountryCodeNumber(
                $validated['clinic_fax_code'] ?? null,
                $validated['clinic_fax_number'] ?? null
            ),
            'clinic_email' => $validated['clinic_email'] ?: null,
            'clinic_username' => $uniqueUsername,
            'clinic_password' => Hash::make(Str::random(32)),
            'clinic_status' => $validated['clinic_status'],
        ];

        $optionalFields = [
            'clinic_registration' => $validated['registration'] ?: null,
            'clinic_header_path' => $headerPath,
        ];

        foreach ($optionalFields as $column => $value) {
            if (Schema::hasColumn('clinic', $column)) {
                $payload[$column] = $value;
            }
        }

        DB::table('clinic')->insertGetId($payload);
        $request->session()->put('panel_mode', 'admin');

        return redirect()
            ->route('admin.clinic_list')
            ->with('status', 'Clinic saved successfully. It is now available in the navigation switcher.');
    }

    public function updateClinic(Request $request, int $clinic): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findClinic($clinic);
        if ($record === null) {
            return redirect()->route('admin.clinic_list')->withErrors(['clinic' => 'The selected clinic could not be found.']);
        }

        $validated = $request->validate([
            'clinic_name' => ['required', 'string', 'max:150'],
            'clinic_email' => ['nullable', 'email', 'max:150'],
            'clinic_phone_code' => ['required', 'string', 'max:10'],
            'clinic_phone_number' => ['required', 'string', 'max:30'],
            'clinic_fax_code' => ['nullable', 'string', 'max:10'],
            'clinic_fax_number' => ['nullable', 'string', 'max:30'],
            'clinic_postcode' => ['required', 'string', 'max:10'],
            'clinic_district' => ['required', 'string', 'max:100'],
            'clinic_state' => ['required', 'string', 'max:100'],
            'registration' => ['required', 'string', 'max:100'],
            'clinic_status' => ['required', 'in:active,not active'],
            'clinic_address' => ['required', 'string', 'max:255'],
            'header_upload' => ['nullable', 'image', 'max:3072'],
        ]);

        $headerPath = (string) ($record->clinic_header_path ?? '');
        if ($request->hasFile('header_upload')) {
            $this->deletePublicFile($headerPath);
            $file = $request->file('header_upload');
            $filename = 'clinic-header-' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/clinic-headers');
            if (! is_dir($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $filename);
            $headerPath = 'uploads/clinic-headers/' . $filename;
        }

        $payload = [
            'clinic_name' => $validated['clinic_name'],
            'clinic_address' => $validated['clinic_address'] ?: null,
            'clinic_postcode' => $validated['clinic_postcode'] ?: null,
            'clinic_district' => $validated['clinic_district'] ?: null,
            'clinic_state' => $validated['clinic_state'] ?: null,
            'clinic_telephone' => $this->buildCountryCodeNumber(
                $validated['clinic_phone_code'] ?? null,
                $validated['clinic_phone_number'] ?? null
            ),
            'clinic_fax' => $this->buildCountryCodeNumber(
                $validated['clinic_fax_code'] ?? null,
                $validated['clinic_fax_number'] ?? null
            ),
            'clinic_email' => $validated['clinic_email'] ?: null,
            'clinic_status' => $validated['clinic_status'],
        ];

        $optionalFields = [
            'clinic_registration' => $validated['registration'] ?: null,
            'clinic_header_path' => $headerPath !== '' ? $headerPath : null,
        ];

        foreach ($optionalFields as $column => $value) {
            if (Schema::hasColumn('clinic', $column)) {
                $payload[$column] = $value;
            }
        }

        DB::table('clinic')
            ->where('clinic_id', $record->clinic_id)
            ->update($payload);

        return redirect()
            ->route('admin.clinic_list')
            ->with('status', 'Clinic updated successfully.');
    }

    public function updateClinicStatus(Request $request, int $clinic): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findClinic($clinic);
        if ($record === null) {
            return redirect()->route('admin.clinic_list')->withErrors(['clinic' => 'The selected clinic could not be found.']);
        }

        $validated = $request->validate([
            'clinic_status' => ['required', 'in:active,not active'],
        ]);

        DB::table('clinic')
            ->where('clinic_id', $record->clinic_id)
            ->update(['clinic_status' => $validated['clinic_status']]);

        return redirect()
            ->route('admin.clinic_list')
            ->with('status', 'Clinic status updated successfully.');
    }

    public function destroyClinic(Request $request, int $clinic): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findClinic($clinic);
        if ($record === null) {
            return redirect()->route('admin.clinic_list')->withErrors(['clinic' => 'The selected clinic could not be found.']);
        }

        $this->deletePublicFile((string) ($record->clinic_header_path ?? ''));

        DB::table('clinic')
            ->where('clinic_id', $record->clinic_id)
            ->delete();

        if ((int) $request->session()->get('active_clinic_id', 0) === (int) $record->clinic_id) {
            $request->session()->forget('active_clinic_id');
            $request->session()->put('panel_mode', 'admin');
        }

        return redirect()
            ->route('admin.clinic_list')
            ->with('status', 'Clinic deleted successfully.');
    }

    public function storeDoctor(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $validated = $request->validate([
            'doctor_firstName' => ['required', 'string', 'max:100'],
            'doctor_lastName' => ['required', 'string', 'max:100'],
            'doctor_NRIC' => ['nullable', 'string', 'max:20', 'required_without:doctor_passportNo'],
            'doctor_passportNo' => ['nullable', 'string', 'max:30', 'required_without:doctor_NRIC'],
            'doctor_DOB' => ['required', 'date'],
            'doctor_gender' => ['required', 'string', 'max:20'],
            'doctor_address' => ['required', 'string', 'max:255'],
            'doctor_postcode' => ['required', 'string', 'max:10'],
            'doctor_district' => ['required', 'string', 'max:100'],
            'doctor_state' => ['required', 'string', 'max:100'],
            'doctor_phone_code' => ['required', 'string', 'max:10'],
            'doctor_phone_number' => ['required', 'string', 'max:30'],
            'doctor_fax_code' => ['nullable', 'string', 'max:10'],
            'doctor_fax_number' => ['nullable', 'string', 'max:30'],
            'doctor_email' => ['required', 'email', 'max:150'],
            'doctor_ethnicity' => ['required', 'string', 'max:50'],
            'doctor_citizenship' => ['required', 'string', 'max:50'],
            'doctor_martialStatus' => ['required', 'string', 'max:30'],
            'MMC_no' => ['required', 'string', 'max:50'],
            'OHD_registrationNo' => ['required', 'string', 'max:50'],
            'doctor_status' => ['required', 'in:active,not active'],
            'doctor_sign_data' => ['required', 'string'],
            'doctor_picture' => ['nullable', 'image', 'max:3072'],
        ]);

        $signaturePath = $this->storeBase64Image(
            $validated['doctor_sign_data'] ?? null,
            'doctor-sign-',
            'uploads/doctor-signatures'
        );

        $picturePath = null;
        if ($request->hasFile('doctor_picture')) {
            $file = $request->file('doctor_picture');
            $filename = 'doctor-picture-' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/doctor-pictures');
            if (! is_dir($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $filename);
            $picturePath = 'uploads/doctor-pictures/' . $filename;
        }

        $usernameBase = Str::slug(trim($validated['doctor_firstName'] . $validated['doctor_lastName']), '');
        $candidateUsername = $usernameBase !== '' ? $usernameBase : 'doctor' . now()->timestamp;
        $uniqueUsername = $candidateUsername;
        $suffix = 1;

        while (DB::table('doctor')->where('doctor_username', $uniqueUsername)->exists()) {
            $suffix++;
            $uniqueUsername = $candidateUsername . $suffix;
        }

        $payload = [
            'doctor_firstName' => $validated['doctor_firstName'],
            'doctor_lastName' => $validated['doctor_lastName'],
            'doctor_NRIC' => $validated['doctor_NRIC'] ?: null,
            'doctor_passportNo' => $validated['doctor_passportNo'] ?: null,
            'doctor_DOB' => $validated['doctor_DOB'] ?: null,
            'doctor_gender' => $validated['doctor_gender'] ?: null,
            'doctor_address' => $validated['doctor_address'] ?: null,
            'doctor_postcode' => $validated['doctor_postcode'] ?: null,
            'doctor_district' => $validated['doctor_district'] ?: null,
            'doctor_state' => $validated['doctor_state'] ?: null,
            'doctor_telephone' => $this->buildCountryCodeNumber(
                $validated['doctor_phone_code'] ?? null,
                $validated['doctor_phone_number'] ?? null
            ),
            'doctor_fax' => $this->buildCountryCodeNumber(
                $validated['doctor_fax_code'] ?? null,
                $validated['doctor_fax_number'] ?? null
            ),
            'doctor_email' => $validated['doctor_email'] ?: null,
            'doctor_ethnicity' => $validated['doctor_ethnicity'] ?: null,
            'doctor_citizenship' => $validated['doctor_citizenship'] ?: null,
            'doctor_martialStatus' => $validated['doctor_martialStatus'] ?: null,
            'MMC_no' => $validated['MMC_no'] ?: null,
            'OHD_registrationNo' => $validated['OHD_registrationNo'] ?: null,
            'doctor_username' => $uniqueUsername,
            'doctor_password' => Hash::make(Str::random(32)),
            'doctor_status' => $validated['doctor_status'],
            'doctor_sign' => $signaturePath,
            'doctor_picture' => $picturePath,
        ];

        DB::table('doctor')->insert($payload);
        $request->session()->put('panel_mode', 'admin');

        return redirect()
            ->route('admin.doctor_list')
            ->with('status', 'Doctor saved successfully.');
    }

    public function updateDoctor(Request $request, int $doctor): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findDoctor($doctor);
        if ($record === null) {
            return redirect()->route('admin.doctor_list')->withErrors(['doctor' => 'The selected doctor could not be found.']);
        }

        $validated = $request->validate([
            'doctor_firstName' => ['required', 'string', 'max:100'],
            'doctor_lastName' => ['required', 'string', 'max:100'],
            'doctor_NRIC' => ['nullable', 'string', 'max:20', 'required_without:doctor_passportNo'],
            'doctor_passportNo' => ['nullable', 'string', 'max:30', 'required_without:doctor_NRIC'],
            'doctor_DOB' => ['required', 'date'],
            'doctor_gender' => ['required', 'string', 'max:20'],
            'doctor_address' => ['required', 'string', 'max:255'],
            'doctor_postcode' => ['required', 'string', 'max:10'],
            'doctor_district' => ['required', 'string', 'max:100'],
            'doctor_state' => ['required', 'string', 'max:100'],
            'doctor_phone_code' => ['required', 'string', 'max:10'],
            'doctor_phone_number' => ['required', 'string', 'max:30'],
            'doctor_fax_code' => ['nullable', 'string', 'max:10'],
            'doctor_fax_number' => ['nullable', 'string', 'max:30'],
            'doctor_email' => ['required', 'email', 'max:150'],
            'doctor_ethnicity' => ['required', 'string', 'max:50'],
            'doctor_citizenship' => ['required', 'string', 'max:50'],
            'doctor_martialStatus' => ['required', 'string', 'max:30'],
            'MMC_no' => ['required', 'string', 'max:50'],
            'OHD_registrationNo' => ['required', 'string', 'max:50'],
            'doctor_status' => ['required', 'in:active,not active'],
            'doctor_sign_data' => ['nullable', 'string'],
            'doctor_picture' => ['nullable', 'image', 'max:3072'],
        ]);

        $signaturePath = (string) ($record->doctor_sign ?? '');
        if (trim((string) ($validated['doctor_sign_data'] ?? '')) !== '') {
            $this->deletePublicFile($signaturePath);
            $signaturePath = (string) $this->storeBase64Image(
                $validated['doctor_sign_data'],
                'doctor-sign-',
                'uploads/doctor-signatures'
            );
        }

        $picturePath = (string) ($record->doctor_picture ?? '');
        if ($request->hasFile('doctor_picture')) {
            $this->deletePublicFile($picturePath);
            $file = $request->file('doctor_picture');
            $filename = 'doctor-picture-' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/doctor-pictures');
            if (! is_dir($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $filename);
            $picturePath = 'uploads/doctor-pictures/' . $filename;
        }

        $payload = [
            'doctor_firstName' => $validated['doctor_firstName'],
            'doctor_lastName' => $validated['doctor_lastName'],
            'doctor_NRIC' => $validated['doctor_NRIC'] ?: null,
            'doctor_passportNo' => $validated['doctor_passportNo'] ?: null,
            'doctor_DOB' => $validated['doctor_DOB'] ?: null,
            'doctor_gender' => $validated['doctor_gender'] ?: null,
            'doctor_address' => $validated['doctor_address'] ?: null,
            'doctor_postcode' => $validated['doctor_postcode'] ?: null,
            'doctor_district' => $validated['doctor_district'] ?: null,
            'doctor_state' => $validated['doctor_state'] ?: null,
            'doctor_telephone' => $this->buildCountryCodeNumber(
                $validated['doctor_phone_code'] ?? null,
                $validated['doctor_phone_number'] ?? null
            ),
            'doctor_fax' => $this->buildCountryCodeNumber(
                $validated['doctor_fax_code'] ?? null,
                $validated['doctor_fax_number'] ?? null
            ),
            'doctor_email' => $validated['doctor_email'] ?: null,
            'doctor_ethnicity' => $validated['doctor_ethnicity'] ?: null,
            'doctor_citizenship' => $validated['doctor_citizenship'] ?: null,
            'doctor_martialStatus' => $validated['doctor_martialStatus'] ?: null,
            'MMC_no' => $validated['MMC_no'] ?: null,
            'OHD_registrationNo' => $validated['OHD_registrationNo'] ?: null,
            'doctor_status' => $validated['doctor_status'],
            'doctor_sign' => $signaturePath !== '' ? $signaturePath : null,
            'doctor_picture' => $picturePath !== '' ? $picturePath : null,
        ];

        DB::table('doctor')
            ->where('doctor_id', $record->doctor_id)
            ->update($payload);

        return redirect()
            ->route('admin.doctor_list')
            ->with('status', 'Doctor updated successfully.');
    }

    public function updateDoctorStatus(Request $request, int $doctor): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findDoctor($doctor);
        if ($record === null) {
            return redirect()->route('admin.doctor_list')->withErrors(['doctor' => 'The selected doctor could not be found.']);
        }

        $validated = $request->validate([
            'doctor_status' => ['required', 'in:active,not active'],
        ]);

        DB::table('doctor')
            ->where('doctor_id', $record->doctor_id)
            ->update(['doctor_status' => $validated['doctor_status']]);

        return redirect()
            ->route('admin.doctor_list')
            ->with('status', 'Doctor status updated successfully.');
    }

    public function destroyDoctor(Request $request, int $doctor): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findDoctor($doctor);
        if ($record === null) {
            return redirect()->route('admin.doctor_list')->withErrors(['doctor' => 'The selected doctor could not be found.']);
        }

        $references = $this->doctorReferenceSummary((int) $record->doctor_id);
        if ($references !== []) {
            return redirect()
                ->route('admin.doctor_list')
                ->withErrors([
                    'doctor' => 'This doctor cannot be deleted because it is still used in: ' . implode(', ', $references) . '.',
                ]);
        }

        try {
            DB::table('doctor')
                ->where('doctor_id', $record->doctor_id)
                ->delete();
        } catch (QueryException $exception) {
            return redirect()
                ->route('admin.doctor_list')
                ->withErrors([
                    'doctor' => 'This doctor cannot be deleted because related records still exist in the system.',
                ]);
        }

        $this->deletePublicFile((string) ($record->doctor_sign ?? ''));
        $this->deletePublicFile((string) ($record->doctor_picture ?? ''));

        return redirect()
            ->route('admin.doctor_list')
            ->with('status', 'Doctor deleted successfully.');
    }

    public function switchClinic(Request $request, int $clinic): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        $exists = DB::table('clinic')
            ->where('clinic_id', $clinic)
            ->exists();

        if (! $exists) {
            return back()->withErrors(['clinic' => 'The selected clinic could not be found.']);
        }

        $request->session()->put([
            'active_clinic_id' => $clinic,
            'panel_mode' => 'clinic',
        ]);

        return redirect()->route('panel.dashboard');
    }

    public function updateAdminUsername(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100'],
        ]);

        $newUsername = trim((string) $validated['username']);

        $exists = User::query()
            ->where('username', $newUsername)
            ->where($user->getKeyName(), '!=', $user->getKey())
            ->exists();

        if ($exists) {
            return back()->withErrors(['username' => 'That username is already in use.'])->withInput();
        }

        User::query()
            ->where($user->getKeyName(), $user->getKey())
            ->update(['username' => $newUsername]);

        $doctorRecord = $this->linkedDoctorRecord($user);
        if ($doctorRecord !== null) {
            DB::table('doctor')
                ->where('doctor_id', $doctorRecord->doctor_id)
                ->update(['doctor_username' => $newUsername]);
        }

        $request->session()->put('panel_user_username', $newUsername);

        return redirect()
            ->route('admin.settings')
            ->with('status', 'Username updated successfully.');
    }

    public function updateAdminPassword(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], (string) $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $hashedPassword = Hash::make($validated['new_password']);

        User::query()
            ->where($user->getKeyName(), $user->getKey())
            ->update(['password' => $hashedPassword]);

        $doctorRecord = $this->linkedDoctorRecord($user);
        if ($doctorRecord !== null) {
            DB::table('doctor')
                ->where('doctor_id', $doctorRecord->doctor_id)
                ->update(['doctor_password' => $hashedPassword]);
        }

        return redirect()
            ->route('admin.settings')
            ->with('status', 'Password updated successfully.');
    }

    public function switchAdmin(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        return redirect()->route('admin.dashboard');
    }

    public function showLogout(Request $request): View
    {
        return view('panel.logout', $this->buildViewData($request, $this->resolvePanelUser($request)));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'panel_user_id',
            'panel_user_email',
            'panel_user_role',
            'panel_user_username',
            'panel_user_original_role',
            'panel_mode',
            'active_clinic_id',
            'doctor_user_id',
            'doctor_user_email',
            'doctor_user_role',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('panel.login');
    }

    protected function buildViewData(Request $request, ?User $user = null): array
    {
        $user = $user ?? $this->resolvePanelUser($request);
        $inAdminMode = $request->session()->get('panel_mode', $this->isAdmin($user) ? 'admin' : 'clinic') === 'admin';
        $activeClinic = $inAdminMode ? null : $this->activeClinic($request);
        $clinicHeaderPath = is_object($activeClinic) && isset($activeClinic->clinic_header_path)
            ? (string) $activeClinic->clinic_header_path
            : null;

        return [
            'clinicName' => $activeClinic?->clinic_name ?? ($this->isAdmin($user) ? 'Admin' : 'Medis SHAMS'),
            'clinicLogoUrl' => $clinicHeaderPath ? asset($clinicHeaderPath) : null,
            'username' => $user ? $this->displayName($user) : 'User',
            'activeClinic' => $activeClinic,
            'panelUser' => $user,
        ];
    }

    protected function requirePanelUser(Request $request): User|RedirectResponse
    {
        $user = $this->resolvePanelUser($request);

        if (! $user) {
            return redirect()->route('panel.login');
        }

        return $user;
    }

    protected function resolvePanelUser(Request $request): ?User
    {
        $userId = $request->session()->get('panel_user_id');

        if (! $userId) {
            return null;
        }

        return User::query()->find($userId);
    }

    protected function activeClinic(Request $request): ?object
    {
        $clinicId = (int) $request->session()->get('active_clinic_id', 0);
        if ($clinicId <= 0) {
            return null;
        }

        return DB::table('clinic')
            ->select($this->clinicSelectColumns())
            ->where('clinic_id', $clinicId)
            ->first();
    }

    protected function shouldUseAdminNavigation(Request $request, ?User $user): bool
    {
        if (! $this->canUseAdminMode($user)) {
            return false;
        }

        return $request->session()->get('panel_mode', 'admin') === 'admin';
    }

    protected function isAdmin(?User $user): bool
    {
        return strtolower((string) ($user?->role ?? '')) === 'admin';
    }

    protected function isDoctor(?User $user): bool
    {
        return strtolower((string) ($user?->role ?? '')) === 'doctor';
    }

    protected function displayName(User $user): string
    {
        $fullName = trim((string) (($user->name ?? '') ?: ''));
        if ($fullName !== '') {
            return $fullName;
        }

        $username = trim((string) (($user->username ?? '') ?: ''));
        if ($username !== '') {
            return Str::title(str_replace(['.', '_', '-'], ' ', $username));
        }

        return 'User';
    }

    protected function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'U';
    }

    protected function redirectToHome(Request $request): RedirectResponse
    {
        $user = $this->resolvePanelUser($request);

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('panel.dashboard');
    }

    protected function firstClinicId(): ?int
    {
        $clinicId = DB::table('clinic')
            ->orderBy('clinic_name')
            ->value('clinic_id');

        return $clinicId ? (int) $clinicId : null;
    }

    protected function clinicSelectColumns(): array
    {
        $columns = ['clinic_id', 'clinic_name', 'clinic_email', 'clinic_telephone', 'clinic_address'];

        if (Schema::hasColumn('clinic', 'clinic_header_path')) {
            $columns[] = 'clinic_header_path';
        }

        return $columns;
    }

    protected function clinicListColumns(): array
    {
        $columns = [
            'clinic_id',
            'clinic_name',
            'clinic_address',
            'clinic_postcode',
            'clinic_district',
            'clinic_state',
            'clinic_telephone',
            'clinic_fax',
            'clinic_email',
            'clinic_username',
        ];

        $optionalColumns = [
            'clinic_registration',
            'clinic_header_path',
            'clinic_status',
        ];

        foreach ($optionalColumns as $column) {
            if (Schema::hasColumn('clinic', $column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    protected function doctorListColumns(): array
    {
        $columns = [
            'doctor_id',
            'doctor_firstName',
            'doctor_lastName',
            'doctor_telephone',
            'doctor_fax',
            'doctor_email',
            'OHD_registrationNo',
        ];

        if (Schema::hasColumn('doctor', 'doctor_status')) {
            $columns[] = 'doctor_status';
        }

        return $columns;
    }

    protected function clinicFormDefaults(?object $clinic = null): array
    {
        [$phoneCode, $phoneNumber] = $this->splitCountryCodeNumber((string) ($clinic->clinic_telephone ?? ''), '60');
        [$faxCode, $faxNumber] = $this->splitCountryCodeNumber((string) ($clinic->clinic_fax ?? ''), '60');

        return [
            'clinic_name' => (string) ($clinic->clinic_name ?? ''),
            'registration' => (string) ($clinic->clinic_registration ?? ''),
            'clinic_email' => (string) ($clinic->clinic_email ?? ''),
            'clinic_phone_code' => $phoneCode,
            'clinic_phone_number' => $phoneNumber,
            'clinic_fax_code' => $faxCode,
            'clinic_fax_number' => $faxNumber,
            'clinic_address' => (string) ($clinic->clinic_address ?? ''),
            'clinic_postcode' => (string) ($clinic->clinic_postcode ?? ''),
            'clinic_district' => (string) ($clinic->clinic_district ?? ''),
            'clinic_state' => (string) ($clinic->clinic_state ?? ''),
            'clinic_status' => (string) ($clinic->clinic_status ?? 'active'),
            'clinic_header_path' => (string) ($clinic->clinic_header_path ?? ''),
        ];
    }

    protected function doctorFormDefaults(?object $doctor = null): array
    {
        [$phoneCode, $phoneNumber] = $this->splitCountryCodeNumber((string) ($doctor->doctor_telephone ?? ''), '60');
        [$faxCode, $faxNumber] = $this->splitCountryCodeNumber((string) ($doctor->doctor_fax ?? ''), '60');

        return [
            'doctor_firstName' => (string) ($doctor->doctor_firstName ?? ''),
            'doctor_lastName' => (string) ($doctor->doctor_lastName ?? ''),
            'doctor_email' => (string) ($doctor->doctor_email ?? ''),
            'doctor_NRIC' => (string) ($doctor->doctor_NRIC ?? ''),
            'doctor_passportNo' => (string) ($doctor->doctor_passportNo ?? ''),
            'doctor_DOB' => (string) ($doctor->doctor_DOB ?? ''),
            'doctor_gender' => (string) ($doctor->doctor_gender ?? ''),
            'doctor_ethnicity' => (string) ($doctor->doctor_ethnicity ?? ''),
            'doctor_citizenship' => (string) ($doctor->doctor_citizenship ?? ''),
            'doctor_martialStatus' => (string) ($doctor->doctor_martialStatus ?? ''),
            'MMC_no' => (string) ($doctor->MMC_no ?? ''),
            'OHD_registrationNo' => (string) ($doctor->OHD_registrationNo ?? ''),
            'doctor_phone_code' => $phoneCode,
            'doctor_phone_number' => $phoneNumber,
            'doctor_fax_code' => $faxCode,
            'doctor_fax_number' => $faxNumber,
            'doctor_address' => (string) ($doctor->doctor_address ?? ''),
            'doctor_postcode' => (string) ($doctor->doctor_postcode ?? ''),
            'doctor_district' => (string) ($doctor->doctor_district ?? ''),
            'doctor_state' => (string) ($doctor->doctor_state ?? ''),
            'doctor_status' => (string) ($doctor->doctor_status ?? 'active'),
            'doctor_sign' => (string) ($doctor->doctor_sign ?? ''),
            'doctor_picture' => (string) ($doctor->doctor_picture ?? ''),
        ];
    }

    protected function requiresClinicSelection(Request $request, ?User $user): bool
    {
        return $this->isDoctor($user) && (int) $request->session()->get('active_clinic_id', 0) <= 0;
    }

    protected function canAccessAdminDashboard(Request $request, ?User $user): bool
    {
        return $this->canUseAdminMode($user) || $this->requiresClinicSelection($request, $user);
    }

    protected function canManageClinics(?User $user): bool
    {
        return $this->isAdmin($user) || $this->isDoctor($user);
    }

    protected function canUseAdminMode(?User $user): bool
    {
        return $this->isAdmin($user) || $this->isDoctor($user);
    }

    protected function isInAdminMode(Request $request, ?User $user): bool
    {
        return $this->canUseAdminMode($user)
            && $request->session()->get('panel_mode', 'admin') === 'admin';
    }

    protected function buildCountryCodeNumber(?string $countryCode, ?string $number): ?string
    {
        $number = trim((string) $number);
        if ($number === '') {
            return null;
        }

        $normalizedCode = preg_replace('/\D/', '', (string) $countryCode) ?? '';
        $normalizedCode = $normalizedCode !== '' ? $normalizedCode : '60';
        $normalizedNumber = preg_replace('/\D/', '', $number) ?? '';
        if ($normalizedNumber === '') {
            return null;
        }

        if (str_starts_with($normalizedNumber, '0')) {
            $normalizedNumber = substr($normalizedNumber, 1);
        }

        return '+' . $normalizedCode . $normalizedNumber;
    }

    protected function splitCountryCodeNumber(?string $value, string $defaultCode = '60'): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [$defaultCode, ''];
        }

        $normalized = preg_replace('/\D/', '', $value) ?? '';
        if ($normalized === '') {
            return [$defaultCode, ''];
        }

        foreach (['60', '65', '62', '66'] as $code) {
            if (str_starts_with($normalized, $code)) {
                return [$code, substr($normalized, strlen($code))];
            }
        }

        return [$defaultCode, $normalized];
    }

    protected function linkedDoctorRecord(User $user): ?object
    {
        return DB::table('doctor')
            ->where('doctor_email', (string) $user->email)
            ->orWhere('doctor_username', (string) $user->username)
            ->first();
    }

    protected function findClinic(int $clinicId): ?object
    {
        return DB::table('clinic')
            ->select($this->clinicListColumns())
            ->where('clinic_id', $clinicId)
            ->first();
    }

    protected function findDoctor(int $doctorId): ?object
    {
        $columns = [
            'doctor_id',
            'doctor_firstName',
            'doctor_lastName',
            'doctor_NRIC',
            'doctor_passportNo',
            'doctor_DOB',
            'doctor_gender',
            'doctor_address',
            'doctor_postcode',
            'doctor_district',
            'doctor_state',
            'doctor_telephone',
            'doctor_fax',
            'doctor_email',
            'doctor_ethnicity',
            'doctor_citizenship',
            'doctor_martialStatus',
            'MMC_no',
            'OHD_registrationNo',
            'doctor_username',
            'doctor_sign',
            'doctor_picture',
        ];

        if (Schema::hasColumn('doctor', 'doctor_status')) {
            $columns[] = 'doctor_status';
        }

        return DB::table('doctor')
            ->select($columns)
            ->where('doctor_id', $doctorId)
            ->first();
    }

    protected function doctorReferenceSummary(int $doctorId): array
    {
        $references = [];

        $referenceTables = [
            'chemical_information' => 'chemical information',
            'summary_report' => 'summary report',
        ];

        foreach ($referenceTables as $table => $label) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'doctor_id')) {
                continue;
            }

            $count = DB::table($table)
                ->where('doctor_id', $doctorId)
                ->count();

            if ($count > 0) {
                $references[] = $label;
            }
        }

        return $references;
    }

    protected function storeBase64Image(?string $value, string $prefix, string $directory): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (! preg_match('/^data:image\/(?P<type>png|jpe?g);base64,(?P<data>.+)$/i', $value, $matches)) {
            return null;
        }

        $binary = base64_decode(str_replace(' ', '+', $matches['data']), true);
        if ($binary === false) {
            return null;
        }

        $extension = strtolower($matches['type']) === 'jpeg' ? 'jpg' : strtolower($matches['type']);
        $filename = $prefix . Str::uuid() . '.' . $extension;
        $destination = public_path($directory);

        if (! is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        file_put_contents($destination . DIRECTORY_SEPARATOR . $filename, $binary);

        return trim($directory, '/\\') . '/' . $filename;
    }

    protected function deletePublicFile(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }

        $fullPath = public_path($path);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
