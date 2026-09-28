<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaystackPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends ImprovedController
{
    public function __construct(private PaystackPaymentService $paymentService)
    {
    }

    public function initialize(string $orderNumber)
    {
        try {
            $order = Order::where('user_id', Auth::id())
                ->where('order_number', strtoupper($orderNumber))
                ->firstOrFail();
            $payment = $this->paymentService->initialize($order);

            return $this->respondWithSuccess('Payment initialized successfully', 200, new PaymentResource($payment));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->respondWithError('Order not found', 404);
        } catch (\Throwable $e) {
            return $this->respondWithError($e->getMessage(), 502);
        }
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'regex:/^KAF-ORD[A-Z0-9]{14}-[A-Z0-9]{8}$/'],
        ]);
        $reference = $validated['reference'];

        if (!$reference) {
            return $this->respondWithError('Payment reference is required', 422);
        }

        $payment = Payment::where('reference', $reference)
            ->where('user_id', Auth::id())
            ->first();

        if (!$payment) {
            return $this->respondWithError('Payment not found', 404);
        }

        try {
            return $this->respondWithSuccess(
                'Payment verification completed',
                200,
                new PaymentResource($this->paymentService->verify($reference))
            );
        } catch (\Throwable $e) {
            return $this->respondWithError($e->getMessage(), 502);
        }
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference');
        if (!$reference) {
            return $this->redirectToPaymentResult(null, null, 'failed');
        }

        $paymentRecord = Payment::with(['order.user'])
            ->where('reference', $reference)
            ->first();

        try {
            $payment = $this->paymentService->verify($reference);
            $payment->load(['order.user']);

            return $this->redirectToPaymentResult(
                $payment,
                $reference,
                $payment->status === 'success' ? 'success' : 'failed'
            );
        } catch (\Throwable $e) {
            return $this->redirectToPaymentResult($paymentRecord, $reference, 'failed');
        }
    }

    protected function redirectToPaymentResult(?Payment $payment, ?string $reference, string $status)
    {
        $order = $payment?->order;
        $username = $order?->user?->username;
        $orderNumber = $order?->order_number;

        if (!$username || !$orderNumber) {
            return $this->respondWithError('Payment result cannot be routed to the frontend', 502);
        }

        $query = array_filter([
            'status' => $status,
            'reference' => $reference,
            'order' => $orderNumber,
        ]);
        $url = rtrim(config('services.paystack.frontend_url'), '/')
            . '/' . rawurlencode($username)
            . '/buying/payment?'
            . http_build_query($query);

        return redirect()->away($url);
    }

    public function webhook(Request $request)
    {
        try {
            $payment = $this->paymentService->handleWebhook(
                $request->getContent(),
                $request->header('x-paystack-signature')
            );

            return $this->respondWithSuccess('Webhook processed', 200, new PaymentResource($payment));
        } catch (\Throwable $e) {
            return $this->respondWithError($e->getMessage(), 400);
        }
    }
}
