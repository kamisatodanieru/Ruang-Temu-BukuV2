<?php

namespace Tests\Feature\Security;

use App\Enums\LoanStatus;
use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_security_headers_are_applied_to_responses(): void
    {
        $response = $this->get(route('catalog.index'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("default-src 'self'", (string) $response->headers->get('Content-Security-Policy'));
        $response->assertHeaderMissing('X-Powered-By');
    }

    public function test_all_response_cookies_have_httponly_flag_and_redirects_have_empty_body(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();

        foreach ($response->headers->getCookies() as $cookie) {
            $this->assertTrue(
                $cookie->isHttpOnly(),
                "Cookie [{$cookie->getName()}] harus memiliki flag HttpOnly."
            );
        }

        $user = User::factory()->anggota()->create([
            'password' => 'Password123!',
        ]);

        $redirectResponse = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $redirectResponse->assertRedirect(route('dashboard'));
        $this->assertSame('', $redirectResponse->getContent());
    }

    public function test_rendered_views_do_not_leak_suspicious_user_html_comments(): void
    {
        $user = User::factory()->admin()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('<!-- User Footer Card -->', false);
    }

    public function test_mass_assignment_safeguards_prevent_privileged_attribute_injection(): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecretPass123!',
            'role_id' => 99,
            'status' => UserStatus::Inactive->value,
        ]);
        $this->assertNull($user->role_id);
        $this->assertNull($user->getAttributes()['status'] ?? null);

        $category = Category::factory()->create();
        $book = new Book([
            'category_id' => $category->id,
            'title' => 'Judul Buku',
            'author' => 'Penulis',
            'total_stock' => 5,
            'available_stock' => 999,
        ]);
        $this->assertNull($book->getAttributes()['available_stock'] ?? null);

        $loan = new Loan([
            'member_id' => 1,
            'book_id' => 1,
            'requested_at' => now(),
            'status' => LoanStatus::Returned->value,
            'approved_by' => 1,
            'returned_confirmed_by' => 1,
        ]);
        $this->assertNull($loan->getAttributes()['status'] ?? null);
        $this->assertNull($loan->approved_by);
        $this->assertNull($loan->returned_confirmed_by);
    }

    public function test_audit_logs_never_contain_plaintext_passwords_or_tokens(): void
    {
        $plainPassword = 'UltraSecretPassword987!';

        $this->post(route('register'), [
            'name' => 'Anggota Baru',
            'email' => 'anggota@example.com',
            'password' => $plainPassword,
            'password_confirmation' => $plainPassword,
        ]);

        $logs = ActivityLog::query()->pluck('description')->implode(' ');

        $this->assertStringNotContainsString($plainPassword, $logs);
    }

    public function test_dark_mode_toggle_is_rendered_on_app_and_guest_layouts(): void
    {
        $catalogRes = $this->get(route('catalog.index'));
        $catalogRes->assertOk();
        $catalogRes->assertSee('id="theme-toggle-btn"', false);
        $catalogRes->assertSee('toggleTheme()', false);

        $loginRes = $this->get(route('login'));
        $loginRes->assertOk();
        $loginRes->assertSee('id="theme-toggle-btn"', false);
        $loginRes->assertSee('toggleTheme()', false);
    }

    public function test_password_visibility_toggle_is_rendered_on_auth_and_profile_forms(): void
    {
        $loginRes = $this->get(route('login'));
        $loginRes->assertOk();
        $loginRes->assertSee("togglePasswordVisibility('password', this)", false);
        $loginRes->assertSee('eye-icon', false);
        $loginRes->assertSee('eye-slash-icon', false);

        $registerRes = $this->get(route('register'));
        $registerRes->assertOk();
        $registerRes->assertSee("togglePasswordVisibility('password', this)", false);
        $registerRes->assertSee("togglePasswordVisibility('password_confirmation', this)", false);

        $user = User::factory()->anggota()->create();
        $profileRes = $this->actingAs($user)->get(route('profile.edit'));
        $profileRes->assertOk();
        $profileRes->assertSee("togglePasswordVisibility('current_password', this)", false);
        $profileRes->assertSee("togglePasswordVisibility('password', this)", false);
        $profileRes->assertSee("togglePasswordVisibility('password_confirmation', this)", false);
    }

    public function test_login_is_rate_limited_per_ip_against_password_spraying(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $res = $this->from(route('login'))->post(route('login'), [
                'email' => "spray_target_{$i}@example.com",
                'password' => 'WrongPassword123!',
            ]);
        }

        $blockedRes = $this->from(route('login'))->post(route('login'), [
            'email' => 'another_target@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $this->assertGuest();
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            (string) $blockedRes->getSession()->get('errors')?->first('email')
        );
    }

    public function test_search_filters_escape_sql_like_wildcards(): void
    {
        $category = Category::factory()->create(['name' => 'Sains']);
        Book::factory()->create([
            'category_id' => $category->id,
            'title' => 'Buku Normal Tanpa Persen',
            'author' => 'Penulis A',
        ]);

        $response = $this->get(route('catalog.index', ['search' => '%']));
        $response->assertOk();
        $response->assertDontSee('Buku Normal Tanpa Persen');
    }
}
