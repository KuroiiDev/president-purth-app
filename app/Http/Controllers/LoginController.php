<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            return redirect()->intended(route('home'));
        }

        return view('auth.login');
    }

    public function auth(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = Str::lower($credentials['email']);
        $ip = $request->ip();
        $throttleKey = Str::transliterate($email.'|'.$ip);
        $ipThrottleKey = 'login:ip:'.$ip;

        // Cek lockout per kombinasi email + IP (5x gagal per menit)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors([
                    'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
                ])
                ->onlyInput('email');
        }

        // Cek lockout per IP (30x gagal per jam)
        if (RateLimiter::tooManyAttempts($ipThrottleKey, 30)) {
            $seconds = RateLimiter::availableIn($ipThrottleKey);

            return back()
                ->withErrors([
                    'email' => "Terlalu banyak percobaan login dari IP ini. Coba lagi dalam {$seconds} detik.",
                ])
                ->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            RateLimiter::clear($throttleKey);
            RateLimiter::clear($ipThrottleKey);

            $request->session()->regenerate();

            return redirect()->intended(route('home'))
                ->with('success', 'Selamat datang kembali, Admin! Mode edit telah aktif.');
        }

        RateLimiter::hit($throttleKey, 60);
        RateLimiter::hit($ipThrottleKey, 3600);

        return back()
            ->withErrors([
                'email' => 'Kredensial yang Anda masukkan tidak cocok dengan data kami.',
            ])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(route('home'))->with('success', 'Anda berhasil logout. Mode edit telah dinonaktifkan.');
    }
}
