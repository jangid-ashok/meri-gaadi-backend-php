<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            'profile_image' => ['nullable', 'file', 'max:'.config('media.max_image_kb', 5120)],
        ]);

        $user = $request->user();
        $user->name = $request->name;
        $oldImage = $user->profile_image;
        $uploadedImage = null;
        if ($request->hasFile('profile_image')) {
            $uploadedImage = app(MediaService::class)->store($request->file('profile_image'), (int) $user->id, null, 'media', 'profile_image')->path;
            $user->profile_image = $uploadedImage;
        }
        try {
            $user->save();
        } catch (\Throwable $exception) {
            if ($uploadedImage) app(MediaService::class)->cleanupNewUpload($uploadedImage);
            throw $exception;
        }
        if ($oldImage && $request->hasFile('profile_image')) {
            $media = Media::where('disk', 'media')->where('path', $oldImage)->first();
            if ($media) app(MediaService::class)->deleteIfUnreferenced($media);
            elseif (! \App\Models\User::where('profile_image', $oldImage)->exists()) Storage::disk('public')->delete($oldImage);
        }

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
