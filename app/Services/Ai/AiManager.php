<?php

namespace App\Services\Ai;

use App\Exceptions\AiUnavailableException;
use App\Interfaces\Ai\AiProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;

/**
 * Factory for AI providers (Strategy pattern):
 *   provider name -> config/ai.php "providers.<name>" -> driver class.
 * The admin's choice in the app (provider / model) wins over .env.
 */
class AiManager
{
    public function __construct(
        private readonly Container $container,
        private readonly AiSettings $settings,
    ) {}

    public function driver(?string $name = null): AiProvider
    {
        $name ??= $this->settings->provider() ?? (string) config('ai.default');

        /** @var array<string, mixed>|null $config */
        $config = config("ai.providers.{$name}");

        if (! is_array($config) || ! isset($config['driver']) || ! is_a($config['driver'], AiProvider::class, true)) {
            Log::error("AI provider [{$name}] is not configured in config/ai.php.");

            throw new AiUnavailableException;
        }

        if ($this->settings->model() !== null && $name === $this->settings->provider()) {
            $config['model'] = $this->settings->model();
        }

        /** @var AiProvider */
        return $this->container->make($config['driver'], ['config' => $config]);
    }

    /**
     * @return list<string>
     */
    public function available(): array
    {
        return array_keys((array) config('ai.providers'));
    }
}
