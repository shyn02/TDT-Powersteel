<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PasswordResetRequestController extends Controller
{
    public function showRequestForm()
    {
        return view('auth.request-password-reset');
    }

    public function request(Request $request)
    {
        $key = 'request-password-reset:'. $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['email' => "Too many attempts. Try again in {$seconds}s."])->withInput();
        }

        $request->validate([
            'email' => ['required','email','max:255'],
        ]);

        $email = strtolower(trim($request->input('email')));
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

        $existing = PasswordResetRequest::whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();
        if (! $existing) {
            PasswordResetRequest::create([
                'email' => $email,
                'user_id' => $user?->id,
                'status' => 'pending',
                'requested_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);
            try { \App\Models\ActivityLog::log($user ?? null, "Password reset requested for {$email}"); } catch (\Throwable $e) {}
        }

        RateLimiter::hit($key, 300);

        return back()->with('status', 'If an account exists for that email, a reset request has been submitted. An admin will review it.');
    }

    public function showResetForm(Request $request, string $token)
    {
        // For admin-approved flow, we use a simple token check via PasswordResetRequest
        // This is a placeholder for future email-based reset via Laravel's Password broker
        return view('auth.reset-password', ['token' => $token, 'email' => $request->input('email')]);
    }
}
