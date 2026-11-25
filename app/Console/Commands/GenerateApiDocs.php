<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Knuckles\Scribe\Commands\GenerateDocumentation;

/**
 * Builds the API documentation (HTML + OpenAPI + Postman) with Scribe into
 * the folder set in config/scribe.php (default ../../Docs/api, outside the
 * repository). Scribe cannot replace a folder outside the project itself,
 * so the old output is removed first. Development only (Scribe is a dev package).
 */
#[Signature('docs:generate')]
#[Description('Generate the API documentation (outside the repository)')]
class GenerateApiDocs extends Command
{
    public function handle(): int
    {
        if (! class_exists(GenerateDocumentation::class)) {
            $this->error('Scribe is not installed (run composer install with dev packages).');

            return self::FAILURE;
        }

        $output = (string) config('scribe.static.output_path');
        $path = str_starts_with($output, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $output) === 1 ? $output : base_path($output);

        File::deleteDirectory($path);

        $result = $this->call('scribe:generate', ['--force' => true, '--no-interaction' => true]);

        if ($result === self::SUCCESS) {
            $this->info('Docs: '.(realpath($path) ?: $path).DIRECTORY_SEPARATOR.'index.html');
        }

        return $result;
    }
}
