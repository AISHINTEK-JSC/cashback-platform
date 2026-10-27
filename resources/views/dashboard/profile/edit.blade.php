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

    <div class="card mt-6 flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:p-6">
        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.5 4.5 2.5 11.8l6.2 2.2 2.3 6.5 3.4-4.1 5 3.7 2.1-15.6Z"/><path d="m8.7 14 12.8-9.5"/></svg>
        </span>
        <div class="min-w-0 flex-1">
            <p class="flex flex-wrap items-center gap-2 font-semibold text-stone-900">
                Telegram
                @if ($user->telegram_chat_id)
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700"><x-heroicon-s-check-circle class="size-3.5" /> Đã liên kết</span>
                @endif
            </p>
            @if ($user->telegram_chat_id)
                <p class="mt-1 text-sm text-stone-600">
                    Đã liên kết {{ $user->telegram_username ? '@'.$user->telegram_username : 'tài khoản Telegram' }} từ {{ $user->telegram_linked_at?->format('d/m/Y') }}.
                    Bạn nhận thông báo đơn hàng, tiền về ví và có thể gửi link sản phẩm cho <a href="https://t.me/{{ config('services.telegram.bot_username') }}" target="_blank" rel="noopener" class="font-medium text-brand-700 hover:underline">{{ '@'.config('services.telegram.bot_username') }}</a> để lấy link hoàn tiền.
                </p>
                @php($telegramNotificationsOn = $user->telegram_notifications ?? true)
                <form method="POST" action="{{ route('dashboard.telegram.notifications') }}" class="mt-3 flex items-center gap-3">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="enabled" value="{{ $telegramNotificationsOn ? 0 : 1 }}">
                    <button
                        type="submit"
                        role="switch"
                        aria-checked="{{ $telegramNotificationsOn ? 'true' : 'false' }}"
                        aria-label="Nhận thông báo qua Telegram"
                        @class([
                            'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500',
                            'bg-brand-600' => $telegramNotificationsOn,
                            'bg-stone-300' => ! $telegramNotificationsOn,
                        ])
                    >
                        <span @class(['inline-block size-5 rounded-full bg-white shadow transition', 'translate-x-5.5' => $telegramNotificationsOn, 'translate-x-0.5' => ! $telegramNotificationsOn])></span>
                    </button>
                    <span class="text-sm text-stone-700">
                        Nhận thông báo qua Telegram
                        <span class="block text-xs text-stone-500">{{ $telegramNotificationsOn ? 'Đơn mới, tiền về ví, rút tiền và phản hồi hỗ trợ.' : 'Đang tắt. Bot vẫn tạo link và tra cứu như bình thường.' }}</span>
                    </span>
                </form>
            @else
                <p class="mt-1 text-sm text-stone-600">Liên kết với bot {{ '@'.config('services.telegram.bot_username') }} để nhận thông báo đơn hàng, tiền về ví ngay trên Telegram và tạo link hoàn tiền chỉ bằng cách gửi link sản phẩm. Sau khi bấm <span class="font-medium">Start</span> trong Telegram, quay lại và tải lại trang này.</p>
            @endif
        </div>
        @if ($user->telegram_chat_id)
            <form method="POST" action="{{ route('dashboard.telegram.unlink') }}" onsubmit="return confirm('Huỷ liên kết Telegram? Bạn sẽ không nhận thông báo qua Telegram nữa.')" class="shrink-0">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary w-full sm:w-auto">Huỷ liên kết</button>
            </form>
        @else
            <form method="POST" action="{{ route('dashboard.telegram.link') }}" class="shrink-0">
                @csrf
                <button type="submit" class="btn btn-primary w-full sm:w-auto">Liên kết Telegram</button>
            </form>
        @endif
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
