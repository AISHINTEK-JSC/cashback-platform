<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Http\Controllers\Controller;
use App\Models\Platform;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(TicketType::class)],
            'platform_id' => ['nullable', 'integer', 'exists:platforms,id'],
            'platform_order_id' => ['nullable', 'string', 'max:64', Rule::requiredIf($request->input('type') === TicketType::MissingOrder->value)],
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
        ]);
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
            ->map(fn ($file) => $file->store('tickets/'.now()->format('Y/m'), 'public'))
            ->values()
            ->all();

        return $paths ?: null;
    }
}
