<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group' => 'site',
            'key' => fake()->unique()->slug(2),
            'value' => fake()->words(3, true),
            'type' => SettingType::String,
            'is_public' => false,
        ];
    }

    public function bool(bool $value = true): self
    {
        return $this->state(fn (): array => [
            'type' => SettingType::Bool,
            'value' => $value ? '1' : '0',
        ]);
    }

    public function int(int $value = 30): self
    {
        return $this->state(fn (): array => [
            'type' => SettingType::Int,
            'value' => (string) $value,
        ]);
    }

    /**
     * @param  array<int|string, mixed>  $value
     */
    public function json(array $value = ['admin', 'login']): self
    {
        return $this->state(fn (): array => [
            'type' => SettingType::Json,
            'value' => json_encode($value),
        ]);
    }

    public function public(): self
    {
        return $this->state(fn (): array => ['is_public' => true]);
    }
}
