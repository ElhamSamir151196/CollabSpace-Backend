<?php

namespace App\Services\Ai;

use App\Enums\AiModule;
use App\Enums\TaskStatus;
use App\Models\Attachment;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\Reports\DashboardService;
use App\Services\Reports\ReportScopeResolver;
use Illuminate\Database\Eloquent\Builder;

/**
 * Collects the workspace data the AI may use to answer — ONLY what this
 * user is allowed to see (same visibility rules as the API), and only the
 * modules the admin allowed. Returned as compact JSON.
 */
class AiContextBuilder
{
    public function __construct(
        private readonly AiSettings $settings,
        private readonly ReportScopeResolver $scopes,
        private readonly DashboardService $dashboard,
    ) {}

    /**
     * Context for the AI chat / Ask AI: the user, their projects, tasks, teams ...
     * $focus narrows everything to one project.
     */
    public function forUser(User $user, ?Project $focus = null): string
    {
        $data = [
            'today' => now()->toDateString(),
            'me' => [
                'name' => $user->name,
                'job_title' => $user->job_title,
                'role' => $user->loadMissing('roles')->role()?->label(),
            ],
        ];

        $projects = Project::query()->visibleTo($user)->when($focus !== null, fn (Builder $q) => $q->whereKey($focus?->id));

        if ($this->settings->allows(AiModule::Projects)) {
            $data['projects'] = $this->projects((clone $projects)->limit((int) config('ai.context.max_projects')));
        }

        $projectIds = (clone $projects)->pluck('id')->all();

        if ($this->settings->allows(AiModule::Tasks)) {
            $data['my_open_tasks'] = $this->tasks(
                Task::query()->whereIn('project_id', $projectIds)
                    ->whereHas('assignees', fn (Builder $users) => $users->whereKey($user->id))
                    ->where('status', '!=', TaskStatus::Completed->value),
            );

            if ($focus !== null) {
                $data['project_tasks'] = $this->tasks(Task::query()->where('project_id', $focus->id));
            }
        }

        if ($this->settings->allows(AiModule::Teams)) {
            $data['my_teams'] = Team::query()
                ->whereHas('members', fn (Builder $members) => $members->whereKey($user->id))
                ->with('members')
                ->limit(10)
                ->get()
                ->map(fn (Team $team) => [
                    'name' => $team->name,
                    'members' => $team->members->map(fn (User $member) => trim($member->name.' ('.($member->membership->position ?? 'member').')'))->all(),
                ])->all();
        }

        if ($this->settings->allows(AiModule::Meetings)) {
            $data['upcoming_meetings'] = $this->meetings(
                Meeting::query()->whereIn('project_id', $projectIds)->where('end_at', '>=', now()),
            );
        }

        if ($this->settings->allows(AiModule::Files)) {
            $data['recent_files'] = $this->files(Attachment::query()->whereIn('project_id', $projectIds));
        }

        if ($this->settings->allows(AiModule::Reports)) {
            $data['task_statistics'] = $this->dashboard->taskStats($this->scopes->forDashboard($user, $focus?->id));
        }

        return $this->encode($data);
    }

    /**
     * Context for project insights: the project itself, its tasks, meetings and files.
     * Not user-specific, so the result can be cached per project.
     */
    public function forProject(Project $project): string
    {
        $data = ['today' => now()->toDateString()];

        $data['project'] = $this->projects(Project::query()->whereKey($project->id))[0] ?? null;

        if ($this->settings->allows(AiModule::Tasks)) {
            $data['tasks'] = $this->tasks(Task::query()->where('project_id', $project->id), limit: 60);
        }

        if ($this->settings->allows(AiModule::Teams)) {
            $data['teams'] = $project->teams()->withCount('members')->get()
                ->map(fn (Team $team) => ['name' => $team->name, 'members' => $team->members_count])->all();
        }

        if ($this->settings->allows(AiModule::Meetings)) {
            $data['meetings'] = $this->meetings(Meeting::query()->where('project_id', $project->id));
        }

        return $this->encode($data);
    }

    /**
     * @param  Builder<Project>  $query
     * @return list<array<string, mixed>>
     */
    private function projects(Builder $query): array
    {
        return $query->with('manager')
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn (Builder $tasks) => $tasks->where('status', TaskStatus::Completed->value),
            ])
            ->get()
            ->map(fn (Project $project) => [
                'name' => $project->name,
                'status' => $project->status->label(),
                'priority' => $project->priority->label(),
                'start_date' => $project->start_date->toDateString(),
                'deadline' => $project->deadline->toDateString(),
                'overdue' => $project->isOverdue(),
                'progress_percent' => $project->progressPercent(),
                'tasks' => (int) $project->tasks_count,
                'completed_tasks' => (int) $project->completed_tasks_count,
                'manager' => $project->manager->name,
            ])->values()->all();
    }

    /**
     * @param  Builder<Task>  $query
     * @return list<array<string, mixed>>
     */
    private function tasks(Builder $query, ?int $limit = null): array
    {
        return $query->with(['project', 'team', 'assignees', 'reviewer'])
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->limit($limit ?? (int) config('ai.context.max_tasks'))
            ->get()
            ->map(fn (Task $task) => [
                'title' => $task->title,
                'project' => $task->project->name,
                'team' => $task->team->name,
                'status' => $task->status->label(),
                'priority' => $task->priority->label(),
                'progress_percent' => $task->progress,
                'due_date' => $task->due_date?->toDateString(),
                'overdue' => $task->isOverdue(),
                'assignees' => $task->assignees->pluck('name')->all(),
                'reviewer' => $task->reviewer?->name,
            ])->values()->all();
    }

    /**
     * @param  Builder<Meeting>  $query
     * @return list<array<string, mixed>>
     */
    private function meetings(Builder $query): array
    {
        return $query->with('project')
            ->orderBy('start_at')
            ->limit((int) config('ai.context.max_meetings'))
            ->get()
            ->map(fn (Meeting $meeting) => [
                'title' => $meeting->title,
                'project' => $meeting->project->name,
                'start' => $meeting->start_at->format('Y-m-d H:i'),
                'state' => $meeting->state(),
            ])->values()->all();
    }

    /**
     * File names only — the AI never reads file contents.
     *
     * @param  Builder<Attachment>  $query
     * @return list<array<string, mixed>>
     */
    private function files(Builder $query): array
    {
        return $query->with(['project', 'uploader'])
            ->latest()
            ->limit((int) config('ai.context.max_files'))
            ->get()
            ->map(fn (Attachment $file) => [
                'name' => $file->original_name,
                'project' => $file->project?->name,
                'uploaded_by' => $file->uploader?->name,
                'uploaded_at' => $file->created_at?->toDateString(),
            ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function encode(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
