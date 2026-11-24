<?php

namespace App\Providers;

use App\Interfaces\Repositories\AttachmentRepositoryInterface;
use App\Interfaces\Repositories\ConversationRepositoryInterface;
use App\Interfaces\Repositories\MessageRepositoryInterface;
use App\Interfaces\Repositories\ProjectRepositoryInterface;
use App\Interfaces\Repositories\TaskRepositoryInterface;
use App\Interfaces\Repositories\TeamRepositoryInterface;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Repositories\Eloquent\AttachmentRepository;
use App\Repositories\Eloquent\ConversationRepository;
use App\Repositories\Eloquent\MessageRepository;
use App\Repositories\Eloquent\ProjectRepository;
use App\Repositories\Eloquent\TaskRepository;
use App\Repositories\Eloquent\TeamRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Binds every repository interface to its Eloquent implementation.
 * Controllers / Actions only type-hint the interface, so the
 * implementation can be swapped (or mocked in tests) in one place.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Interface => implementation.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        TeamRepositoryInterface::class => TeamRepository::class,
        ProjectRepositoryInterface::class => ProjectRepository::class,
        TaskRepositoryInterface::class => TaskRepository::class,
        AttachmentRepositoryInterface::class => AttachmentRepository::class,
        ConversationRepositoryInterface::class => ConversationRepository::class,
        MessageRepositoryInterface::class => MessageRepository::class,
    ];
}
