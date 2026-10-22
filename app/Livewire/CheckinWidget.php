<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Services\CheckinService;
use Illuminate\View\View;
use Livewire\Component;

class CheckinWidget extends Component
{
    public ?string $message = null;

    public function checkin(CheckinService $checkins): void
    {
        try {
            $checkin = $checkins->checkin(auth()->user());
            $this->message = 'Điểm danh thành công! +'.vnd($checkin->reward_amount);
            $this->dispatch('wallet-updated');
        } catch (BusinessRuleException $e) {
            $this->message = $e->getMessage();
        }
    }

    public function render(CheckinService $checkins): View
    {
        $user = auth()->user();
        $streak = $checkins->currentStreak($user);
        $checkedIn = $checkins->hasCheckedInToday($user);

        // Hiển thị 7 ô thưởng của chu kỳ hiện tại
        $cycleStart = intdiv(max($streak - ($checkedIn ? 1 : 0), 0), 7) * 7;
        $days = collect(range(1, 7))->map(fn (int $i) => [
            'day' => $cycleStart + $i,
            'reward' => $checkins->rewardFor($cycleStart + $i),
            'done' => $cycleStart + $i <= $streak,
        ]);

        return view('livewire.checkin-widget', compact('streak', 'checkedIn', 'days'));
    }
}
