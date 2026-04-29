<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    /** Maximum wrong-attempt count before the record is deleted. */
    private const MAX_ATTEMPTS = 5;

    /** OTP validity in minutes. */
    private const EXPIRY_MINUTES = 5;

    // ──────────────────────────────────────────────────────────────────────────
    // Step 1 — Send OTP
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Generate a new OTP, store it hashed, and email it to the user.
     *
     * @throws ValidationException if the email is not registered
     */
    public function sendOtp(string $email): void
    {
        // Make sure the email belongs to a real user
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['No account found with this email address.'],
            ]);
        }

        // Delete any previous OTP codes for this email
        OtpCode::where('email', $email)->delete();

        // Generate a cryptographically-secure 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store the hashed OTP
        OtpCode::create([
            'email'       => $email,
            'otp'         => Hash::make($otp),
            'expires_at'  => now()->addMinutes(self::EXPIRY_MINUTES),
            'is_verified' => false,
            'attempts'    => 0,
        ]);

        // Send the plain OTP via email (MAIL_MAILER=log in dev → check logs)
        $user->notify(new SendOtpNotification($otp));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Step 2 — Verify OTP
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Verify that the supplied OTP is correct, not expired, and not over-attempted.
     *
     * @throws ValidationException on any failure
     */
    public function verifyOtp(string $email, string $otp): void
    {
        $record = OtpCode::where('email', $email)
            ->where('is_verified', false)
            ->latest()
            ->first();

        $this->guardRecord($record, $otp);

        // Mark as verified so it can only be consumed once
        $record->update(['is_verified' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Step 3 — Reset Password
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Double-check the OTP, update the user's password, then delete the record.
     *
     * @throws ValidationException on any failure
     */
    public function resetPassword(string $email, string $otp, string $newPassword): void
    {
        $record = OtpCode::where('email', $email)
            ->where('is_verified', true)
            ->latest()
            ->first();

        $this->guardRecord($record, $otp);

        // Update user password
        User::where('email', $email)->update([
            'password' => Hash::make($newPassword),
        ]);

        // Consume the OTP — delete so it can never be re-used
        $record->delete();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Shared guard
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Validate expiry, attempt limit, and OTP hash.
     *
     * @throws ValidationException
     */
    private function guardRecord(?OtpCode $record, string $otp): void
    {
        if (! $record) {
            throw ValidationException::withMessages([
                'otp' => ['No OTP request found. Please request a new one.'],
            ]);
        }

        if ($record->isExpired()) {
            $record->delete();
            throw ValidationException::withMessages([
                'otp' => ['OTP has expired. Please request a new one.'],
            ]);
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            $record->delete();
            throw ValidationException::withMessages([
                'otp' => ['Too many wrong attempts. Please request a new OTP.'],
            ]);
        }

        if (! Hash::check($otp, $record->otp)) {
            // Increment attempt counter
            $record->increment('attempts');
            $remaining = self::MAX_ATTEMPTS - $record->fresh()->attempts;

            throw ValidationException::withMessages([
                'otp' => ["Invalid OTP. {$remaining} attempt(s) remaining."],
            ]);
        }
    }
}
