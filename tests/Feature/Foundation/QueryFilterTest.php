<?php

use App\Filters\QueryFilter;
use App\Models\User;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

class TestUserFilter extends QueryFilter
{
    protected array $sortable = ['name', 'created_at'];

    protected string $defaultSort = 'name';

    public function search(string $term): void
    {
        $this->whereLike(['name', 'email'], $term);
    }

    public function emailDomain(string $domain): void
    {
        $this->builder->where('email', 'like', '%@'.$domain);
    }

    // Not public -> must never be callable from the request.
    protected function dangerous(): void
    {
        $this->builder->whereRaw('1 = 0');
    }
}

/** @extends BaseRepository<User> */
class TestUserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}

beforeEach(function () {
    User::factory()->create(['name' => 'Sara Hassan', 'email' => 'sara@collab.test']);
    User::factory()->create(['name' => 'Ali Mohamed', 'email' => 'ali@other.test']);
    User::factory()->create(['name' => '100% Done', 'email' => 'done@collab.test']);
});

function filteredNames(array $input): array
{
    return User::query()->filter(new TestUserFilter($input))->pluck('name')->all();
}

it('applies public filter methods mapped from snake_case keys', function () {
    expect(filteredNames(['email_domain' => 'collab.test']))
        ->toBe(['100% Done', 'Sara Hassan']);
});

it('ignores unknown, non-public and empty filters', function () {
    expect(filteredNames(['dangerous' => '1', 'apply' => 'x', 'password' => 'x', 'search' => '']))
        ->toHaveCount(3);
});

it('treats % and _ in search terms literally', function () {
    expect(filteredNames(['search' => '100%']))->toBe(['100% Done'])
        ->and(filteredNames(['search' => '%']))->toBe(['100% Done'])
        ->and(filteredNames(['search' => '_']))->toBe([]);
});

it('sorts only by whitelisted columns', function () {
    expect(filteredNames(['sort' => '-name']))->toBe(['Sara Hassan', 'Ali Mohamed', '100% Done'])
        // "password" is not sortable -> falls back to the default sort (name asc)
        ->and(filteredNames(['sort' => 'password']))->toBe(['100% Done', 'Ali Mohamed', 'Sara Hassan']);
});

it('paginates through the base repository with a filter and caps per_page', function () {
    $repository = app(TestUserRepository::class);

    $page = $repository->paginate(new TestUserFilter(['email_domain' => 'collab.test']), perPage: 1);
    expect($page->total())->toBe(2)->and($page->perPage())->toBe(1);

    expect($repository->paginate(perPage: 5000)->perPage())->toBe(100);
});
