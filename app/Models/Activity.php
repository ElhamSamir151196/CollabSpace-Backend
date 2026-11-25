<?php

namespace App\Models;

use App\Traits\Filterable;
use Spatie\Activitylog\Models\Activity as BaseActivity;

/**
 * One audit log entry (spatie/laravel-activitylog), with filtering for Admin > Activity log.
 * Registered as "activity_model" in config/activitylog.php.
 */
class Activity extends BaseActivity
{
    use Filterable;

    /**
     * Short names the API uses for "subject_type" (stored as class names).
     *
     * @var array<string, class-string>
     */
    public const SUBJECT_TYPES = [
        'user' => User::class,
        'team' => Team::class,
        'project' => Project::class,
        'task' => Task::class,
        'meeting' => Meeting::class,
        'message' => Message::class,
        'file' => Attachment::class,
    ];

    /**
     * "project" for App\Models\Project, or null for unknown / no subject.
     */
    public function subjectAlias(): ?string
    {
        $alias = array_search($this->subject_type, self::SUBJECT_TYPES, true);

        return $alias === false ? null : $alias;
    }
}
