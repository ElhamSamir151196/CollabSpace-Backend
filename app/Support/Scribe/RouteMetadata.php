<?php

namespace App\Support\Scribe;

use Illuminate\Support\Str;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;

/**
 * Scribe metadata from the route itself (dev only, used by "php artisan scribe:generate"):
 *  - group:  the feature, from the URL ("/api/v1/projects/..." -> "Projects",
 *            "/api/v1/admin/users" -> "Admin - Users");
 *  - title:  from the route name when the method has no doc comment
 *            ("v1.projects.teams.store" -> "Create projects teams");
 *  - auth:   true when the route uses "auth:sanctum".
 * A @group / @authenticated tag in a doc comment still wins.
 */
class RouteMetadata extends Strategy
{
    private const ACTIONS = [
        'index' => 'List',
        'store' => 'Create',
        'show' => 'Show',
        'update' => 'Update',
        'destroy' => 'Delete',
    ];

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function __invoke(ExtractedEndpointData $endpointData, array $settings = []): array
    {
        $route = $endpointData->route;
        $metadata = [];

        $current = $endpointData->metadata->groupName;
        if ($current === null || $current === '' || $current === config('scribe.groups.default')) {
            $metadata['groupName'] = $this->group($route->uri());
        }

        if (blank($endpointData->metadata->title) && $route->getName() !== null) {
            $metadata['title'] = $this->title($route->getName());
        }

        if (in_array('auth:sanctum', $route->gatherMiddleware(), true)) {
            $metadata['authenticated'] = true;
        }

        return $metadata;
    }

    private function group(string $uri): string
    {
        $segments = array_values(array_filter(explode('/', Str::after($uri, 'api/v1/')), fn ($s) => ! str_starts_with($s, '{')));
        $first = $segments[0] ?? 'general';

        if ($first === 'admin') {
            return 'Admin - '.$this->headline($segments[1] ?? 'general');
        }

        return $this->headline($first);
    }

    /** "ai-settings" -> "AI Settings". */
    private function headline(string $value): string
    {
        return (string) preg_replace('/\bAi\b/', 'AI', Str::headline($value));
    }

    private function title(string $name): string
    {
        $parts = explode('.', Str::after($name, 'v1.'));
        $action = array_pop($parts);

        $title = isset(self::ACTIONS[$action])
            ? self::ACTIONS[$action].' '.Str::lower(Str::headline(implode(' ', $parts)))
            : Str::ucfirst(Str::lower(Str::headline(implode(' ', [...$parts, $action]))));

        return (string) preg_replace('/\b[Aa]i\b/', 'AI', $title);
    }
}
