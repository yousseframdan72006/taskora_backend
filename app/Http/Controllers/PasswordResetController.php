<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Services\OtpService;

class PasswordResetController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/forgot-password
    // ──────────────────────────────────────────────────────────────────────────

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $this->otpService->sendOtp($request->validated('email'));

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email. It is valid for 5 minutes.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/verify-otp
    // ──────────────────────────────────────────────────────────────────────────

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $data = $request->validated();

        $this->otpService->verifyOtp($data['email'], $data['otp']);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/reset-password
    // ──────────────────────────────────────────────────────────────────────────

    public function resetPassword(ResetPasswordRequest $request)
    {
        $data = $request->validated();

        $this->otpService->resetPassword(
            $data['email'],
            $data['otp'],
            $data['password'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You can now log in.',
        ]);
    }
}
