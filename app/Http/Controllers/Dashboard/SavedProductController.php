<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SavedProduct;
use App\Models\TrackingLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedProductController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard.saved-products.index', [
            'products' => $request->user()->savedProducts()->with('platform')->latest()->paginate(24),
        ]);
    }

    /**
     * Lưu sản phẩm từ một link hoàn tiền đã tạo.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['tracking_link_id' => ['required', 'integer']]);

        $link = TrackingLink::query()->whereKey($data['tracking_link_id'])->where('user_id', $request->user()->id)->firstOrFail();

        $request->user()->savedProducts()->updateOrCreate(
            ['platform_id' => $link->platform_id, 'product_id' => $link->product_id ?? $link->sub_id],
            [
                'name' => $link->product_name ?? 'Sản phẩm',
                'image' => $link->product_image,
                'price' => $link->price,
                'url' => $link->original_url,
            ],
        );

        return back()->with('success', 'Đã lưu sản phẩm.');
    }

    public function destroy(Request $request, SavedProduct $savedProduct): RedirectResponse
    {
        abort_unless($savedProduct->user_id === $request->user()->id, 404);

        $savedProduct->delete();

        return back()->with('success', 'Đã bỏ lưu sản phẩm.');
    }
}
