<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Throwaway routes that only exist inside these tests.
    Route::middleware('api')->prefix('api/v1/_test')->group(function () {
        Route::post('validation', fn () => request()->validate(['email' => ['required', 'email']]));
        Route::get('protected', fn () => 'secret')->middleware('auth:sanctum');
        Route::get('forbidden', fn () => abort(403));
        Route::get('crash', fn () => throw new RuntimeException('Database password is 1234'));
    });
});

it('returns the health check in the standard shape', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertExactJson([
            'success' => true,
            'message' => null,
            'data' => ['status' => 'ok', 'version' => 'v1', 'checks' => ['database' => 'up', 'cache' => 'up']],
        ]);
});

it('returns 422 with field errors on validation failure', function () {
    $this->postJson('/api/v1/_test/validation', ['email' => 'not-an-email'])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('data', null)
        ->assertJsonStructure(['errors' => ['email']]);
});

it('returns 401 json even without an Accept header', function () {
    // ForceJsonResponse must stop Laravel from redirecting to a login page.
    $this->get('/api/v1/_test/protected')
        ->assertStatus(401)
        ->assertJson(['success' => false, 'message' => 'Unauthenticated.']);
});

it('returns 403 in the standard shape', function () {
    $this->getJson('/api/v1/_test/forbidden')
        ->assertForbidden()
        ->assertJson(['success' => false, 'message' => 'This action is unauthorized.']);
});

it('returns a generic 404 for unknown routes', function () {
    $this->getJson('/api/v1/does-not-exist')
        ->assertNotFound()
        ->assertJson(['success' => false, 'message' => 'Resource not found.']);
});

it('hides exception details on 500 when debug is off', function () {
    config(['app.debug' => false]);

    $this->getJson('/api/v1/_test/crash')
        ->assertStatus(500)
        ->assertExactJson(['success' => false, 'message' => 'Server error.', 'data' => null]);
});

it('includes exception details on 500 when debug is on', function () {
    config(['app.debug' => true]);

    $this->getJson('/api/v1/_test/crash')
        ->assertStatus(500)
        ->assertJsonPath('errors.exception', RuntimeException::class);
});
