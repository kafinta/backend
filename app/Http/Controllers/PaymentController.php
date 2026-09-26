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

    public function initialize($orderId)
    {
        try {
            $order = Order::where('user_id', Auth::id())->findOrFail($orderId);
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
        $reference = $request->input('reference');
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
            return $this->respondWithError('Payment reference is required', 422);
        }

        try {
            $payment = $this->paymentService->verify($reference);
            return $this->respondWithSuccess('Payment verification completed', 200, new PaymentResource($payment));
        } catch (\Throwable $e) {
            return $this->respondWithError($e->getMessage(), 502);
        }
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
