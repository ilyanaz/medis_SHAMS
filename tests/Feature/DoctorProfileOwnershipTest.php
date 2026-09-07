<?php

namespace Tests\Feature;

use App\Http\Controllers\PanelController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DoctorProfileOwnershipTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('username');
            $table->string('email');
            $table->string('role');
            $table->string('password')->default('');
        });
        Schema::create('doctor', function (Blueprint $table) {
            $table->increments('doctor_id');
            $table->string('doctor_username')->nullable();
            $table->string('doctor_email')->nullable();
        });
        DB::table('users')->insert([
            ['user_id' => 1, 'username' => 'first', 'email' => 'first@example.test', 'role' => 'Doctor'],
            ['user_id' => 2, 'username' => 'second', 'email' => 'second@example.test', 'role' => 'Doctor'],
        ]);
        DB::table('doctor')->insert([
            ['doctor_id' => 1, 'doctor_username' => 'first', 'doctor_email' => 'first@example.test'],
            ['doctor_id' => 2, 'doctor_username' => 'existing', 'doctor_email' => null],
        ]);
        (require database_path('migrations/2026_09_07_000001_link_users_to_doctor_profiles.php'))->up();
    }

    public function test_existing_link_survives_username_and_email_changes(): void
    {
        DB::table('users')->where('user_id', 1)->update(['username' => 'changed', 'email' => 'changed@example.test']);
        $controller = new class extends PanelController {
            public function doctorFor(User $user): ?object { return $this->linkedDoctorRecord($user); }
        };
        $this->assertSame(1, $controller->doctorFor(User::find(1))->doctor_id);
        $this->assertNull($controller->doctorFor(User::find(2)));
    }

    public function test_profile_cannot_link_existing_unassigned_doctor(): void
    {
        $this->withSession(['panel_user_id' => 2])
            ->post(route('admin.profile.link'), ['doctor_id' => 2])
            ->assertRedirect(route('admin.settings', ['tab' => 'profile']))
            ->assertSessionHasErrors('doctor_id');

        $this->assertNull(User::find(2)->doctor_id);
    }

    public function test_doctor_cannot_be_claimed_by_another_account(): void
    {
        $this->withSession(['panel_user_id' => 2])
            ->post(route('admin.profile.link'), ['doctor_id' => 1])
            ->assertSessionHasErrors('doctor_id');

        $this->assertNull(User::find(2)->doctor_id);
    }

    public function test_linked_user_cannot_switch_to_a_second_doctor(): void
    {
        $this->withSession(['panel_user_id' => 1])
            ->post(route('admin.profile.link'), ['doctor_id' => 2])
            ->assertSessionHasErrors('doctor_id');

        $this->assertSame(1, User::find(1)->doctor_id);
    }

    public function test_old_edit_endpoint_cannot_edit_another_doctor(): void
    {
        $this->withSession(['panel_user_id' => 1])->put(route('admin.doctor.update', ['doctor' => 2]), [])->assertForbidden();
    }

    public function test_doctor_list_redirects_to_profile(): void
    {
        $this->get('/admin/doctors')->assertRedirect('/admin/settings?tab=profile');
    }
}
