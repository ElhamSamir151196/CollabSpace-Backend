<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\AiModule;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Ai\AiManager;
use App\Services\Ai\AiSettings;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Admin > AI settings (singleton resource): /admin/ai-settings.
 * Turn AI on/off, choose the provider / model, and which data the AI may read.
 */
class AiSettingsController extends Controller
{
    public function show(AiSettings $settings, AiManager $manager): JsonResponse
    {
        Gate::authorize(Permission::AiConfigure->value);

        return ApiResponse::success($this->payload($settings, $manager));
    }

    public function update(Request $request, #[CurrentUser] User $user, AiSettings $settings, AiManager $manager): JsonResponse
    {
        Gate::authorize(Permission::AiConfigure->value);

        $validated = $request->validate([
            'enabled' => ['sometimes', 'boolean'],
            'provider' => ['sometimes', 'nullable', Rule::in($manager->available())],
            'model' => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._\-\/]+$/'],
            'allowed_modules' => ['sometimes', 'array'],
            'allowed_modules.*' => ['string', 'distinct', Rule::enum(AiModule::class)],
        ]);

        if (array_key_exists('enabled', $validated)) {
            $validated['enabled'] = (bool) $validated['enabled'];
        }

        $settings->update($validated, $user);

        return ApiResponse::success($this->payload($settings, $manager), __('AI settings saved.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(AiSettings $settings, AiManager $manager): array
    {
        return [
            ...$settings->all(),
            'default_provider' => config('ai.default'),
            'available_providers' => $manager->available(),
            'available_modules' => AiModule::options(),
        ];
    }
}
