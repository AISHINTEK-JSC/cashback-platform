<x-auth-card title="Xác thực email" subtitle="Còn một bước nữa để bắt đầu nhận hoàn tiền.">
    <div class="flex flex-col items-center text-center">
        <span class="grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-600 ring-8 ring-brand-50/50">
            <x-heroicon-o-envelope-open class="size-7" />
        </span>
        <p class="mt-5 text-sm text-stone-600">
            Chúng tôi đã gửi link xác thực tới <span class="font-semibold text-stone-900">{{ $email }}</span>.
            Mở email và bấm <span class="font-semibold text-stone-900">Xác thực địa chỉ email</span> để kích hoạt tài khoản.
        </p>
        <p class="mt-2 text-xs text-stone-500">Không thấy email? Kiểm tra thư mục Spam/Quảng cáo. Link có hiệu lực trong 60 phút.</p>
    </div>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <button type="submit" class="btn btn-primary w-full">
            <x-heroicon-o-arrow-path class="size-5" /> Gửi lại email xác thực
        </button>
    </form>

    <x-slot:footer>
        <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center text-sm text-stone-600">
            @csrf
            Sai email? <button type="submit" class="font-semibold text-brand-700 hover:underline">Đăng xuất và đăng ký lại</button>
        </form>
    </x-slot:footer>
</x-auth-card>
