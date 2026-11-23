<?php

namespace App\Services\Project;

use App\Interfaces\Repositories\ProjectRepositoryInterface;
use Illuminate\Support\Str;

/**
 * "Alpha"          -> "alpha"
 * "Alpha" again    -> "alpha-2", "alpha-3" ...
 * "مشروع" (no Latin letters) -> "project", "project-2" ...
 */
class ProjectSlugGenerator
{
    private const MAX_LENGTH = 170;

    public function __construct(private readonly ProjectRepositoryInterface $projects) {}

    public function generate(string $name): string
    {
        $base = Str::limit(Str::slug($name), self::MAX_LENGTH, '');
        $base = $base !== '' ? $base : 'project';

        $slug = $base;
        $suffix = 2;

        while ($this->projects->slugExists($slug)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
