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
