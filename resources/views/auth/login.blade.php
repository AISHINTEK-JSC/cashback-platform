<x-auth-card title="Đăng nhập" subtitle="Chào mừng bạn quay lại!">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-form.input name="login" label="Email hoặc số điện thoại" autocomplete="username" required autofocus />
        <x-form.input name="password" type="password" label="Mật khẩu" autocomplete="current-password" required />
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-stone-700">
                <input type="checkbox" name="remember" class="rounded border-stone-300 text-brand-600 focus:ring-brand-500"> Ghi nhớ đăng nhập
            </label>
            <a href="{{ route('password.request') }}" class="font-medium text-brand-700 hover:underline">Quên mật khẩu?</a>
        </div>
        <button type="submit" class="btn btn-primary w-full">Đăng nhập</button>
    </form>

    @include('auth.social-buttons')

    <x-slot:footer>
        <p class="mt-6 text-center text-sm text-stone-600">Chưa có tài khoản? <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">Đăng ký miễn phí</a></p>
    </x-slot:footer>
</x-auth-card>
