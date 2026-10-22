<x-auth-card title="Đặt lại mật khẩu">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" label="Email" :value="$email" autocomplete="email" required />
        <x-form.input name="password" type="password" label="Mật khẩu mới" autocomplete="new-password" required />
        <x-form.input name="password_confirmation" type="password" label="Nhập lại mật khẩu mới" autocomplete="new-password" required />
        <button type="submit" class="btn btn-primary w-full">Đặt lại mật khẩu</button>
    </form>
</x-auth-card>
