<x-auth-card title="Tạo tài khoản" subtitle="Miễn phí, chỉ mất 30 giây để bắt đầu nhận hoàn tiền.">
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <x-form.input name="name" label="Họ và tên" autocomplete="name" required autofocus />
        <x-form.input name="email" type="email" label="Email" autocomplete="email" required />
        <x-form.input name="phone" type="tel" label="Số điện thoại (không bắt buộc)" autocomplete="tel" inputmode="numeric" />
        <x-form.input name="password" type="password" label="Mật khẩu" autocomplete="new-password" hint="Tối thiểu 8 ký tự." required />
        <x-form.input name="password_confirmation" type="password" label="Nhập lại mật khẩu" autocomplete="new-password" required />
        <x-form.input name="referral_code" label="Mã giới thiệu (nếu có)" :value="$referralCode" class="uppercase" />
        <label class="flex items-start gap-2 text-sm text-stone-700">
            <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 rounded border-stone-300 text-brand-600 focus:ring-brand-500">
            <span>Tôi đồng ý với <a href="{{ route('pages.terms') }}" target="_blank" class="font-medium text-brand-700 underline">điều khoản sử dụng</a> và <a href="{{ route('pages.privacy') }}" target="_blank" class="font-medium text-brand-700 underline">chính sách bảo mật</a>.</span>
        </label>
        @error('terms') <p class="field-error">{{ $message }}</p> @enderror
        <button type="submit" class="btn btn-primary w-full">Đăng ký</button>
    </form>

    @include('auth.social-buttons')

    <x-slot:footer>
        <p class="mt-6 text-center text-sm text-stone-600">Đã có tài khoản? <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Đăng nhập</a></p>
    </x-slot:footer>
</x-auth-card>
