<?php

namespace App\Interfaces\Reports;

/**
 * Turns an HTML document into PDF bytes.
 * Bound to MpdfRenderer in AppServiceProvider; swap the binding to change the engine.
 */
interface PdfRenderer
{
    public function render(string $html, string $title): string;
}
