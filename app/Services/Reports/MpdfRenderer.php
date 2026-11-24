<?php

namespace App\Services\Reports;

use App\Interfaces\Reports\PdfRenderer;
use Illuminate\Support\Facades\File;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * mPDF is used because it shapes Arabic text correctly and supports RTL
 * (a note written in Arabic is printed properly in the report).
 */
class MpdfRenderer implements PdfRenderer
{
    public function render(string $html, string $title): string
    {
        $tempDir = storage_path('app/mpdf');
        File::ensureDirectoryExists($tempDir);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDir,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_left' => 14,
            'margin_right' => 14,
            // Pick the right script / font automatically (Arabic, Latin ...).
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->SetTitle($title);
        $mpdf->SetCreator((string) config('app.name'));
        $mpdf->SetFooter('{DATE Y-m-d}||{PAGENO} / {nbpg}');
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
