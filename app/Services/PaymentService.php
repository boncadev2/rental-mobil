<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function create(Booking $booking, string $method, string $paymentType = 'FULL'): Payment
    {
        $invoice = Invoice::firstOrCreate(['booking_id' => $booking->id], ['invoice_number' => 'INV-'.str()->upper(str()->random(10)), 'total' => $booking->total_amount, 'balance' => $booking->total_amount]);
        if ($invoice->balance <= 0) throw ValidationException::withMessages(['payment' => 'Booking ini sudah lunas.']);
        $amount = $paymentType === 'DP' ? (int) ceil($booking->total_amount * 0.1) : (float) $invoice->balance;
        if ($paymentType === 'DP' && $invoice->paid_amount > 0) throw ValidationException::withMessages(['payment' => 'DP sudah dibayarkan. Silakan pilih pelunasan.']);
        $payment = Payment::create(['booking_id' => $booking->id, 'invoice_id' => $invoice->id, 'payment_code' => 'PAY-'.str()->upper(str()->random(10)), 'method' => $method, 'payment_type' => $paymentType, 'amount' => $amount]);
        if (config('services.xendit.secret_key') && ! app()->environment('testing')) {
            $returnUrl = rtrim((string) config('app.url'), '/').'/payment/'.$booking->id;
            $session = Http::withBasicAuth(config('services.xendit.secret_key'), '')->acceptJson()->post('https://api.xendit.co/sessions', ['reference_id' => $payment->payment_code, 'session_type' => 'PAY', 'mode' => 'PAYMENT_LINK', 'amount' => (float) $payment->amount, 'currency' => 'IDR', 'country' => 'ID', 'success_return_url' => $returnUrl, 'cancel_return_url' => $returnUrl, 'metadata' => ['booking_code' => $booking->booking_code, 'payment_type' => $paymentType, 'preferred_method' => $method]])->throw()->json();
            $payment->update(['gateway_transaction_id' => $session['payment_session_id'] ?? null, 'qr_string' => $session['payment_link_url'] ?? null, 'gateway_response' => $session]);
        }
        return $payment;
    }

    public function reconcile(Payment $payment): Payment
    {
        if ($payment->status === 'PAID' || ! $payment->gateway_transaction_id || ! config('services.xendit.secret_key')) {
            return $payment;
        }

        $response = Http::withBasicAuth(config('services.xendit.secret_key'), '')
            ->acceptJson()
            ->get('https://api.xendit.co/sessions/'.rawurlencode($payment->gateway_transaction_id));

        if (! $response->successful()) {
            return $payment;
        }

        $session = $response->json();

        if (($session['status'] ?? null) !== 'COMPLETED' || ($session['reference_id'] ?? null) !== $payment->payment_code) {
            return $payment;
        }

        return $this->webhook([
            'payment_code' => $payment->payment_code,
            'transaction_id' => $session['payment_id'] ?? $session['payment_session_id'] ?? $payment->gateway_transaction_id,
            'status' => 'PAID',
            'xendit_event' => 'payment_session.reconciled',
        ]);
    }

    public function recordCashSettlement(Booking $booking): Payment
    {
        return DB::transaction(function () use ($booking) {
            $invoice = Invoice::query()->lockForUpdate()->where('booking_id', $booking->id)->firstOrFail();

            if ($invoice->balance <= 0) {
                throw ValidationException::withMessages(['payment' => 'Invoice ini sudah lunas.']);
            }

            $amount = (float) $invoice->balance;
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'invoice_id' => $invoice->id,
                'payment_code' => 'CASH-'.str()->upper(str()->random(10)),
                'method' => 'CASH',
                'payment_type' => 'FULL',
                'amount' => $amount,
                'status' => 'PAID',
                'gateway_response' => ['recorded_by' => 'admin', 'method' => 'CASH'],
                'paid_at' => now(),
            ]);

            $invoice->update([
                'paid_amount' => (float) $invoice->paid_amount + $amount,
                'balance' => 0,
                'status' => 'PAID',
            ]);

            $booking->update(['status' => BookingStatus::CONFIRMED]);
            $booking->vehicle()->update(['status' => VehicleStatus::RESERVED]);

            return $payment;
        });
    }

    public function webhook(array $payload): Payment
    {
        return DB::transaction(function () use ($payload) {
            $payment = Payment::query()->lockForUpdate()->where('payment_code', $payload['payment_code'])->firstOrFail();
            if ($payment->status === 'PAID') return $payment;
            $payment->update(['status' => 'PAID', 'gateway_transaction_id' => $payload['transaction_id'], 'paid_at' => now(), 'gateway_response' => $payload]);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($payment->invoice_id);
            $paidAmount = min((float) $invoice->total, (float) $invoice->paid_amount + (float) $payment->amount);
            $balance = max(0, (float) $invoice->total - $paidAmount);
            $invoice->update(['paid_amount' => $paidAmount, 'balance' => $balance, 'status' => $balance > 0 ? 'PARTIALLY_PAID' : 'PAID']);
            $booking = $payment->booking;
            $booking->update(['status' => BookingStatus::CONFIRMED]);
            Booking::query()->where('vehicle_id', $booking->vehicle_id)->where('status', BookingStatus::WAITING_PAYMENT)->whereKeyNot($booking->id)->delete();
            $booking->vehicle()->update(['status' => VehicleStatus::RESERVED]);
            return $payment;
        });
    }
}
