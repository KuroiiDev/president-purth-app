<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('test@example.com|127.0.0.1');
        RateLimiter::clear('other@example.com|127.0.0.1');
        RateLimiter::clear('case@example.com|127.0.0.1');
        RateLimiter::clear('login:ip:127.0.0.1');
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_is_locked_out_after_5_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->from('/login')->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
            $response->assertSessionHasErrors(['email' => 'Kredensial yang Anda masukkan tidak cocok dengan data kami.']);
        }

        // 6th attempt with CORRECT password is still locked out
        $response = $this->from('/login')->post('/login', [
            'email' => 'test@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $errorBag = session('errors');
        $errorMessage = $errorBag->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login. Coba lagi dalam', $errorMessage);
        $this->assertGuest();
    }

    public function test_different_email_is_not_locked_out_by_another_users_lock(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password-1'),
        ]);

        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'password' => Hash::make('password-2'),
        ]);

        // Lock out test@example.com with 5 failures
        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // other@example.com can still login successfully
        $response = $this->post('/login', [
            'email' => 'other@example.com',
            'password' => 'password-2',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($otherUser);
    }

    public function test_rate_limiting_is_case_insensitive_for_email(): void
    {
        User::factory()->create([
            'email' => 'case@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        // 5 attempts with mixed-case email
        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => 'CaSe@ExAmPlE.cOm',
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt with lowercase email should be locked out
        $response = $this->from('/login')->post('/login', [
            'email' => 'case@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $errorBag = session('errors');
        $errorMessage = $errorBag->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login. Coba lagi dalam', $errorMessage);
        $this->assertGuest();
    }

    public function test_successful_login_resets_rate_limiter(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        // 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->from('/login')->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // 4th attempt is successful
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'correct-password',
        ]);

        $this->assertAuthenticated();

        // Logout
        $this->post('/logout');
        $this->assertGuest();

        // Should have reset attempts (can fail again up to 5 times without lock)
        $response = $this->from('/login')->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
        $response->assertSessionHasErrors(['email' => 'Kredensial yang Anda masukkan tidak cocok dengan data kami.']);
    }
}
