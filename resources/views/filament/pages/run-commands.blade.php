<x-filament-panels::page>
    <x-filament::section
        heading="Lệnh có sẵn"
        description="Mỗi lệnh chạy trực tiếp trên server, tối đa khoảng 5 phút. Lịch chạy tự động (đồng bộ đơn, duyệt đơn, quét gian lận) chỉ hoạt động khi có worker scheduler, nếu không hãy chạy tay ở đây."
    >
        <div class="divide-y divide-gray-200 dark:divide-white/10">
            @foreach (\App\Filament\Pages\RunCommands::COMMANDS as $key => $command)
                <div style="display: flex; align-items: center; gap: 1rem; padding: 0.875rem 0">
                    <div style="flex: 1; min-width: 0">
                        <p style="font-weight: 600">{{ $command['label'] }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $command['description'] }}</p>
                        <code class="text-xs text-gray-500 dark:text-gray-400">{{ \App\Filament\Pages\RunCommands::commandLine($key) }}</code>
                    </div>
                    {{ ($this->runAction)(['command' => $key]) }}
                </div>
            @endforeach
        </div>
    </x-filament::section>

    @if ($lastRun)
        <x-filament::section
            :heading="'Kết quả: '.$lastRun['label']"
            :description="$lastRun['command_line'].' · '.$lastRun['ran_at'].' · '.$lastRun['duration'].' giây · mã thoát '.$lastRun['exit_code']"
            :icon="$lastRun['exit_code'] === 0 ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'"
            :icon-color="$lastRun['exit_code'] === 0 ? 'success' : 'danger'"
        >
            <pre style="max-height: 28rem; overflow: auto; white-space: pre-wrap; word-break: break-word; font-size: 0.8125rem; line-height: 1.5" class="rounded-lg bg-gray-950 p-4 text-gray-100">{{ $lastRun['output'] }}</pre>
        </x-filament::section>
    @endif
</x-filament-panels::page>
