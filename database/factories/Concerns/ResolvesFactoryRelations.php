<?php

namespace Database\Factories\Concerns;

use App\Models\User;

trait ResolvesFactoryRelations
{
    protected function randomModelId(string $modelClass, mixed $fallback = null): mixed
    {
        return $modelClass::query()->inRandomOrder()->value('id') ?? $fallback;
    }

    protected function randomUserId(): ?int
    {
        return User::query()->inRandomOrder()->value('id');
    }
}
