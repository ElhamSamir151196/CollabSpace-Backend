<?php

use App\Enums\UserRole;
use App\Services\Ai\AiManager;

it('shows the AI settings to admins only', function () {
    actingAsRole(UserRole::ProjectManager);
    $this->getJson('/api/v1/admin/ai-settings')->assertForbidden();
    $this->putJson('/api/v1/admin/ai-settings', ['enabled' => false])->assertForbidden();

    actingAsRole(UserRole::Admin);
    $this->getJson('/api/v1/admin/ai-settings')
        ->assertOk()
        ->assertJsonPath('data.enabled', true)
        ->assertJsonPath('data.available_providers', ['gemini', 'fake']);
});

it('lets the admin change the provider, model and modules', function () {
    actingAsRole(UserRole::Admin);

    $this->putJson('/api/v1/admin/ai-settings', [
        'enabled' => false,
        'provider' => 'gemini',
        'model' => 'gemini-2.5-pro',
        'allowed_modules' => ['projects', 'tasks'],
    ])
        ->assertOk()
        ->assertJsonPath('data.enabled', false)
        ->assertJsonPath('data.provider', 'gemini')
        ->assertJsonPath('data.allowed_modules', ['projects', 'tasks']);

    $driver = app(AiManager::class)->driver();
    expect($driver->name())->toBe('gemini')->and($driver->model())->toBe('gemini-2.5-pro');

    $this->getJson('/api/v1/ai/status')->assertJsonPath('data.enabled', false);
});

it('validates the AI settings', function () {
    actingAsRole(UserRole::Admin);

    $this->putJson('/api/v1/admin/ai-settings', [
        'provider' => 'skynet',
        'model' => 'bad model!',
        'allowed_modules' => ['projects', 'salaries'],
    ])->assertUnprocessable()->assertJsonValidationErrors(['provider', 'model', 'allowed_modules.1']);
});
