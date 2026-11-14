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
            'with_ginger' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $product = Product::query()->find($this->integer('product_id'));

            if ($product === null) {
                return;
            }

            if ($product->requiresLactoseChoice()
                && (! $this->exists('lactose_intolerant') || $this->input('lactose_intolerant') === null)) {
                $validator->errors()->add(
                    'lactose_intolerant',
                    'Please choose a milk option before adding Cloudy Carrot.',
                );
            }

            if ($product->requiresGingerChoice()
                && (! $this->exists('with_ginger') || $this->input('with_ginger') === null)) {
                $validator->errors()->add(
                    'with_ginger',
                    'Please choose a ginger option before adding Tigernut Drink.',
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
            'lactose_intolerant.boolean' => 'Please choose a milk option before adding Cloudy Carrot.',
            'with_ginger.boolean' => 'Please choose a ginger option before adding Tigernut Drink.',
        ];
    }
}
