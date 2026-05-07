<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DeveloperAuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('developer_user_id')) {
            return redirect()->route('developer.dashboard');
        }

        return view('developer.devLogin');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $credentials['email'])
            ->where('role', 'Admin')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'The provided login details are incorrect.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put([
            'developer_user_id' => $user->getKey(),
            'developer_user_email' => $user->email,
            'developer_user_role' => $user->role,
        ]);

        return redirect()->route('developer.dashboard');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('developer_user_id')) {
            return redirect()->route('developer.login');
        }

        return view('developer.devDashboard', [
            'developerEmail' => (string) $request->session()->get('developer_user_email', ''),
            'developerRole' => (string) $request->session()->get('developer_user_role', ''),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'developer_user_id',
            'developer_user_email',
            'developer_user_role',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('developer.login');
    }
}
