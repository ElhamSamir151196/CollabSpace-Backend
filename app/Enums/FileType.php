<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * File category, from the extension. Used for the icon in the UI and the "type" filter.
 */
enum FileType: string
{
    use HasEnumHelpers;

    case Image = 'image';
    case Document = 'document';
    case Spreadsheet = 'spreadsheet';
    case Presentation = 'presentation';
    case Design = 'design';
    case Code = 'code';
    case Archive = 'archive';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::Document => 'Document',
            self::Spreadsheet => 'Spreadsheet',
            self::Presentation => 'Presentation',
            self::Design => 'Design',
            self::Code => 'Code',
            self::Archive => 'Archive',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<string>
     */
    public function extensions(): array
    {
        return match ($this) {
            self::Image => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            self::Document => ['pdf', 'doc', 'docx', 'txt', 'md', 'rtf'],
            self::Spreadsheet => ['xls', 'xlsx', 'csv'],
            self::Presentation => ['ppt', 'pptx'],
            self::Design => ['fig', 'psd', 'ai', 'xd'],
            // No server-side script types (php, html ...) on purpose.
            self::Code => ['sql', 'json', 'js', 'jsx', 'ts', 'tsx', 'css', 'xml', 'yml', 'yaml'],
            self::Archive => ['zip', 'rar', '7z'],
            self::Other => [],
        };
    }

    public static function fromExtension(string $extension): self
    {
        $extension = strtolower($extension);

        foreach (self::cases() as $type) {
            if (in_array($extension, $type->extensions(), true)) {
                return $type;
            }
        }

        return self::Other;
    }

    /**
     * Every extension users may upload (all categories).
     *
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return array_merge(...array_map(fn (self $type) => $type->extensions(), self::cases()));
    }

    /**
     * Files the browser may display inline (preview); everything else is a download.
     */
    public function isPreviewable(string $extension): bool
    {
        return $this === self::Image || strtolower($extension) === 'pdf';
    }
}
