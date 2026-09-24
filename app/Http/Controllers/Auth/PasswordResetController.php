<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Send a password reset link to the given user.
     */
    public function sendResetLinkEmail(ForgotPasswordRequest $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = User::where('email', strtolower($request->email))->first();

        if ($user) {
            $token = Password::broker()->createToken($user);
            $user->sendPasswordResetNotification($token);
        }

        // Generic response prevents account enumeration attacks
        return back()->with('status', 'If your email address is registered with this workspace, a password reset link has been sent to your inbox.');
    }

    /**
     * Reset the given user's password.
     */
    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Your password has been successfully reset! Please sign in with your new credentials.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
