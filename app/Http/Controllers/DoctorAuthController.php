<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DoctorAuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('panel_user_id') || $request->session()->has('doctor_user_id')) {
            return $this->redirectToPanelHome($request);
        }

        return view('doctor.docLogin');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $credentials['email'])
            ->where('role', 'Doctor')
            ->first();

        if (! $user || ! $this->passwordMatches($user, $credentials['password'])) {
            return back()
                ->withErrors(['email' => 'The provided login details are incorrect.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put([
            'doctor_user_id' => $user->getKey(),
            'doctor_user_email' => $user->email,
            'doctor_user_role' => $user->role,
            'panel_user_id' => $user->getKey(),
            'panel_user_email' => $user->email,
            'panel_user_role' => (string) $user->role,
            'panel_user_username' => (string) $user->username,
            'panel_user_original_role' => (string) $user->role,
            'panel_mode' => 'admin',
        ]);
        $this->applyAssignedClinicScope($request, $user);

        return redirect()->route('admin.dashboard');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('doctor_user_id') && ! $request->session()->has('panel_user_id')) {
            return redirect()->route('doctor.login');
        }

        return $this->redirectToPanelHome($request);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'doctor_user_id',
            'doctor_user_email',
            'doctor_user_role',
            'panel_user_id',
            'panel_user_email',
            'panel_user_role',
            'panel_user_username',
            'panel_user_original_role',
            'panel_mode',
            'active_clinic_id',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('doctor.login');
    }

    protected function redirectToPanelHome(Request $request): RedirectResponse
    {
        $role = strtolower((string) ($request->session()->get('panel_user_original_role')
            ?? $request->session()->get('doctor_user_role')
            ?? ''));

        if ($role === 'doctor') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('panel.dashboard');
    }

    protected function passwordMatches(User $user, string $plainPassword): bool
    {
        $storedPassword = (string) ($user->password ?? '');

        if ($storedPassword === '') {
            return false;
        }

        if (Hash::check($plainPassword, $storedPassword)) {
            return true;
        }

        $normalizedStored = strtolower($storedPassword);
        $matchesLegacyPlain = hash_equals($storedPassword, $plainPassword);
        $matchesLegacyMd5 = preg_match('/^[a-f0-9]{32}$/i', $storedPassword) === 1
            && hash_equals($normalizedStored, md5($plainPassword));
        $matchesLegacySha1 = preg_match('/^[a-f0-9]{40}$/i', $storedPassword) === 1
            && hash_equals($normalizedStored, sha1($plainPassword));

        if (! $matchesLegacyPlain && ! $matchesLegacyMd5 && ! $matchesLegacySha1) {
            return false;
        }

        $user->forceFill([
            'password' => Hash::make($plainPassword),
        ])->save();

        return true;
    }

    protected function applyAssignedClinicScope(Request $request, User $user): void
    {
        $clinicId = $this->assignedClinicId($user);
        if ($clinicId === null) {
            $request->session()->forget('active_clinic_id');
            $request->session()->put('panel_mode', 'admin');

            return;
        }

        $request->session()->put([
            'active_clinic_id' => $clinicId,
            'panel_mode' => 'clinic',
        ]);
    }

    protected function assignedClinicId(User $user): ?int
    {
        if (! Schema::hasTable('doctor') || ! Schema::hasTable('clinic') || ! Schema::hasColumn('clinic', 'doctor_id')) {
            return null;
        }

        $doctor = DB::table('doctor')
            ->when(
                Schema::hasColumn('doctor', 'doctor_email'),
                static fn ($query) => $query->where('doctor_email', (string) $user->email)
            )
            ->when(
                Schema::hasColumn('doctor', 'doctor_username'),
                static function ($query) use ($user): void {
                    $query->orWhere('doctor_username', (string) $user->username);
                }
            )
            ->when(
                Schema::hasColumn('doctor', 'doctor_status'),
                static fn ($query) => $query->where('doctor_status', 'active')
            )
            ->first();

        if (! $doctor) {
            return null;
        }

        $clinicId = DB::table('clinic')
            ->when(
                Schema::hasColumn('clinic', 'clinic_status'),
                static fn ($query) => $query->where('clinic_status', 'active')
            )
            ->where('doctor_id', (int) $doctor->doctor_id)
            ->orderBy('clinic_id')
            ->value('clinic_id');

        return $clinicId ? (int) $clinicId : null;
    }
}
