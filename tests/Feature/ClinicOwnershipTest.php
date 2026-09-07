<?php

namespace Tests\Feature;

use App\Http\Controllers\PanelController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClinicOwnershipTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->increments('user_id');
            $table->string('username');
            $table->string('email');
            $table->string('role');
            $table->string('password')->default('');
            $table->unsignedInteger('doctor_id')->nullable();
        });

        Schema::create('clinic', function (Blueprint $table): void {
            $table->increments('clinic_id');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('clinic_name');
            $table->string('clinic_email')->nullable();
            $table->string('clinic_status', 20)->default('active');
            $table->string('clinic_header_path')->nullable();
        });

        DB::table('users')->insert([
            ['user_id' => 1, 'username' => 'wan-haziq', 'email' => 'wan@example.test', 'role' => 'Doctor', 'doctor_id' => 1],
            ['user_id' => 2, 'username' => 'ilyana', 'email' => 'ilyana@example.test', 'role' => 'Doctor', 'doctor_id' => 2],
        ]);

        DB::table('clinic')->insert([
            ['clinic_id' => 1, 'user_id' => 1, 'clinic_name' => 'Dr Wan Clinic', 'clinic_email' => 'wan-clinic@example.test', 'clinic_status' => 'active'],
            ['clinic_id' => 2, 'user_id' => 2, 'clinic_name' => 'Ilyana Clinic', 'clinic_email' => 'ilyana-clinic@example.test', 'clinic_status' => 'active'],
        ]);
    }

    public function test_user_can_switch_to_own_clinic(): void
    {
        $this->withSession(['panel_user_id' => 2])
            ->post(route('panel.clinic.switch', ['clinic' => 2]))
            ->assertRedirect(route('panel.dashboard'));

        $this->assertSame(2, session('active_clinic_id'));
    }

    public function test_user_cannot_switch_to_another_users_clinic(): void
    {
        $this->withSession(['panel_user_id' => 2])
            ->post(route('panel.clinic.switch', ['clinic' => 1]))
            ->assertSessionHasErrors('clinic');

        $this->assertNull(session('active_clinic_id'));
    }

    public function test_active_clinic_ignores_another_users_session_clinic(): void
    {
        $controller = new class extends PanelController {
            public function activeClinicFor(\Illuminate\Http\Request $request): ?object
            {
                return $this->activeClinic($request);
            }
        };

        $request = request();
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('panel_user_id', 2);
        $request->session()->put('active_clinic_id', 1);

        $this->assertNull($controller->activeClinicFor($request));
    }
}
