<?php

namespace Meraki\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Meraki\Core\Models\Plugin;

class PluginFactory extends Factory
{
    protected $model = Plugin::class;

    public function definition(): array
    {
        return [
            'name'         => 'meraki-' . $this->faker->unique()->word(),
            'version'      => $this->faker->numerify('#.#.#'),
            'status'       => Plugin::STATUS_ACTIVE,
            'installed_at' => null,
            'meta'         => null,
        ];
    }
}
