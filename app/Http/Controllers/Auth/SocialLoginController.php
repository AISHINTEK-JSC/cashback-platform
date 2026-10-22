<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\LoginTracker;
use App\Services\Auth\RegisterUser;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SocialLoginController extends Controller
{
    public const PROVIDERS = ['google', 'facebook'];

    public function redirect(string $provider): SymfonyRedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider, RegisterUser $register, LoginTracker $tracker): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors(['login' => 'Đăng nhập '.ucfirst($provider).' không thành công, vui lòng thử lại.']);
        }

        if (! $socialUser->getEmail()) {
            return redirect()->route('login')->withErrors(['login' => 'Tài khoản '.ucfirst($provider).' chưa có email. Vui lòng đăng ký bằng email.']);
        }

        [$user, $isNew] = DB::transaction(function () use ($provider, $socialUser, $register, $request): array {
            $account = SocialAccount::query()->where('provider', $provider)->where('provider_id', $socialUser->getId())->first();

            if ($account) {
                return [$account->user, false];
            }

            $user = User::query()->where('email', $socialUser->getEmail())->first();
            $isNew = $user === null;

            $user ??= $register->handle([
                'name' => $socialUser->getName() ?: $socialUser->getEmail(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
                'email_verified_at' => now(),
            ], $request->cookie(ReferralService::COOKIE));

            $user->socialAccounts()->create(['provider' => $provider, 'provider_id' => $socialUser->getId()]);

            return [$user, $isNew];
        });

        if (! $user->isActive()) {
            return redirect()->route('login')->withErrors(['login' => 'Tài khoản của bạn đã bị khoá. Vui lòng liên hệ hỗ trợ.']);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $tracker->record($user, $isNew ? 'register' : 'login');

        return redirect()->intended(route('dashboard.index'));
    }
}
