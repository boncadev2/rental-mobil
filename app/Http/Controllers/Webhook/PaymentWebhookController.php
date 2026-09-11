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
        $status = $data['status'] ?? $request->input('status');
        $event = $request->input('event');
        $isPaid = in_array($status, ['SUCCEEDED', 'SUCCEEDDED', 'PAID', 'COMPLETED'], true) || $event === 'payment_session.completed';
        abort_unless($reference && $transaction && $isPaid, 422);
        return response()->json($payments->webhook(['payment_code' => $reference, 'transaction_id' => $transaction, 'status' => 'PAID', 'xendit_event' => $event]));
    }
}
