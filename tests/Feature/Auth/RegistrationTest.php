<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_valid_member_registration_succeeds_and_hashes_password(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'SecretPassphrase123!',
            'password_confirmation' => 'SecretPassphrase123!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertGuest();

        $user = User::query()->with('role')->where('email', 'budi@example.com')->firstOrFail();

        $this->assertSame(RoleName::Anggota->value, $user->role->name);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNotSame('SecretPassphrase123!', $user->password);
        $this->assertStringStartsWith('$2y$', $user->password);
        $this->assertTrue(Hash::check('SecretPassphrase123!', $user->password));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'user.registered',
        ]);
    }

    public function test_password_must_be_between_15_and_64_characters_per_nist_800_63b_rev4(): void
    {
        // 14 karakter (< 15) harus ditolak
        $shortPassword = str_repeat('a', 14);
        $resShort = $this->from(route('register'))->post(route('register'), [
            'name' => 'User Pendek',
            'email' => 'short@example.com',
            'password' => $shortPassword,
            'password_confirmation' => $shortPassword,
        ]);
        $resShort->assertRedirect(route('register'));
        $resShort->assertSessionHasErrors('password');
        $this->assertGuest();

        // 65 karakter (> 64) harus ditolak
        $longPassword = str_repeat('b', 65);
        $resLong = $this->from(route('register'))->post(route('register'), [
            'name' => 'User Panjang',
            'email' => 'long@example.com',
            'password' => $longPassword,
            'password_confirmation' => $longPassword,
        ]);
        $resLong->assertRedirect(route('register'));
        $resLong->assertSessionHasErrors('password');
        $this->assertGuest();

        // Tepat 15 karakter (batas bawah) diterima
        $minBoundaryPassword = str_repeat('c', 15);
        $resMin = $this->post(route('register'), [
            'name' => 'User Batas Bawah',
            'email' => 'min15@example.com',
            'password' => $minBoundaryPassword,
            'password_confirmation' => $minBoundaryPassword,
        ]);
        $resMin->assertRedirect(route('login'));
        $this->assertGuest();

        // Tepat 64 karakter (batas atas) diterima
        $maxBoundaryPassword = str_repeat('d', 64);
        $resMax = $this->post(route('register'), [
            'name' => 'User Batas Atas',
            'email' => 'max64@example.com',
            'password' => $maxBoundaryPassword,
            'password_confirmation' => $maxBoundaryPassword,
        ]);
        $resMax->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_identical_passwords_produce_unique_salted_bcrypt_hashes(): void
    {
        $samePassphrase = 'FrasaSandiPerpustakaan2026!';

        $this->post(route('register'), [
            'name' => 'Anggota Satu',
            'email' => 'satu@example.com',
            'password' => $samePassphrase,
            'password_confirmation' => $samePassphrase,
        ]);

        $this->post(route('register'), [
            'name' => 'Anggota Dua',
            'email' => 'dua@example.com',
            'password' => $samePassphrase,
            'password_confirmation' => $samePassphrase,
        ]);

        $user1 = User::query()->where('email', 'satu@example.com')->firstOrFail();
        $user2 = User::query()->where('email', 'dua@example.com')->firstOrFail();

        $this->assertNotSame($user1->password, $user2->password);
        $this->assertTrue(Hash::check($samePassphrase, $user1->password));
        $this->assertTrue(Hash::check($samePassphrase, $user2->password));
    }

    public function test_duplicate_email_registration_is_rejected(): void
    {
        User::factory()->anggota()->create([
            'email' => 'duplikat@example.com',
        ]);

        $response = $this->from(route('register'))->post(route('register'), [
            'name' => 'Pengguna Kedua',
            'email' => 'duplikat@example.com',
            'password' => 'SecretPassphrase123!',
            'password_confirmation' => 'SecretPassphrase123!',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_manipulating_request_payload_cannot_escalate_role_to_admin_or_petugas(): void
    {
        $adminRole = Role::findByName(RoleName::Admin);

        $response = $this->post(route('register'), [
            'name' => 'Attacker User',
            'email' => 'attacker@example.com',
            'password' => 'SecretPassphrase123!',
            'password_confirmation' => 'SecretPassphrase123!',
            'role' => RoleName::Admin->value,
            'role_id' => $adminRole?->id,
            'is_admin' => true,
            'status' => UserStatus::Inactive->value,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $user = User::query()->with('role')->where('email', 'attacker@example.com')->firstOrFail();

        $this->assertSame(RoleName::Anggota->value, $user->role->name);
        $this->assertNotSame($adminRole?->id, $user->role_id);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_sql_injection_and_xss_payload_in_registration_is_treated_as_plain_escaped_data(): void
    {
        $payloadName = "O'Reilly'; DROP TABLE users;-- <script>alert(1)</script>";

        $response = $this->post(route('register'), [
            'name' => $payloadName,
            'email' => 'securitytest@example.com',
            'password' => 'SecretPassphrase123!',
            'password_confirmation' => 'SecretPassphrase123!',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $this->assertDatabaseHas('users', [
            'email' => 'securitytest@example.com',
            'name' => $payloadName,
        ]);

        $user = User::query()->where('email', 'securitytest@example.com')->firstOrFail();
        $dashboard = $this->actingAs($user)->get(route('dashboard'));
        $dashboard->assertOk();
        $dashboard->assertDontSee('<script>alert(1)</script>', false);
        $dashboard->assertSee(e($payloadName), false);
    }
}
