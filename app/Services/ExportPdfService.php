<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportPdfService
{
    public function download(
        string $view,
        array $data,
        string $filename,
        string $orientation = 'portrait',
        string $paper = 'a4'
    ): StreamedResponse {
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
    ): StreamedResponse {
        return $this->download($view, $data, $filename, $orientation, $paper);
    }
}
