<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Notifications\ConfirmMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


class AuthController extends BaseController
{

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'mail_token' => Str::random(64),
        ]);
         $user->notify(new ConfirmMail($user));

        $token = $user->createToken('frontend-token')->plainTextToken;

        return $this->sendResponse([
            'token' => $token,
            'user' => $user,
        ], 'Registered successfully please check your email to verify your account');
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->sendError('Invalid credentials', [], 401);
        }

        $user = Auth::user();

        if (!$user->email_verified_at) {
            return $this->sendError(
                'Please verify your email first',
                ['email_verified' => false],
                403
            );
        }

        $user->tokens()->delete();

        $token = $user->createToken('frontend-token')->plainTextToken;

        return $this->sendResponse([
            'token' => $token,
            'user' => $user,
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse([], 'Logged out successfully');
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return $this->sendError('Email already verified', [], 400);
        }

        $user->mail_token = Str::random(64);
        $user->save();

        $user->notify(new ConfirmMail($user));

        return $this->sendResponse([], 'Verification email resent');
    }

    public function verifyEmail($token)
    {
        $frontendUrl = 'http://localhost:5173/auth/email-verified?status=success';

        $user = User::where('mail_token', $token)->first();

        if (!$user) {
            return $this->sendError('Invalid token', [], 404);
        }

        $user->email_verified_at = now();
        $user->mail_token = null;
        $user->save();

        return redirect($frontendUrl);
    }
}