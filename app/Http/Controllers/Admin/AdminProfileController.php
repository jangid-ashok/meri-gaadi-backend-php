<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AdminProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('admin.auth.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = $request->user();
        $user->name = $request->name;
        if ($request->hasFile('profile_image')) {
            $user->profile_image = $request->file('profile_image')->store('profile-images', 'public');
        }
        $user->save();

        return back()->with('status', __('Profile updated successfully.'));
    }

    public function changePassword()
    {
        return view('admin.auth.change-password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if (Hash::check($request->password, $request->user()->password)) {
            return back()->withErrors(['password' => __('The new password must be different from your current password.')]);
        }

        $request->user()->update(['password' => Hash::make($request->password)]);
        auth()->logoutOtherDevices($request->password);

        return back()->with('status', __('Password changed successfully.'));
    }
}