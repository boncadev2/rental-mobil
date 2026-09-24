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

        return Inertia::render('Public/Payment/Show', ['booking' => $booking->fresh()->load('vehicle'), 'invoice' => \App\Models\Invoice::query()->where('booking_id', $booking->id)->first(), 'payment' => $payment, 'selectedPaymentType' => request('payment_type') === 'FULL' ? 'FULL' : 'DP']);
    }

    public function create(Request $request, Booking $booking, PaymentService $payments): JsonResponse
    {
        abort_unless($booking->customer?->user_id === $request->user()?->id, 403);

        $data = $request->validate(['method' => ['required', 'in:QRIS,VA'], 'payment_type' => ['required', 'in:DP,FULL']]);
        $payment = $payments->create($booking, $data['method'], $data['payment_type']);
        return response()->json(['payment' => $payment]);
    }
}
