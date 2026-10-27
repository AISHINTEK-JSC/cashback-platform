<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Models\TrackingLink;
use App\Services\ActivityLogger;
use App\Services\TrackingLinkService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Ô "Dán link → Lấy link hoàn tiền" ở trang chủ và dashboard.
 */
class LinkGenerator extends Component
{
    #[Validate('required|string|max:2000', message: ['required' => 'Hãy dán link sản phẩm.'])]
    public string $url = '';

    public ?int $linkId = null;

    public bool $compact = false;

    /**
     * Link được chia sẻ từ app sàn (?link=...&auto=1): tạo luôn khi trang vừa tải, không cần bấm nút.
     */
    public bool $autoGenerate = false;

    public function mount(): void
    {
        $this->url = (string) request()->query('link', '');
        $this->autoGenerate = $this->url !== '' && request()->boolean('auto');
    }

    public function generateShared(TrackingLinkService $links, ActivityLogger $activity): void
    {
        if (! $this->autoGenerate) {
            return;
        }

        $this->autoGenerate = false;
        // Bỏ ?link=&auto= khỏi thanh địa chỉ để tải lại trang không tạo thêm link trùng
        $this->js("history.replaceState(null, '', location.pathname)");
        $this->generate($links, $activity);
    }

    public function generate(TrackingLinkService $links, ActivityLogger $activity): void
    {
        $user = auth()->user();

        if (! $user) {
            session()->put('url.intended', url()->previous());
            $this->redirectRoute('login');

            return;
        }

        if (! $user->hasVerifiedEmail()) {
            $this->redirectRoute('verification.notice');

            return;
        }

        $this->validate();

        $key = 'link-generate:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 30)) {
            $this->addError('url', 'Bạn tạo link quá nhanh, vui lòng thử lại sau ít phút.');

            return;
        }

        RateLimiter::hit($key, 600);

        try {
            $link = $links->create($user, $this->url);
        } catch (BusinessRuleException $e) {
            $this->addError('url', $e->getMessage());

            return;
        }

        $activity->log($user, 'link.create', ['tracking_link_id' => $link->id]);
        $this->linkId = $link->id;
        $this->dispatch('cashback-link-created', platform: $link->platform?->code);
    }

    public function resetForm(): void
    {
        $this->reset('url', 'linkId');
    }

    public function render(): View
    {
        return view('livewire.link-generator', [
            'link' => $this->linkId ? TrackingLink::query()->with('platform')->where('user_id', auth()->id())->find($this->linkId) : null,
        ]);
    }
}
