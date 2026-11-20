<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guest_cannot_access_private_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_anggota_is_forbidden_from_admin_routes(): void
    {
        $anggota = User::factory()->anggota()->create();
        $target = User::factory()->anggota()->create();

        $this->actingAs($anggota)->get(route('admin.users.index'))->assertForbidden();

        $this->actingAs($anggota)->post(route('admin.users.store'), [
            'name' => 'Petugas Baru',
            'email' => 'petugasbaru@example.com',
            'role' => RoleName::Petugas->value,
            'password' => 'SecretPassphrase123!',
            'password_confirmation' => 'SecretPassphrase123!',
        ])->assertForbidden();

        $this->actingAs($anggota)
            ->patch(route('admin.users.toggle-status', $target))
            ->assertForbidden();
    }

    public function test_petugas_is_forbidden_from_admin_user_management(): void
    {
        $petugas = User::factory()->petugas()->create();
        $target = User::factory()->anggota()->create();

        $this->actingAs($petugas)->get(route('admin.users.index'))->assertForbidden();

        $this->actingAs($petugas)->post(route('admin.users.store'), [
            'name' => 'Admin Gelap',
            'email' => 'admingelap@example.com',
            'role' => RoleName::Admin->value,
            'password' => 'SecretPassphrase123!',
            'password_confirmation' => 'SecretPassphrase123!',
        ])->assertForbidden();

        $this->actingAs($petugas)
            ->patch(route('admin.users.toggle-status', $target))
            ->assertForbidden();
    }

    public function test_admin_can_create_petugas_and_toggle_other_user_status(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->anggota()->create();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();

        $createRes = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Petugas Layanan',
            'email' => 'petugas@ruangtemubuku.local',
            'role' => RoleName::Petugas->value,
            'password' => 'PetugasPass123!',
            'password_confirmation' => 'PetugasPass123!',
        ]);

        $createRes->assertRedirect(route('admin.users.index'));
        $createdPetugas = User::query()->with('role')->where('email', 'petugas@ruangtemubuku.local')->firstOrFail();
        $this->assertSame(RoleName::Petugas->value, $createdPetugas->role->name);

        $toggleRes = $this->actingAs($admin)->patch(route('admin.users.toggle-status', $member));
        $toggleRes->assertRedirect(route('admin.users.index'));
        $this->assertSame(UserStatus::Inactive, $member->fresh()->status);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'user.status_changed',
            'subject_id' => $member->id,
        ]);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patch(route('admin.users.toggle-status', $admin));

        $response->assertForbidden();
        $this->assertSame(UserStatus::Active, $admin->fresh()->status);
    }

    public function test_member_can_update_own_profile_but_cannot_escalate_role(): void
    {
        $member = User::factory()->anggota()->create();

        $response = $this->actingAs($member)->put(route('profile.update'), [
            'name' => 'Nama Baru Anggota',
            'email' => 'namabaru@example.com',
            'current_password' => 'PasswordAman12345!',
            'role_id' => 1,
            'status' => UserStatus::Inactive->value,
        ]);

        $response->assertRedirect(route('profile.edit'));

        $member->refresh()->load('role');
        $this->assertSame('Nama Baru Anggota', $member->name);
        $this->assertSame('namabaru@example.com', $member->email);
        $this->assertSame(RoleName::Anggota->value, $member->role->name);
        $this->assertSame(UserStatus::Active, $member->status);
    }

    public function test_changing_email_requires_current_password_while_updating_name_only_does_not(): void
    {
        $member = User::factory()->anggota()->create([
            'email' => 'awal@example.com',
        ]);

        // Mengubah nama saja tanpa current_password diperbolehkan
        $resNameOnly = $this->actingAs($member)->put(route('profile.update'), [
            'name' => 'Nama Saja Diubah',
            'email' => 'awal@example.com',
        ]);
        $resNameOnly->assertRedirect(route('profile.edit'));
        $this->assertSame('Nama Saja Diubah', $member->fresh()->name);

        // Mengubah email tanpa current_password wajib ditolak
        $resEmailNoPass = $this->actingAs($member)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'name' => 'Nama Saja Diubah',
                'email' => 'diambilalih@example.com',
            ]);
        $resEmailNoPass->assertRedirect(route('profile.edit'));
        $resEmailNoPass->assertSessionHasErrors('current_password');
        $this->assertSame('awal@example.com', $member->fresh()->email);
    }
}
