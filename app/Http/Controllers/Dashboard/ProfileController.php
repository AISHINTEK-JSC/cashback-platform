<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('dashboard.profile.edit', [
            'user' => $request->user(),
            'activities' => $request->user()->activityLogs()->latest('id')->limit(20)->get(),
        ]);
    }

    public function update(Request $request, ActivityLogger $activity): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'regex:/^0\d{9}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ], [
            'phone.regex' => 'Số điện thoại gồm 10 chữ số, bắt đầu bằng 0.',
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = '/storage/'.$request->file('avatar')->store('avatars', 'public');
        } else {
            unset($data['avatar']);
        }

        $user->update($data);
        $activity->log($user, 'profile.update');

        return back()->with('success', 'Đã cập nhật hồ sơ.');
    }

    public function updatePassword(Request $request, ActivityLogger $activity): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => [$user->password ? 'required' : 'nullable', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update(['password' => $data['password']]);
        $activity->log($user, 'password.update');

        return back()->with('success', 'Đã đổi mật khẩu.');
    }
}
