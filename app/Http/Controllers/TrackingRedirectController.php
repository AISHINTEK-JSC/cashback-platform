<?php

namespace App\Http\Controllers;

use App\Models\TrackingLink;
use Illuminate\Http\RedirectResponse;

/**
 * Link trung gian /go/{subId}: đếm click rồi chuyển sang link affiliate của sàn.
 */
class TrackingRedirectController extends Controller
{
    public function __invoke(string $subId): RedirectResponse
    {
        $link = TrackingLink::query()->where('sub_id', $subId)->firstOrFail();

        $link->incrementQuietly('click_count');

        return redirect()->away($link->affiliate_url);
    }
}
