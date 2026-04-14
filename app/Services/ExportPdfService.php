<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ExportPdfService
{
    public function download(
        string $view,
        array $data,
        string $filename,
        string $orientation = 'portrait',
        string $paper = 'a4'
    ): Response {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper($paper, $orientation);

        return response()->streamDownload(
            static fn () => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function stream(
        string $view,
        array $data,
        string $filename,
        string $orientation = 'portrait',
        string $paper = 'a4'
    ): Response {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper($paper, $orientation);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
