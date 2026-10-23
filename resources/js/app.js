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
});
