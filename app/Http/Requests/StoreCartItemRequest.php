<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCartItemRequest extends FormRequest
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
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:48'],
            'lactose_intolerant' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $product = Product::query()->find($this->integer('product_id'));

            if ($product === null || ! $product->requiresLactoseChoice()) {
                return;
            }

            if (! $this->exists('lactose_intolerant') || $this->input('lactose_intolerant') === null) {
                $validator->errors()->add(
                    'lactose_intolerant',
                    'Please choose whether you are lactose intolerant before adding Cloudy Carrot.',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lactose_intolerant.boolean' => 'Please choose whether you are lactose intolerant before adding Cloudy Carrot.',
        ];
    }
}
