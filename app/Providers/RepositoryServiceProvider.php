<?php

namespace App\Providers;

use App\Interfaces\Repositories\UserRepositoryInterface;
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
    ];
}
