<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AdminSettingsTabsTest extends TestCase
{
    private function renderSettings(string $tab = 'profile', array $errors = []): string
    {
        $request = Request::create('/admin/settings', 'GET', ['tab' => $tab]);
        $request->setLaravelSession(app('session.store'));
        app()->instance('request', $request);

        return view('admin.admin_setting', [
            'accountUser' => (object) ['username' => 'test-doctor', 'email' => 'doctor@example.test'],
            'doctorRecord' => (object) ['doctor_id' => 1, 'doctor_email' => 'doctor@example.test', 'doctor_sign' => null, 'doctor_picture' => null],
            'doctorFormData' => ['doctor_firstName' => 'Test', 'doctor_lastName' => 'Doctor'],
            'errors' => (new ViewErrorBag())->put('default', new MessageBag($errors)),
        ])->render();
    }

    public function test_profile_contains_username_and_doctor_form_with_one_page_shell(): void
    {
        $html = $this->renderSettings();
        $this->assertStringContainsString('name="username"', $html);
        $this->assertStringNotContainsString('Change Username', $html);
        $this->assertLessThan(strpos($html, 'name="doctor_firstName"'), strpos($html, 'name="username"'));
        $this->assertStringContainsString('name="doctor_firstName"', $html);
        $this->assertStringContainsString('name="doctor_sign_upload"', $html);
        $this->assertStringContainsString(route('admin.profile.update'), $html);
        $this->assertStringNotContainsString('Use Existing Doctor Information', $html);
        $this->assertStringNotContainsString('Use This Profile', $html);
        $this->assertStringNotContainsString('name="current_password"', $html);
        $this->assertSame(1, substr_count(strtolower($html), '<!doctype html>'));
    }

    public function test_password_tab_only_shows_password_form(): void
    {
        $html = $this->renderSettings('password');
        $this->assertStringContainsString('name="current_password"', $html);
        $this->assertStringContainsString('name="new_password_confirmation"', $html);
        $this->assertStringNotContainsString('name="doctor_firstName"', $html);
        $this->assertStringNotContainsString('name="username"', $html);
    }

    public function test_password_validation_error_keeps_password_tab_visible(): void
    {
        $html = $this->renderSettings('profile', ['current_password' => 'The current password is incorrect.']);
        $this->assertStringContainsString('name="current_password"', $html);
        $this->assertStringContainsString('The current password is incorrect.', $html);
    }
}
