<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class AdminPasswordResetController extends Controller
{
    public function create()
    {
        return view('admin.auth.forgot-password');
    }

    public function email(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->where('is_admin', true)->first();
        if ($user) {
            if (app()->environment('local')) {
                $token = app('auth.password.broker')->createToken($user);
                session([
                    'admin_reset_url' => route('admin.password.reset', ['token' => $token, 'email' => $user->email]),
                    'admin_reset_email' => $user->email,
                ]);

                return redirect()->route('admin.password.link');
            }

            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('status', __('If an admin account exists for that email, a password reset link has been sent.'));
    }

    public function showLink()
    {
        abort_unless(app()->environment('local'), 404);

        return view('admin.auth.reset-link-sent', [
            'resetUrl' => session('admin_reset_url'),
            'email' => session('admin_reset_email'),
        ]);
    }

    public function createReset(Request $request, string $token)
    {
        return view('admin.auth.reset-password', compact('request', 'token'));
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::where('email', $request->email)->where('is_admin', true)->first();
        if (! $user) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __('The password reset link is invalid or has expired.')]);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('admin.login')->with('status', __('Your password has been reset.'))
            : back()->withInput($request->only('email'))->withErrors(['email' => __('The password reset link is invalid or has expired.')]);
    }
}