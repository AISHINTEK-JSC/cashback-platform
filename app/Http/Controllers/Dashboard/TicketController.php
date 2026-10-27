<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Platform;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard.tickets.index', [
            'tickets' => $request->user()->supportTickets()->latest('updated_at')->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        return view('dashboard.tickets.create', [
            'platforms' => Platform::query()->active()->get(),
            'types' => TicketType::cases(),
            'prefill' => $request->only('type', 'platform_order_id'),
            'recentLinks' => $request->user()->trackingLinks()->with('platform')->where('created_at', '>=', now()->subDays(90))->latest()->limit(30)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->input('type') === TicketType::MissingOrder->value) {
            return $this->storeMissingOrder($request);
        }

        $data = $request->validate([
            'type' => ['required', Rule::enum(TicketType::class)],
            'platform_id' => ['nullable', 'integer', 'exists:platforms,id'],
            'platform_order_id' => ['nullable', 'string', 'max:64'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['image', 'max:4096'],
        ]);

        $ticket = DB::transaction(function () use ($request, $data): SupportTicket {
            $ticket = $request->user()->supportTickets()->create([
                'type' => $data['type'],
                'platform_id' => $data['platform_id'] ?? null,
                'platform_order_id' => $data['platform_order_id'] ?? null,
                'subject' => $data['subject'],
                'status' => TicketStatus::Open,
            ]);

            $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $data['message'],
                'attachments' => $this->storeAttachments($request),
            ]);

            return $ticket;
        });

        return redirect()->route('dashboard.tickets.show', $ticket)->with('success', 'Đã gửi yêu cầu hỗ trợ. Chúng tôi sẽ phản hồi sớm nhất.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        return view('dashboard.tickets.show', [
            'ticket' => $ticket->load('messages.user', 'platform'),
            'claimLink' => filled($ticket->details['tracking_link_id'] ?? null)
                ? $request->user()->trackingLinks()->find($ticket->details['tracking_link_id'])
                : null,
        ]);
    }

    /**
     * Khiếu nại thiếu đơn theo mẫu: tự đối chiếu với đơn đã đồng bộ trước khi tạo ticket,
     * lưu ngày mua, số tiền, link đã dùng và kết quả đối chiếu vào details để admin xử lý nhanh.
     */
    private function storeMissingOrder(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'platform_id' => ['required', 'integer', Rule::exists('platforms', 'id')],
            'platform_order_id' => ['required', 'string', 'max:64'],
            'purchased_at' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.now()->subDays(90)->toDateString()],
            'order_amount' => ['nullable', 'integer', 'min:1000', 'max:1000000000'],
            'tracking_link_id' => ['nullable', 'integer', Rule::exists('tracking_links', 'id')->where('user_id', $user->id)],
            'message' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['required', 'array', 'min:1', 'max:3'],
            'attachments.*' => ['image', 'max:4096'],
        ], [
            'platform_id.required' => 'Hãy chọn sàn đã mua.',
            'purchased_at.before_or_equal' => 'Ngày mua không được ở tương lai.',
            'purchased_at.after_or_equal' => 'Chỉ nhận khiếu nại cho đơn mua trong 90 ngày gần nhất.',
            'attachments.required' => 'Hãy đính kèm ảnh chụp đơn hàng (có mã đơn và thời gian đặt).',
        ]);

        $platform = Platform::query()->findOrFail($data['platform_id']);
        $orderId = trim($data['platform_order_id']);
        $purchasedAt = Carbon::parse($data['purchased_at'])->startOfDay();
        $existingOrder = Order::query()->where('platform_id', $platform->id)->where('platform_order_id', $orderId)->first();

        if ($existingOrder && $existingOrder->user_id === $user->id) {
            return redirect()->route('dashboard.orders.show', $existingOrder)->with('success', 'Đơn này đã được ghi nhận vào tài khoản của bạn, không cần khiếu nại.');
        }

        $openClaim = $user->supportTickets()
            ->where('type', TicketType::MissingOrder)
            ->where('platform_id', $platform->id)
            ->where('platform_order_id', $orderId)
            ->where('status', '!=', TicketStatus::Closed)
            ->first();

        if ($openClaim) {
            return redirect()->route('dashboard.tickets.show', $openClaim)->with('success', 'Bạn đã gửi khiếu nại cho đơn này, chúng tôi đang xử lý.');
        }

        if ($purchasedAt->isToday()) {
            return back()->withInput()->withErrors(['purchased_at' => 'Đơn thường được ghi nhận trong vòng 24 giờ sau khi đặt. Hãy đợi đến ngày mai, nếu vẫn chưa thấy đơn thì gửi khiếu nại.']);
        }

        // Link của user trên sàn đó trong 7 ngày trước ngày mua: admin đối chiếu sub_id với báo cáo của sàn
        $relatedLinkIds = $user->trackingLinks()
            ->where('platform_id', $platform->id)
            ->whereBetween('created_at', [$purchasedAt->copy()->subDays(7), $purchasedAt->copy()->endOfDay()])
            ->latest()
            ->limit(5)
            ->pluck('id')
            ->all();

        $details = [
            'purchased_at' => $purchasedAt->toDateString(),
            'order_amount' => $data['order_amount'] ?? null,
            'tracking_link_id' => $data['tracking_link_id'] ?? null,
            'check' => [
                'result' => match (true) {
                    $existingOrder === null => 'not_found',
                    $existingOrder->user_id === null => 'unmatched',
                    default => 'other_user',
                },
                'order_id' => $existingOrder?->id,
                'related_link_ids' => $relatedLinkIds,
            ],
        ];

        $summary = "Khiếu nại thiếu đơn {$platform->name} {$orderId}, mua ngày {$purchasedAt->format('d/m/Y')}"
            .(filled($data['order_amount'] ?? null) ? ', giá trị '.vnd((int) $data['order_amount']) : '').'.';

        $ticket = DB::transaction(function () use ($request, $user, $platform, $orderId, $details, $summary, $data): SupportTicket {
            $ticket = $user->supportTickets()->create([
                'type' => TicketType::MissingOrder,
                'platform_id' => $platform->id,
                'platform_order_id' => $orderId,
                'subject' => "Thiếu đơn {$platform->name} {$orderId}",
                'status' => TicketStatus::Open,
                'details' => $details,
            ]);

            $ticket->messages()->create([
                'user_id' => $user->id,
                'message' => trim($summary."\n\n".($data['message'] ?? '')),
                'attachments' => $this->storeAttachments($request),
            ]);

            return $ticket;
        });

        return redirect()->route('dashboard.tickets.show', $ticket)->with('success', 'Đã gửi khiếu nại thiếu đơn. Chúng tôi sẽ đối chiếu với sàn và phản hồi trong 1-3 ngày làm việc.');
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['image', 'max:4096'],
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $data['message'],
            'attachments' => $this->storeAttachments($request),
        ]);

        $ticket->update(['status' => TicketStatus::Open]);

        return back()->with('success', 'Đã gửi tin nhắn.');
    }

    /**
     * @return list<string>|null
     */
    private function storeAttachments(Request $request): ?array
    {
        $paths = collect($request->file('attachments', []))
            ->map(fn ($file) => $file->store('tickets/'.now()->format('Y/m'), 'uploads'))
            ->values()
            ->all();

        return $paths ?: null;
    }
}
