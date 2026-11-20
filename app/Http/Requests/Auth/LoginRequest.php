<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public const MAX_ATTEMPTS = 3;

    /**
     * Batas percobaan gagal global per alamat IP untuk mencegah password spraying lintas akun.
     */
    public const MAX_IP_ATTEMPTS = 20;

    /**
     * Hash bcrypt dummy untuk menyamakan waktu komputasi ketika email tidak terdaftar (mencegah timing enumeration).
     */
    private const DUMMY_PASSWORD_HASH = '$2y$04$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

    /**
     * Masa berlaku jendela perhitungan percobaan gagal (1 jam).
     */
    private const ATTEMPT_DECAY_SECONDS = 3600;

    /**
     * Masa berlaku jendela percobaan gagal per IP (10 menit) dan durasi blokir IP (15 menit).
     */
    private const IP_DECAY_SECONDS = 600;

    private const IP_LOCKOUT_SECONDS = 900;

    /**
     * Masa berlaku penyimpanan riwayat tier hukuman (24 jam).
     */
    private const TIER_DECAY_SECONDS = 86400;

    /**
     * Durasi timeout bertingkat berdasarkan level tier (dalam detik):
     * Tier 1 = 60 detik (1 menit)
     * Tier 2 = 300 detik (5 menit)
     * Tier 3 = 900 detik (15 menit)
     * Tier 4+ = 3600 detik (1 jam)
     *
     * @var array<int, int>
     */
    private const LOCKOUT_TIERS = [
        1 => 60,
        2 => 300,
        3 => 900,
        4 => 3600,
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    /**
     * Autentikasi kredensial request dengan progressive rate limiting (3x gagal -> 1m, 15m, 1j),
     * pesan generik, dan pemeriksaan status akun.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = Str::lower((string) $this->string('email'));
        $password = (string) $this->string('password');

        $user = User::query()->with('role')->where('email', $email)->first();

        if ($user === null) {
            Hash::check($password, self::DUMMY_PASSWORD_HASH);
            $this->recordFailedAttempt();
        }

        if (! Hash::check($password, $user->password)) {
            $this->recordFailedAttempt();
        }

        if (! $user->isActive()) {
            $this->recordFailedAttempt(
                customMessage: 'Akun Anda tidak aktif. Silakan hubungi administrator perpustakaan.'
            );
        }

        Auth::login($user, $this->boolean('remember'));

        $this->clearRateLimitingState();
    }

    /**
     * Pastikan percobaan login tidak sedang berada dalam masa lockout (baik per akun maupun per IP).
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (RateLimiter::tooManyAttempts($this->ipLockoutKey(), 1)) {
            event(new Lockout($this));

            $seconds = RateLimiter::availableIn($this->ipLockoutKey());

            throw ValidationException::withMessages([
                'email' => $this->formatLockoutMessage($seconds),
            ]);
        }

        if (! RateLimiter::tooManyAttempts($this->lockoutKey(), 1)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->lockoutKey());

        throw ValidationException::withMessages([
            'email' => $this->formatLockoutMessage($seconds),
        ]);
    }

    /**
     * Catat kegagalan login dan aktifkan timeout bertingkat apabila sudah mencapai 3 kali gagal.
     *
     * @throws ValidationException
     */
    private function recordFailedAttempt(?string $customMessage = null): never
    {
        $ipAttempts = RateLimiter::hit($this->ipThrottleKey(), self::IP_DECAY_SECONDS);
        $attempts = RateLimiter::hit($this->throttleKey(), self::ATTEMPT_DECAY_SECONDS);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $tier = ((int) Cache::get($this->tierKey(), 0)) + 1;
            Cache::put($this->tierKey(), $tier, self::TIER_DECAY_SECONDS);

            $lockoutSeconds = self::LOCKOUT_TIERS[min($tier, 4)];

            RateLimiter::clear($this->throttleKey());
            RateLimiter::hit($this->lockoutKey(), $lockoutSeconds);

            event(new Lockout($this));

            $seconds = RateLimiter::availableIn($this->lockoutKey());

            throw ValidationException::withMessages([
                'email' => $this->formatLockoutMessage($seconds),
            ]);
        }

        if ($ipAttempts >= self::MAX_IP_ATTEMPTS) {
            RateLimiter::clear($this->ipThrottleKey());
            RateLimiter::hit($this->ipLockoutKey(), self::IP_LOCKOUT_SECONDS);

            event(new Lockout($this));

            $seconds = RateLimiter::availableIn($this->ipLockoutKey());

            throw ValidationException::withMessages([
                'email' => $this->formatLockoutMessage($seconds),
            ]);
        }

        throw ValidationException::withMessages([
            'email' => $customMessage ?? 'Email atau password yang Anda masukkan salah.',
        ]);
    }

    private function clearRateLimitingState(): void
    {
        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->lockoutKey());
        RateLimiter::clear($this->ipThrottleKey());
        RateLimiter::clear($this->ipLockoutKey());
        Cache::forget($this->tierKey());
    }

    private function formatLockoutMessage(int $seconds): string
    {
        if ($seconds >= 60) {
            $minutes = (int) ceil($seconds / 60);

            return "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik ({$minutes} menit).";
        }

        return "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.";
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email', '')).'|'.$this->ip());
    }

    public function lockoutKey(): string
    {
        return $this->throttleKey().'|lockout';
    }

    public function tierKey(): string
    {
        return $this->throttleKey().'|tier';
    }

    public function ipThrottleKey(): string
    {
        return 'login-ip|'.$this->ip();
    }

    public function ipLockoutKey(): string
    {
        return $this->ipThrottleKey().'|lockout';
    }
}
