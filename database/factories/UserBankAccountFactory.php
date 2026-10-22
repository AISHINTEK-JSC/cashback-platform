<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserBankAccount>
 */
class UserBankAccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bank_code' => 'VCB',
            'bank_name' => 'Vietcombank',
            'account_number' => fake()->numerify('##########'),
            'account_name' => 'NGUYEN VAN A',
            'is_default' => true,
        ];
    }
}
