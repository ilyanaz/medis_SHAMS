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
        if ($request->session()->has('doctor_user_id')) {
            return redirect()->route('doctor.dashboard');
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
        ]);

        return redirect()->route('doctor.dashboard');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('doctor_user_id')) {
            return redirect()->route('doctor.login');
        }

        return view('doctor.dashboard', [
            'doctorEmail' => (string) $request->session()->get('doctor_user_email', ''),
            'doctorRole' => (string) $request->session()->get('doctor_user_role', ''),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'doctor_user_id',
            'doctor_user_email',
            'doctor_user_role',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('doctor.login');
    }
}