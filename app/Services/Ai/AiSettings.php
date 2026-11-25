<?php

namespace App\Services\Ai;

use App\Enums\AiModule;
use App\Models\AiSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The AI settings an admin controls from the app (SRS: "Only Administrators
 * may configure AI permissions and settings"). Cached; API keys stay in .env.
 *
 * Defaults: AI on, provider/model from .env, every module allowed.
 */
class AiSettings
{
    private const CACHE_KEY = 'ai.settings';

    /**
     * @return array{enabled: bool, provider: string|null, model: string|null, allowed_modules: list<string>}
     */
    public function all(): array
    {
        /** @var array<string, mixed> $stored */
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => AiSetting::query()->pluck('value', 'key')->all());

        $modules = $stored['allowed_modules'] ?? null;

        return [
            'enabled' => (bool) ($stored['enabled'] ?? true),
            'provider' => isset($stored['provider']) ? (string) $stored['provider'] : null,
            'model' => isset($stored['model']) ? (string) $stored['model'] : null,
            'allowed_modules' => is_array($modules) ? array_values(array_map('strval', $modules)) : AiModule::values(),
        ];
    }

    public function enabled(): bool
    {
        return $this->all()['enabled'];
    }

    public function provider(): ?string
    {
        return $this->all()['provider'];
    }

    public function model(): ?string
    {
        return $this->all()['model'];
    }

    public function allows(AiModule $module): bool
    {
        return in_array($module->value, $this->all()['allowed_modules'], true);
    }

    /**
     * @param  array<string, mixed>  $values  only the keys that changed
     */
    public function update(array $values, User $by): void
    {
        DB::transaction(function () use ($values, $by): void {
            foreach ($values as $key => $value) {
                AiSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $by->id]);
            }
        });

        Cache::forget(self::CACHE_KEY);

        activity('ai')->causedBy($by)->event('settings_updated')->withProperties($values)->log('AI settings updated');
    }
}
