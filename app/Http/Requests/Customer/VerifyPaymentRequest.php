<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gateway_order_id' => ['nullable', 'string', 'max:120', 'required_without:razorpay_order_id'],
            'razorpay_order_id' => ['nullable', 'string', 'max:120', 'required_without:gateway_order_id'],
            'gateway_payment_id' => ['nullable', 'string', 'max:120', 'required_without:razorpay_payment_id'],
            'razorpay_payment_id' => ['nullable', 'string', 'max:120', 'required_without:gateway_payment_id'],
            'signature' => ['nullable', 'string', 'max:255', 'required_without:razorpay_signature'],
            'razorpay_signature' => ['nullable', 'string', 'max:255', 'required_without:signature'],
        ];
    }
}
