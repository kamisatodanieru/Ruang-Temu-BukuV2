<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_authenticate_and_session_is_regenerated(): void
    {
        $user = User::factory()->anggota()->create([
            'password' => 'SecretPass123!',
        ]);

        $oldSessionId = session()->getId();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'SecretPass123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
        $this->assertNotSame($oldSessionId, session()->getId());
    }

    public function test_invalid_login_fails_with_generic_message_without_enumerating_email(): void
    {
        $user = User::factory()->anggota()->create([
            'email' => 'registered@example.com',
            'password' => 'SecretPass123!',
        ]);

        $responseWrongPass = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'WrongPassword!',
        ]);

        $responseUnknownEmail = $this->from(route('login'))->post(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'WrongPassword!',
        ]);

        $this->assertGuest();

        $errorWrongPass = $responseWrongPass->getSession()->get('errors')->first('email');
        $errorUnknownEmail = $responseUnknownEmail->getSession()->get('errors')->first('email');

        $this->assertSame('Email atau password yang Anda masukkan salah.', $errorWrongPass);
        $this->assertSame($errorWrongPass, $errorUnknownEmail);
    }

    public function test_login_is_rate_limited_with_progressive_tiers_after_three_failed_attempts(): void
    {
        $user = User::factory()->anggota()->create([
            'password' => 'SecretPassphrase123!',
        ]);

        // Sesi 1: Percobaan ke-1 dan ke-2 gagal dengan pesan generik
        for ($i = 1; $i <= 2; $i++) {
            $res = $this->from(route('login'))->post(route('login'), [
                'email' => $user->email,
                'password' => 'WrongPass!',
            ]);
            $this->assertSame(
                'Email atau password yang Anda masukkan salah.',
                $res->getSession()->get('errors')->first('email')
            );
        }

        // Percobaan ke-3 gagal -> memicu Lockout Tier 1 (60 detik / 1 menit)
        $resTier1 = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'WrongPass!',
        ]);
        $this->assertGuest();
        $errorTier1 = $resTier1->getSession()->get('errors')->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login', $errorTier1);
        $this->assertStringContainsString('(1 menit)', $errorTier1);

        // Selama masa lockout Tier 1 aktif, password benar sekalipun tetap ditolak
        $resBlocked = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'SecretPassphrase123!',
        ]);
        $this->assertGuest();
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            $resBlocked->getSession()->get('errors')->first('email')
        );

        // Setelah 61 detik berlalu, user mendapat 3 kesempatan baru (Opsi A)
        $this->travel(61)->seconds();

        for ($i = 1; $i <= 2; $i++) {
            $res = $this->from(route('login'))->post(route('login'), [
                'email' => $user->email,
                'password' => 'WrongPass!',
            ]);
            $this->assertSame(
                'Email atau password yang Anda masukkan salah.',
                $res->getSession()->get('errors')->first('email')
            );
        }

        // Percobaan ke-3 gagal di sesi kedua -> memicu Lockout Tier 2 (300 detik / 5 menit)
        $resTier2 = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'WrongPass!',
        ]);
        $errorTier2 = $resTier2->getSession()->get('errors')->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login', $errorTier2);
        $this->assertStringContainsString('(5 menit)', $errorTier2);

        // Setelah 5 menit + 1 detik berlalu, user kembali mendapat 3 kesempatan baru
        $this->travel(301)->seconds();

        for ($i = 1; $i <= 2; $i++) {
            $res = $this->from(route('login'))->post(route('login'), [
                'email' => $user->email,
                'password' => 'WrongPass!',
            ]);
            $this->assertSame(
                'Email atau password yang Anda masukkan salah.',
                $res->getSession()->get('errors')->first('email')
            );
        }

        // Percobaan ke-3 gagal di sesi ketiga -> memicu Lockout Tier 3 (900 detik / 15 menit)
        $resTier3 = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'WrongPass!',
        ]);
        $errorTier3 = $resTier3->getSession()->get('errors')->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login', $errorTier3);
        $this->assertStringContainsString('(15 menit)', $errorTier3);

        // Setelah 15 menit + 1 detik berlalu, user kembali mendapat 3 kesempatan baru
        $this->travel(901)->seconds();

        for ($i = 1; $i <= 2; $i++) {
            $this->from(route('login'))->post(route('login'), [
                'email' => $user->email,
                'password' => 'WrongPass!',
            ]);
        }

        // Percobaan ke-3 gagal di sesi keempat -> memicu Lockout Tier 4 (3600 detik / 60 menit)
        $resTier4 = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'WrongPass!',
        ]);
        $errorTier4 = $resTier4->getSession()->get('errors')->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login', $errorTier4);
        $this->assertStringContainsString('(60 menit)', $errorTier4);

        // Setelah 1 jam + 1 detik berlalu, login dengan password benar mereset seluruh tier ke 0
        $this->travel(3601)->seconds();

        $resSuccess = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'SecretPassphrase123!',
        ]);
        $this->assertAuthenticatedAs($user);
        $resSuccess->assertRedirect(route('dashboard'));
    }

    public function test_inactive_account_cannot_login(): void
    {
        $user = User::factory()->anggota()->inactive()->create([
            'password' => 'SecretPass123!',
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'SecretPass123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_active_session_is_terminated_when_user_status_becomes_inactive(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        // Admin menonaktifkan akun saat user masih memiliki session aktif
        $user->status = UserStatus::Inactive;
        $user->save();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logout_terminates_session_and_requires_post_method(): void
    {
        $user = User::factory()->anggota()->create();

        $this->actingAs($user)->get('/logout')->assertMethodNotAllowed();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
