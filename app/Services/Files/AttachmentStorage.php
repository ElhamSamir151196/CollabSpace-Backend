<?php

namespace App\Services\Files;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Writes uploaded files to the private disk under a random name
 * (the user's file name is only kept in the database).
 */
class AttachmentStorage
{
    /**
     * @return array{disk: string, path: string, original_name: string, mime_type: string|null, extension: string, size: int}
     */
    public function store(UploadedFile $file, string $directory): array
    {
        $disk = (string) config('collabspace.files.disk');
        $extension = strtolower($file->getClientOriginalExtension());

        $path = $file->storeAs($directory, Str::uuid()->toString().'.'.$extension, ['disk' => $disk]);

        if ($path === false) {
            throw new RuntimeException('The file could not be saved.');
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'original_name' => $this->cleanName($file->getClientOriginalName()),
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'size' => (int) $file->getSize(),
        ];
    }

    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }

    /**
     * Keeps the visible name safe: no folders, no control characters, max 255 chars.
     */
    private function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $name);

        return Str::limit(trim($name) !== '' ? trim($name) : 'file', 255, '');
    }
}
