<?php

use App\Enums\TicketType;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\TrackingLink;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('uploads');
    $this->platform = shopeeWithDefaultRule();
    $this->user = User::factory()->create();
});

function missingOrderClaim(array $overrides = []): array
{
    return [
        'type' => 'missing_order',
        'platform_id' => test()->platform->id,
        'platform_order_id' => '241020ABC',
        'purchased_at' => now()->subDays(2)->toDateString(),
        'order_amount' => 250000,
        'message' => 'Mua 2 áo, có dùng mã giảm giá.',
        'attachments' => [UploadedFile::fake()->image('don-hang.jpg')],
        ...$overrides,
    ];
}

it('files a structured missing order claim with the links used around the purchase date', function () {
    $usedLink = TrackingLink::factory()->for($this->user)->for($this->platform)->create(['created_at' => now()->subDays(3)]);
    $oldLink = TrackingLink::factory()->for($this->user)->for($this->platform)->create(['created_at' => now()->subDays(30)]);

    $this->actingAs($this->user)
        ->post(route('dashboard.tickets.store'), missingOrderClaim(['tracking_link_id' => $usedLink->id]))
        ->assertRedirect();

    $ticket = SupportTicket::sole();
    expect($ticket->type)->toBe(TicketType::MissingOrder)
        ->and($ticket->subject)->toBe("Thiếu đơn {$this->platform->name} 241020ABC")
        ->and($ticket->details['purchased_at'])->toBe(now()->subDays(2)->toDateString())
        ->and($ticket->details['order_amount'])->toBe(250000)
        ->and($ticket->details['tracking_link_id'])->toBe($usedLink->id)
        ->and($ticket->details['check']['result'])->toBe('not_found')
        ->and($ticket->details['check']['related_link_ids'])->toBe([$usedLink->id])
        ->and($ticket->messages()->sole()->attachments)->toHaveCount(1);

    $this->get(route('dashboard.tickets.show', $ticket))
        ->assertSee('241020ABC')
        ->assertSee(vnd(250000))
        ->assertSee($usedLink->product_name);
});

it('sends members to the order when it is already recorded for them', function () {
    $order = Order::factory()->for($this->platform)->for($this->user)->create(['platform_order_id' => '241020ABC']);

    $this->actingAs($this->user)
        ->post(route('dashboard.tickets.store'), missingOrderClaim())
        ->assertRedirect(route('dashboard.orders.show', $order));

    expect(SupportTicket::count())->toBe(0);
});

it('flags orders that are unmatched or belong to someone else for the admin', function () {
    $unmatched = Order::factory()->for($this->platform)->create(['platform_order_id' => '241020ABC', 'user_id' => null]);

    $this->actingAs($this->user)->post(route('dashboard.tickets.store'), missingOrderClaim());

    expect(SupportTicket::sole()->details['check'])->toMatchArray(['result' => 'unmatched', 'order_id' => $unmatched->id]);

    Order::factory()->for($this->platform)->for(User::factory())->create(['platform_order_id' => 'KHAC999']);
    $this->post(route('dashboard.tickets.store'), missingOrderClaim(['platform_order_id' => 'KHAC999']));

    expect(SupportTicket::query()->where('platform_order_id', 'KHAC999')->sole()->details['check']['result'])->toBe('other_user');
});

it('asks members to wait for orders placed today', function () {
    $this->actingAs($this->user)
        ->post(route('dashboard.tickets.store'), missingOrderClaim(['purchased_at' => now()->toDateString()]))
        ->assertSessionHasErrors('purchased_at');

    expect(SupportTicket::count())->toBe(0);
});

it('requires a screenshot and a purchase date within 90 days', function () {
    $this->actingAs($this->user)
        ->post(route('dashboard.tickets.store'), missingOrderClaim(['attachments' => [], 'purchased_at' => now()->subDays(120)->toDateString()]))
        ->assertSessionHasErrors(['attachments', 'purchased_at']);
});

it('does not open a second claim for the same order', function () {
    $this->actingAs($this->user)->post(route('dashboard.tickets.store'), missingOrderClaim());
    $first = SupportTicket::sole();

    $this->post(route('dashboard.tickets.store'), missingOrderClaim())->assertRedirect(route('dashboard.tickets.show', $first));

    expect(SupportTicket::count())->toBe(1);
});

it('rejects links that belong to another member', function () {
    $foreignLink = TrackingLink::factory()->for(User::factory())->for($this->platform)->create();

    $this->actingAs($this->user)
        ->post(route('dashboard.tickets.store'), missingOrderClaim(['tracking_link_id' => $foreignLink->id]))
        ->assertSessionHasErrors('tracking_link_id');
});

it('shows the claim check to admins', function () {
    $link = TrackingLink::factory()->for($this->user)->for($this->platform)->create(['created_at' => now()->subDays(3)]);
    $this->actingAs($this->user)->post(route('dashboard.tickets.store'), missingOrderClaim(['tracking_link_id' => $link->id]));

    $this->actingAs(User::factory()->admin()->create())
        ->get('/super/admin/support-tickets/'.SupportTicket::sole()->id)
        ->assertSuccessful()
        ->assertSee('Khiếu nại thiếu đơn')
        ->assertSee('Không tìm thấy đơn')
        ->assertSee($link->sub_id);
});
