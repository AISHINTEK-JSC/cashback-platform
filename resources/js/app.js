/**
 * Alpine được Livewire nạp sẵn; đăng ký các helper dùng chung ở đây.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('copyable', (text) => ({
        copied: false,
        async copy() {
            try {
                await navigator.clipboard.writeText(text);
            } catch {
                const input = document.createElement('textarea');
                input.value = text;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                input.remove();
            }

            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        },
    }));

    // Nút sáng/tối: lưu lựa chọn vào localStorage "theme"; chưa chọn thì theo hệ điều hành
    window.Alpine.data('themeToggle', () => ({
        isDark: document.documentElement.classList.contains('dark'),
        init() {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
                let theme = null;
                try {
                    theme = localStorage.getItem('theme');
                } catch {}

                if (theme !== 'light' && theme !== 'dark') {
                    this.apply(event.matches);
                }
            });
        },
        toggle() {
            this.apply(!this.isDark);
            try {
                localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
            } catch {}
        },
        apply(isDark) {
            this.isDark = isDark;
            document.documentElement.classList.toggle('dark', isDark);
        },
    }));

    // Ô chọn có tìm kiếm (x-form.searchable-select): so khớp không dấu trên tên, mô tả và mã
    const normalize = (text) =>
        String(text ?? '')
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .replace(/đ/gi, 'd')
            .toLowerCase();

    window.Alpine.data('searchableSelect', (options, selected) => ({
        options: options.map((option) => ({ ...option, search: normalize(`${option.label} ${option.description ?? ''} ${option.value}`) })),
        selected: selected || null,
        open: false,
        query: '',
        active: 0,
        init() {
            this.$watch('query', () => (this.active = 0));
        },
        get selectedOption() {
            return this.options.find((option) => option.value === this.selected) ?? null;
        },
        get filtered() {
            const query = normalize(this.query.trim());

            return query ? this.options.filter((option) => option.search.includes(query)) : this.options;
        },
        toggle() {
            this.open ? this.close() : this.show();
        },
        show() {
            this.query = '';
            this.open = true;
            // Đặt sau $watch('query') (chạy ở tick kế tiếp) để không bị reset về dòng đầu
            this.$nextTick(() => {
                this.active = Math.max(0, this.filtered.findIndex((option) => option.value === this.selected));
                this.$refs.search.focus();
                this.scrollToActive();
            });
        },
        close(focusButton = true) {
            this.open = false;
            if (focusButton) {
                this.$refs.button.focus();
            }
        },
        choose(option) {
            this.selected = option.value;
            this.close();
        },
        chooseActive() {
            const option = this.filtered[this.active];
            if (option) {
                this.choose(option);
            }
        },
        move(step) {
            const count = this.filtered.length;
            if (count === 0) {
                return;
            }

            this.active = (this.active + step + count) % count;
            this.scrollToActive();
        },
        scrollToActive() {
            this.$nextTick(() => this.$refs.list?.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' }));
        },
    }));

    // Nút "Cài ứng dụng": chỉ hiện khi trình duyệt cho cài PWA (Chrome/Edge/Android), Safari iOS không hỗ trợ sự kiện này
    window.Alpine.data('pwaInstall', () => ({
        canInstall: window.deferredInstallPrompt !== undefined,
        init() {
            window.addEventListener('pwa-installable', () => (this.canInstall = true));
            window.addEventListener('appinstalled', () => (this.canInstall = false));
        },
        async install() {
            const prompt = window.deferredInstallPrompt;
            if (!prompt) {
                return;
            }

            prompt.prompt();
            await prompt.userChoice;
            window.deferredInstallPrompt = undefined;
            this.canInstall = false;
        },
    }));
});

// beforeinstallprompt có thể bắn trước khi Alpine khởi động nên giữ lại ở window
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.deferredInstallPrompt = event;
    window.dispatchEvent(new Event('pwa-installable'));
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
