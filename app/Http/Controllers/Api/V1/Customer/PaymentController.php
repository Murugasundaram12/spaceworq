<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\InitiatePaymentRequest;
use App\Http\Requests\Customer\VerifyPaymentRequest;
use App\Http\Resources\PaymentTransactionResource;
use App\Models\Booking;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function initiate(Booking $booking, InitiatePaymentRequest $request): JsonResponse
    {
        Gate::authorize('manageAsCustomer', $booking);

        $idempotencyKey = (string) ($request->validated()['idempotency_key']
            ?? $request->header('Idempotency-Key')
            ?? Str::uuid()->toString());

        $transaction = $this->paymentService->initiatePayment(
            $booking,
            $request->user(),
            $idempotencyKey
        );

        return (new PaymentTransactionResource($transaction))
            ->additional([
                'meta' => [
                    'gateway_key_id' => config('spaceworq.payment.razorpay.key_id'),
                ],
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function verify(Booking $booking, VerifyPaymentRequest $request): JsonResponse
    {
        Gate::authorize('manageAsCustomer', $booking);

        $transaction = $this->paymentService->verifyPayment(
            $booking,
            $request->user(),
            $request->validated()
        );

        return (new PaymentTransactionResource($transaction))->response();
    }

    public function webhook(string $gateway, Request $request): JsonResponse
    {
        $signature = $request->header('X-Razorpay-Signature')
            ?? $request->header('X-Webhook-Signature');

        $result = $this->paymentService->handleWebhook(
            strtolower($gateway),
            (string) $request->getContent(),
            $signature ? (string) $signature : null
        );

        return response()->json(['data' => $result]);
    }
}
