<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments)
    {
        abort_unless(hash_equals((string) config('services.xendit.webhook_token'), (string) $request->header('x-callback-token')), 403);
        $data = $request->input('data', []);
        $reference = $data['reference_id'] ?? $request->input('reference_id');
        $transaction = $data['payment_id'] ?? $data['payment_session_id'] ?? $request->input('payment_id');
        $status = strtoupper((string) ($data['status'] ?? $request->input('status')));
        $event = $request->input('event');
        $isPaid = in_array($status, ['SUCCEEDED', 'SUCCEEDDED', 'PAID', 'COMPLETED'], true) || $event === 'payment_session.completed';

        if (! $isPaid && (in_array($status, ['EXPIRED', 'CANCELED', 'CANCELLED'], true) || $event === 'payment_session.expired')) {
            if ($reference) {
                Payment::where('payment_code', $reference)->where('status', 'PENDING')->update(['status' => 'EXPIRED']);
            }
            return response()->json(['message' => 'Payment expired recorded']);
        }

        abort_unless($reference && $transaction && $isPaid, 422);
        return response()->json($payments->webhook(['payment_code' => $reference, 'transaction_id' => $transaction, 'status' => 'PAID', 'xendit_event' => $event]));
    }
}
