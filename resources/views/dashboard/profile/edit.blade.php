<x-layouts.dashboard title="Hồ sơ & bảo mật" description="Quản lý thông tin cá nhân, mật khẩu và theo dõi hoạt động đăng nhập.">
    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('dashboard.profile.update') }}" enctype="multipart/form-data" class="card space-y-4 p-5 sm:p-6">
            @csrf
            @method('PUT')
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-xl bg-brand-50 text-brand-600"><x-heroicon-o-user class="size-5" /></span>
                <p class="font-semibold text-stone-900">Thông tin cá nhân</p>
            </div>
            <div x-data="{ preview: null }" class="flex items-center gap-4 rounded-xl bg-stone-50 p-3">
                <span class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-full bg-brand-100 text-xl font-semibold text-brand-700 ring-4 ring-surface">
                    <img x-show="preview" x-cloak :src="preview" alt="" class="size-full object-cover">
                    @if ($user->avatar)
                        <img x-show="!preview" src="{{ $user->avatar }}" alt="" class="size-full object-cover">
                    @else
                        <span x-show="!preview">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    @endif
                </span>
                <div class="min-w-0">
                    <label for="avatar" class="btn btn-secondary cursor-pointer !py-2"><x-heroicon-o-camera class="size-4" /> Đổi ảnh đại diện</label>
                    <input id="avatar" type="file" name="avatar" accept="image/*" class="sr-only" @change="const f = $event.target.files[0]; preview = f ? URL.createObjectURL(f) : null">
                    <p class="mt-1.5 text-xs text-stone-500">Ảnh JPG, PNG. Bấm "Lưu thay đổi" để cập nhật.</p>
                </div>
            </div>
            @error('avatar') <p class="field-error">{{ $message }}</p> @enderror
            <x-form.input name="name" label="Họ và tên" :value="$user->name" required />
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" type="email" value="{{ $user->email }}" class="input bg-stone-50 text-stone-500" disabled>
            </div>
            <x-form.input name="phone" type="tel" label="Số điện thoại" :value="$user->phone" inputmode="numeric" />
            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </form>

        <form method="POST" action="{{ route('dashboard.profile.password') }}" class="card h-fit space-y-4 p-5 sm:p-6">
            @csrf
            @method('PUT')
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-xl bg-brand-50 text-brand-600"><x-heroicon-o-lock-closed class="size-5" /></span>
                <p class="font-semibold text-stone-900">{{ $user->password ? 'Đổi mật khẩu' : 'Đặt mật khẩu' }}</p>
            </div>
            @if ($user->password)
                <x-form.input name="current_password" type="password" label="Mật khẩu hiện tại" autocomplete="current-password" required />
            @else
                <p class="flex gap-2 rounded-xl bg-sky-50 p-3 text-sm text-sky-900"><x-heroicon-o-information-circle class="size-5 shrink-0" /> Bạn đang đăng nhập bằng Google/Facebook. Đặt mật khẩu để có thể đăng nhập bằng email.</p>
            @endif
            <x-form.input name="password" type="password" label="Mật khẩu mới" autocomplete="new-password" required />
            <x-form.input name="password_confirmation" type="password" label="Nhập lại mật khẩu mới" autocomplete="new-password" required />
            <button type="submit" class="btn btn-primary">Cập nhật mật khẩu</button>
        </form>
    </div>

    <x-section-header title="Nhật ký hoạt động gần đây" description="Nếu thấy hoạt động lạ, hãy đổi mật khẩu ngay." class="mt-8" />
    @if ($activities->isEmpty())
        <x-empty-state icon="shield-check" title="Chưa có hoạt động" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-base min-w-[520px]">
                <thead><tr><th>Thời gian</th><th>Hoạt động</th><th>IP</th></tr></thead>
                <tbody>
                    @foreach ($activities as $activity)
                        <tr class="hover:bg-stone-50">
                            <td class="whitespace-nowrap text-stone-600">{{ $activity->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-stone-900">{{ __('activity.'.$activity->action) }}</td>
                            <td class="font-mono text-xs text-stone-500">{{ $activity->ip }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.dashboard>
