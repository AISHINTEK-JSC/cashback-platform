<?php

namespace Database\Factories;

use App\Models\Platform;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Platform>
 */
class PlatformFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'shopee',
            'name' => 'Shopee',
            'lock_days' => 30,
            'is_active' => true,
        ];
    }

    public function code(string $code, string $name): static
    {
        return $this->state(fn (array $attributes) => ['code' => $code, 'name' => $name]);
    }
}
