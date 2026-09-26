<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PaystackPaymentService
{
    public function initialize(Order $order): Payment
    {
        $this->ensureConfigured();

        if ($order->status === 'cancelled') {
            throw new RuntimeException('Cancelled orders cannot be paid');
        }

        $existingPayment = $order->payment;
        if ($existingPayment && $existingPayment->status === 'success') {
            return $existingPayment;
        }

        $amountMinor = $this->amountToMinorUnits($order->total);
        $reference = $existingPayment && $existingPayment->status === 'pending'
            ? $existingPayment->reference
            : $this->generateReference($order);

        $response = $this->client()->post('/transaction/initialize', [
            'email' => $order->user->email,
            'amount' => $amountMinor,
            'currency' => $order->currency ?: config('services.paystack.currency', 'NGN'),
            'reference' => $reference,
            'callback_url' => config('services.paystack.callback_url'),
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        if (!$response->successful() || !$response->json('status')) {
            throw new RuntimeException('Paystack could not initialize this payment: ' . $this->gatewayMessage($response));
        }

        $data = $response->json('data', []);
        if (empty($data['authorization_url']) || empty($data['access_code'])) {
            throw new RuntimeException('Paystack returned an incomplete payment initialization response');
        }

        return Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'user_id' => $order->user_id,
                'provider' => 'paystack',
                'reference' => $data['reference'] ?? $reference,
                'access_code' => $data['access_code'],
                'authorization_url' => $data['authorization_url'],
                'amount_minor' => $amountMinor,
                'currency' => $order->currency ?: config('services.paystack.currency', 'NGN'),
                'status' => 'pending',
            ]
        );
    }

    public function verify(string $reference): Payment
    {
        $this->ensureConfigured();

        $response = $this->client()->get('/transaction/verify/' . rawurlencode($reference));
        if (!$response->successful() || !$response->json('status')) {
            throw new RuntimeException('Paystack could not verify this payment: ' . $this->gatewayMessage($response));
        }

        $data = $response->json('data', []);
        $payment = Payment::where('reference', $reference)->firstOrFail();
        $expectedAmount = $payment->amount_minor;
        $receivedAmount = (int) ($data['amount'] ?? 0);
        $receivedCurrency = strtoupper((string) ($data['currency'] ?? ''));

        if ($receivedAmount !== $expectedAmount || $receivedCurrency !== strtoupper($payment->currency)) {
            throw new RuntimeException('Paystack payment amount or currency does not match the order');
        }

        return DB::transaction(function () use ($payment, $data) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

            if ($payment->status === 'success') {
                return $payment;
            }

            $status = $data['status'] ?? 'failed';
            $payment->update([
                'status' => $status === 'success' ? 'success' : $status,
                'gateway_transaction_id' => isset($data['id']) ? (string) $data['id'] : null,
                'gateway_response' => $data,
                'paid_at' => $status === 'success' ? now() : null,
            ]);

            if ($status === 'success' && !$order->paid_at) {
                $order->update(['paid_at' => now()]);
            }

            return $payment->fresh();
        });
    }

    public function handleWebhook(string $payload, ?string $signature): Payment
    {
        $this->ensureConfigured();

        if (!$signature || !hash_equals(hash_hmac('sha512', $payload, config('services.paystack.secret_key')), $signature)) {
            throw new RuntimeException('Invalid Paystack webhook signature');
        }

        $event = json_decode($payload, true);
        $reference = $event['data']['reference'] ?? null;
        if (($event['event'] ?? null) !== 'charge.success' || !$reference) {
            throw new RuntimeException('Unsupported or incomplete Paystack webhook event');
        }

        return $this->verify($reference);
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(rtrim(config('services.paystack.base_url'), '/'))
            ->withToken(config('services.paystack.secret_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->connectTimeout(10);
    }

    protected function amountToMinorUnits($amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    protected function generateReference(Order $order): string
    {
        return 'KAF-' . $order->order_number . '-' . Str::upper(Str::random(8));
    }

    protected function ensureConfigured(): void
    {
        if (!config('services.paystack.secret_key')) {
            throw new RuntimeException('Paystack is not configured. Set PAYSTACK_SECRET_KEY in .env');
        }
    }

    protected function gatewayMessage($response): string
    {
        return (string) ($response->json('message') ?: $response->body() ?: 'Unknown gateway error');
    }
}
