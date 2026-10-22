<x-layouts.dashboard title="Gửi yêu cầu hỗ trợ">
    <form method="POST" action="{{ route('dashboard.tickets.store') }}" enctype="multipart/form-data" class="card max-w-2xl space-y-4 p-5 sm:p-6" x-data="{ type: @js(old('type', $prefill['type'] ?? 'missing_order')) }">
        @csrf
        <div>
            <label for="type" class="label">Loại yêu cầu</label>
            <select id="type" name="type" class="input" x-model="type">
                @foreach ($types as $type)
                    <option value="{{ $type->value }}">{{ $type->getLabel() }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid gap-4 sm:grid-cols-2" x-show="type === 'missing_order' || type === 'wrong_amount'">
            <div>
                <label for="platform_id" class="label">Sàn</label>
                <select id="platform_id" name="platform_id" class="input">
                    <option value="">Chọn sàn</option>
                    @foreach ($platforms as $platform)
                        <option value="{{ $platform->id }}" @selected((string) old('platform_id') === (string) $platform->id)>{{ $platform->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-form.input name="platform_order_id" label="Mã đơn hàng trên sàn" :value="$prefill['platform_order_id'] ?? null" />
        </div>

        <x-form.input name="subject" label="Tiêu đề" required />

        <div>
            <label for="message" class="label">Nội dung</label>
            <textarea id="message" name="message" rows="5" class="input" required placeholder="Mô tả chi tiết: thời gian mua, link đã dùng, số tiền...">{{ old('message') }}</textarea>
            @error('message') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="attachments" class="label">Ảnh chụp màn hình (tối đa 3 ảnh)</label>
            <input id="attachments" type="file" name="attachments[]" accept="image/*" multiple class="block w-full text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-stone-100 file:px-3 file:py-2 file:font-medium">
            @error('attachments.*') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Gửi yêu cầu</button>
    </form>
</x-layouts.dashboard>
