<?php

namespace App\Http\Requests\Concerns;

use App\Enums\FileType;
use Illuminate\Http\UploadedFile;

/**
 * Rules for a "files[]" upload field: allowed extensions only, size limit,
 * and a maximum number of files per request (see config/collabspace.php).
 */
trait ValidatesUploadedFiles
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function fileRules(bool $required): array
    {
        return [
            'files' => [$required ? 'required' : 'sometimes', 'array', 'min:1', 'max:'.config('collabspace.files.max_per_upload')],
            'files.*' => [
                'required',
                'file',
                'max:'.config('collabspace.files.max_kb'),
                'extensions:'.implode(',', FileType::allowedExtensions()),
            ],
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function uploadedFiles(): array
    {
        // Validation already guaranteed every item is a real uploaded file.
        $files = $this->file('files', []);

        return array_values(is_array($files) ? $files : [$files]);
    }
}
