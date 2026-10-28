<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_proof' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_proof.required' => 'Please upload a screenshot of your MoMo payment confirmation.',
            'payment_proof.image' => 'The payment proof must be an image file.',
            'payment_proof.mimes' => 'Use a JPG, PNG, or WebP screenshot.',
            'payment_proof.max' => 'The screenshot must be smaller than 5MB.',
        ];
    }
}
