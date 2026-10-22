<x-auth-card title="Quên mật khẩu" subtitle="Nhập email đã đăng ký, chúng tôi sẽ gửi link đặt lại mật khẩu.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-form.input name="email" type="email" label="Email" autocomplete="email" required autofocus />
        <button type="submit" class="btn btn-primary w-full">Gửi link đặt lại</button>
    </form>
    <x-slot:footer>
        <p class="mt-6 text-center text-sm text-stone-600"><a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Quay lại đăng nhập</a></p>
    </x-slot:footer>
</x-auth-card>
