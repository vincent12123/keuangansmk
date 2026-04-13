<?php

namespace App\Services;

class AuditTrailService
{
    public function logExport(string $jenis, array $filters = [], ?string $description = null): void
    {
        activity('export')
            ->causedBy(auth()->user())
            ->withProperties([
                'jenis' => $jenis,
                'filters' => $filters,
                'timestamp' => now()->toDateTimeString(),
            ])
            ->log($description ?: "export_{$jenis}");
    }

    public function logPrint(string $jenis, array $payload = [], ?string $description = null): void
    {
        activity('print')
            ->causedBy(auth()->user())
            ->withProperties(array_merge($payload, [
                'jenis' => $jenis,
                'timestamp' => now()->toDateTimeString(),
            ]))
            ->log($description ?: "print_{$jenis}");
    }

    public function logImport(string $jenis, array $payload = [], ?string $description = null): void
    {
        activity('import')
            ->causedBy(auth()->user())
            ->withProperties(array_merge($payload, [
                'jenis' => $jenis,
                'timestamp' => now()->toDateTimeString(),
            ]))
            ->log($description ?: "import_{$jenis}");
    }

    public function logWhatsApp(string $phone, string $message, string $status, array $extra = []): void
    {
        activity('whatsapp')
            ->causedBy(auth()->user())
            ->withProperties(array_merge($extra, [
                'phone' => $phone,
                'message' => $message,
                'status' => $status,
                'timestamp' => now()->toDateTimeString(),
            ]))
            ->log('send_whatsapp_notification');
    }
}
