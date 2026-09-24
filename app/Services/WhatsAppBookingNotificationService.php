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
            $success = $this->send($customerPhone, $this->customerMessage($booking));
            if ($success) {
                $booking->update(['whatsapp_notification_sent' => true]);
            }
        }

        $adminPhone = $this->normalizePhone(AppSetting::value('whatsapp_admin_phone') ?: config('services.whatsapp.admin_phone'));
        if ($adminPhone !== null) {
            $this->send($adminPhone, $this->adminMessage($booking));
        }
    }

    public function sendVerificationCode(string $phone, string $code, ?string $customerName = null): bool
    {
        $targetPhone = $this->normalizePhone($phone);
        if ($targetPhone === null) {
            return false;
        }

        $greeting = $customerName ? "Halo {$customerName},\n\n" : "Halo,\n\n";
        $message = "{$greeting}Kode verifikasi akun Anda adalah: *{$code}*\n\nKode ini berlaku selama 10 menit. Masukkan kode ini pada halaman verifikasi profil untuk mengaktifkan akun Anda.";

        return $this->send($targetPhone, $message);
    }

    private function send(string $phone, string $message): bool
    {
        $baseUrl = rtrim((string) (AppSetting::value('whatsapp_gateway_url') ?: config('services.whatsapp.base_url')), '/');
        $apiKey = AppSetting::value('whatsapp_api_key') ?: config('services.whatsapp.api_key');
        $sessionId = AppSetting::value('whatsapp_session_id') ?: config('services.whatsapp.session_id');

        if ($baseUrl === '' || blank($apiKey)) {
            Log::warning('WhatsApp notification was skipped because the gateway is not configured.');
            return false;
        }

        try {
            $payload = [
                'to' => $phone,
                'message' => $message,
            ];

            if (!blank($sessionId)) {
                $payload['session_id'] = $sessionId;
            }

            $response = Http::acceptJson()
                ->timeout(10)
                ->withHeaders(['x-api-key' => $apiKey])
                ->post("{$baseUrl}/api/send-text", $payload);

            if ($response->failed()) {
                Log::warning('WhatsApp notification failed.', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return false;
            }
            
            return true;
        } catch (\Throwable $exception) {
            Log::warning('WhatsApp notification could not be sent.', [
                'phone' => $phone,
                'exception' => $exception->getMessage(),
            ]);
            return false;
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
