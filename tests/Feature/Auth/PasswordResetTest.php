<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
    }

    public function test_forgot_password_returns_generic_status_without_leaking_account_existence(): void
    {
        $user = User::factory()->anggota()->create([
            'email' => 'member@example.com',
        ]);

        $resExisting = $this->post(route('password.email'), ['email' => $user->email]);
        $resNonExisting = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        $resExisting->assertSessionHas('status', PasswordResetLinkController::GENERIC_RESET_LINK_MESSAGE);
        $resNonExisting->assertSessionHas('status', PasswordResetLinkController::GENERIC_RESET_LINK_MESSAGE);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->anggota()->create();

        $token = Password::createToken($user);

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewSecurePass123!', $user->fresh()->password));
    }
}
