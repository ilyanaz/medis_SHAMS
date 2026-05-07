<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
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
        $request->session()->forget('active_clinic_id');

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
}
