<x-layouts.dashboard title="Hồ sơ & bảo mật">
    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('dashboard.profile.update') }}" enctype="multipart/form-data" class="card space-y-4 p-5">
            @csrf
            @method('PUT')
            <p class="font-semibold text-stone-900">Thông tin cá nhân</p>
            <div class="flex items-center gap-4">
                <span class="grid size-16 place-items-center overflow-hidden rounded-full bg-stone-200 text-xl font-semibold text-stone-700">
                    @if ($user->avatar)<img src="{{ $user->avatar }}" alt="" class="size-full object-cover">@else{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}@endif
                </span>
                <input type="file" name="avatar" accept="image/*" class="text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-stone-100 file:px-3 file:py-2 file:font-medium">
            </div>
            @error('avatar') <p class="field-error">{{ $message }}</p> @enderror
            <x-form.input name="name" label="Họ và tên" :value="$user->name" required />
            <div>
                <label class="label">Email</label>
                <input type="email" value="{{ $user->email }}" class="input bg-stone-50 text-stone-500" disabled>
            </div>
            <x-form.input name="phone" type="tel" label="Số điện thoại" :value="$user->phone" inputmode="numeric" />
            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </form>

        <form method="POST" action="{{ route('dashboard.profile.password') }}" class="card h-fit space-y-4 p-5">
            @csrf
            @method('PUT')
            <p class="font-semibold text-stone-900">{{ $user->password ? 'Đổi mật khẩu' : 'Đặt mật khẩu' }}</p>
            @if ($user->password)
                <x-form.input name="current_password" type="password" label="Mật khẩu hiện tại" autocomplete="current-password" required />
            @else
                <p class="text-sm text-stone-600">Bạn đang đăng nhập bằng Google/Facebook. Đặt mật khẩu để có thể đăng nhập bằng email.</p>
            @endif
            <x-form.input name="password" type="password" label="Mật khẩu mới" autocomplete="new-password" required />
            <x-form.input name="password_confirmation" type="password" label="Nhập lại mật khẩu mới" autocomplete="new-password" required />
            <button type="submit" class="btn btn-primary">Cập nhật mật khẩu</button>
        </form>
    </div>

    <h2 class="mt-8 mb-3 font-semibold text-stone-900">Nhật ký hoạt động gần đây</h2>
    <div class="card overflow-x-auto">
        <table class="table-base min-w-[520px]">
            <thead><tr><th>Thời gian</th><th>Hoạt động</th><th>IP</th></tr></thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr>
                        <td class="whitespace-nowrap text-stone-600">{{ $activity->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ __('activity.'.$activity->action) }}</td>
                        <td class="font-mono text-xs text-stone-500">{{ $activity->ip }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-stone-500">Chưa có hoạt động.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.dashboard>
