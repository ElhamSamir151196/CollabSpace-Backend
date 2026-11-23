<?php

namespace App\Http\Controllers\Api\V1\Files;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a file from the private disk.
 * The route is protected by the "signed" middleware: the link was generated
 * for a user who could see the file, and it stops working when it expires.
 *
 * ?inline=1 (only on images / PDF links) shows the file in the browser;
 * everything else is always sent as a download.
 */
class DownloadFileController extends Controller
{
    public function __invoke(Request $request, Attachment $file): StreamedResponse
    {
        $disk = Storage::disk($file->disk);

        abort_unless($disk->exists($file->path), 404);

        $inline = $request->boolean('inline') && $file->type()->isPreviewable($file->extension);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, no-store',
        ];

        if ($file->mime_type !== null) {
            $headers['Content-Type'] = $file->mime_type;
        }

        return $disk->response(
            $file->path,
            $file->original_name,
            $headers,
            $inline ? 'inline' : 'attachment',
        );
    }
}
