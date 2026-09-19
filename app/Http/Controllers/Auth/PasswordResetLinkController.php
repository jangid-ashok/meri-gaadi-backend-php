<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        
        // For local development, we'll display the reset link instead of sending email
        if (app()->environment('local')) {
            $status = $this->sendLocalResetLink($request->email);
            
            if ($status === Password::RESET_LINK_SENT) {
                return redirect()->route('password.show-link')->with([
                    'reset_url' => session('reset_url'),
                    'reset_email' => $request->email,
                ]);
            }
            
            return back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
        }
        
        // For production, send email
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                            ->withErrors(['email' => __($status)]);
    }

    /**
     * Display the reset link (local development only).
     *
     * @return \Illuminate\View\View
     */
    public function showLink()
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        return view('auth.reset-link-sent', [
            'resetUrl' => session('reset_url'),
            'email' => session('reset_email'),
        ]);
    }

    /**
     * Send reset link for local development (display in browser).
     *
     * @param  string  $email
     * @return string
     */
    protected function sendLocalResetLink($email)
    {
        $user = \App\Models\User::where('email', $email)->first();
        
        if (!$user) {
            return Password::INVALID_USER;
        }

        $token = app('auth.password.broker')->createToken($user);
        $user->sendPasswordResetNotification($token);
        $resetUrl = route('password.reset', ['token' => $token], false);
        
        // Store the reset URL in session for display
        session([
            'reset_url' => route('password.reset', ['token' => $token]),
            'reset_email' => $email,
            'reset_link_shown' => true
        ]);

        return Password::RESET_LINK_SENT;
    }
}
