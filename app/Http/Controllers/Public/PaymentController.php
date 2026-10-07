<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function show(Booking $booking, PaymentService $payments): Response
    {
        abort_unless($booking->customer?->user_id === request()->user()?->id, 403);

        $payment = Payment::query()->where('booking_id', $booking->id)->latest()->first();

        if ($payment) {
            $payment = $payments->reconcile($payment);
        }

        $invoice = \App\Models\Invoice::query()->where('booking_id', $booking->id)->first();

        if ($payment && $payment->status === 'EXPIRED' && $invoice && (float) $invoice->balance > 0) {
            $payment = $payments->create($booking, $payment->method ?: 'ONLINE', $payment->payment_type ?: 'FULL');
        }

        return Inertia::render('Public/Payment/Show', [
            'booking' => $booking->fresh()->load('vehicle'),
            'invoice' => $invoice,
            'payment' => $payment,
            'selectedPaymentType' => request('payment_type') === 'FULL' ? 'FULL' : 'DP'
        ]);
    }

    public function create(Request $request, Booking $booking, PaymentService $payments): JsonResponse
    {
        abort_unless($booking->customer?->user_id === $request->user()?->id, 403);

        $data = $request->validate([
            'method' => ['sometimes', 'nullable', 'string', 'in:QRIS,VA,ONLINE,XENDIT'],
            'payment_type' => ['required', 'in:DP,FULL'],
        ]);
        $payment = $payments->create($booking, $data['method'] ?? 'ONLINE', $data['payment_type']);
        return response()->json(['payment' => $payment]);
    }
}
