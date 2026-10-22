<?php

use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Services\CheckinService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->checkins = app(CheckinService::class);
});

it('rewards the first checkin and credits the available balance', function () {
    $checkin = $this->checkins->checkin($this->user);

    expect($checkin->streak)->toBe(1)
        ->and($checkin->reward_amount)->toBe(100)
        ->and($this->user->wallet->fresh()->available_balance)->toBe(100);
});

it('prevents checking in twice a day', function () {
    $this->checkins->checkin($this->user);
    $this->checkins->checkin($this->user);
})->throws(BusinessRuleException::class, 'đã điểm danh');

it('continues the streak on consecutive days and resets after a gap', function () {
    $this->travelTo(now()->subDays(3));
    $this->checkins->checkin($this->user);
    $this->travelBack();

    $this->travelTo(now()->subDays(2));
    expect($this->checkins->checkin($this->user)->streak)->toBe(2);
    $this->travelBack();

    expect($this->checkins->checkin($this->user)->streak)->toBe(1);
});

it('cycles through the configured rewards', function () {
    expect($this->checkins->rewardFor(7))->toBe(500)
        ->and($this->checkins->rewardFor(8))->toBe(100);
});
