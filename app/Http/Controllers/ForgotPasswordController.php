<?php

namespace App\Http\Controllers;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Controllers\BaseController;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ForgotPasswordController extends BaseController
{
 // SEND RESET LINK
    public function forgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::Where('email', $request->email)->first();

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token'=> $token,
                'created_at'=> Carbon::now()
            ]
        );
        $resetLink = "http://localhost:5173/auth/reset-password?token={$token}&email={$user->email}";

        $user->notify(new ResetPasswordNotification($user, $resetLink));

        return response ()->json([
            'success'=> true,
            'message' => 'password reset link sent to your email'
        ]);
            
    }

    // RESET PASSWORD
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();
        
        if(!$record) {
            return $this->sendError('Invalid or expired token');
        }

        $user = User::where('email', $request->email)->first();

        $user->password = Hash::make($request->password);
        $user->remember_token = Str::random(60);
        $user->save();

        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();
      
            return $this->sendResponse([], 'Password reset successful');
}
}