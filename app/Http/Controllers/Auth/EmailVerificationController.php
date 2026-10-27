<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Xác thực email cho tài khoản đăng ký bằng email; tài khoản Google đã được xác thực sẵn khi tạo.
 */
class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard.index'));
        }

        return view('auth.verify-email', ['email' => $request->user()->email]);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->intended(route('dashboard.index'))->with('success', 'Email đã được xác thực. Dán link sản phẩm để bắt đầu nhận hoàn tiền.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard.index'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Đã gửi lại email xác thực, vui lòng kiểm tra hộp thư.');
    }
}
