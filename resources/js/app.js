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
});
