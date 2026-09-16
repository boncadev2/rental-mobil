<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppBookingNotificationService
{
    public function sendBookingCreated(Booking $booking): void
    {
        $booking->loadMissing(['customer', 'vehicle']);

        $customerPhone = $this->normalizePhone($booking->customer?->phone);
        if ($customerPhone !== null) {
            $this->send($customerPhone, $this->customerMessage($booking));
        }

        $adminPhone = $this->normalizePhone(AppSetting::value('whatsapp_admin_phone') ?: config('services.whatsapp.admin_phone'));
        if ($adminPhone !== null) {
            $this->send($adminPhone, $this->adminMessage($booking));
        }
    }

    private function send(string $phone, string $message): void
    {
        $baseUrl = rtrim((string) config('services.whatsapp.base_url'), '/');
        $apiKey = config('services.whatsapp.api_key');
        $sessionId = AppSetting::value('whatsapp_session_id') ?: config('services.whatsapp.session_id');

        if ($baseUrl === '' || blank($apiKey) || blank($sessionId)) {
            Log::warning('WhatsApp booking notification was skipped because the gateway is not configured.');

            return;
        }

        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->withHeaders(['x-api-key' => $apiKey])
                ->post("{$baseUrl}/api/send-text", [
                    'session_id' => $sessionId,
                    'to' => $phone,
                    'message' => $message,
                ]);

            if ($response->failed()) {
                Log::warning('WhatsApp booking notification failed.', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('WhatsApp booking notification could not be sent.', [
                'phone' => $phone,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function customerMessage(Booking $booking): string
    {
        return "Halo {$booking->customer->full_name},\n\nBooking Anda berhasil dibuat.\nKode booking: {$booking->booking_code}\nMobil: {$booking->vehicle->brand} {$booking->vehicle->model}\nTanggal sewa: {$booking->start_datetime->format('d/m/Y')} - {$booking->end_datetime->format('d/m/Y')}\nTotal: Rp ".number_format((float) $booking->total_amount, 0, ',', '.')."\n\nSilakan lanjutkan pembayaran untuk mengonfirmasi booking Anda.";
    }

    private function adminMessage(Booking $booking): string
    {
        return "Booking baru berhasil masuk.\n\nKode: {$booking->booking_code}\nPelanggan: {$booking->customer->full_name}\nWhatsApp: {$booking->customer->phone}\nMobil: {$booking->vehicle->brand} {$booking->vehicle->model}\nTanggal sewa: {$booking->start_datetime->format('d/m/Y')} - {$booking->end_datetime->format('d/m/Y')}\nTotal: Rp ".number_format((float) $booking->total_amount, 0, ',', '.');
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        return str_starts_with($digits, '62') ? $digits : $digits;
    }
}
