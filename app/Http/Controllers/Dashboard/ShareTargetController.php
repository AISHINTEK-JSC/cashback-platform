<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Nhận nội dung chia sẻ từ app Shopee/TikTok/Lazada qua Web Share Target của PWA (share_target trong site.webmanifest).
 * App thường gửi link lẫn trong đoạn chữ ("Xem sản phẩm này... https://s.shopee.vn/abc"), nên tách link ra rồi tự tạo link hoàn tiền.
 */
class ShareTargetController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $sharedUrl = $this->extractUrl(implode(' ', array_filter([
            $request->string('url')->toString(),
            $request->string('text')->toString(),
            $request->string('title')->toString(),
        ])));

        if ($sharedUrl === null) {
            return redirect()->route('dashboard.links.index')->with('error', 'Không tìm thấy link sản phẩm trong nội dung được chia sẻ. Hãy chọn "Sao chép liên kết" trong app rồi dán vào ô bên dưới.');
        }

        return redirect()->route('dashboard.links.index', ['link' => $sharedUrl, 'auto' => 1]);
    }

    private function extractUrl(string $sharedText): ?string
    {
        if (! preg_match('~https?://[^\s<>"\']+~iu', $sharedText, $matches)) {
            return null;
        }

        return rtrim($matches[0], '.,;:!?)]}»…');
    }
}
