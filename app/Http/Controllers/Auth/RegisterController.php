<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\LoginTracker;
use App\Services\Auth\RegisterUser;
use App\Services\ReferralService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.register', [
            'referralCode' => $request->query('ref', $request->cookie(ReferralService::COOKIE)),
        ]);
    }

    public function store(Request $request, RegisterUser $register, LoginTracker $tracker): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^0\d{9}$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'referral_code' => ['nullable', 'string', 'max:20', 'exists:users,referral_code'],
            'terms' => ['accepted'],
        ], [
            'phone.regex' => 'Số điện thoại gồm 10 chữ số, bắt đầu bằng 0.',
            'referral_code.exists' => 'Mã giới thiệu không tồn tại.',
            'terms.accepted' => 'Bạn cần đồng ý với điều khoản sử dụng.',
        ]);

        $user = $register->handle(
            [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ],
            $data['referral_code'] ?? null,
        );

        // Gửi mail lỗi thì vẫn cho vào trang xác thực để bấm gửi lại, không làm hỏng việc đăng ký
        rescue(fn () => event(new Registered($user)));

        Auth::login($user);
        $request->session()->regenerate();
        $tracker->record($user, 'register');
        Cookie::queue(Cookie::forget(ReferralService::COOKIE));

        return redirect()->route('verification.notice');
    }
}
