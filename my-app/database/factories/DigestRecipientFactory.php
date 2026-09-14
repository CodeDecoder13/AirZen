<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DigestRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DigestRecipient> */
final class DigestRecipientFactory extends Factory
{
    protected $model = DigestRecipient::class;

    public function definition(): array
    {
        return ['email' => $this->faker->unique()->safeEmail()];
    }
}
