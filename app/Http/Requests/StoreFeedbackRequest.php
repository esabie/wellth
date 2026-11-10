<?php

namespace App\Http\Requests;

use App\Support\FeedbackFormOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedbackRequest extends FormRequest
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
            'product_id' => ['required', 'integer'],
            'shopping_rating' => ['required', 'integer', Rule::in(array_keys(FeedbackFormOptions::shoppingRatings()))],
            'improvement_tags' => ['required', 'array', 'min:1'],
            'improvement_tags.*' => ['string', Rule::in(array_keys(FeedbackFormOptions::improvementTags()))],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Please tap your favourite blend from this order.',
            'shopping_rating.required' => 'Please rate how shopping went.',
            'improvement_tags.required' => 'Please tap anything we could improve, or “All good”.',
            'improvement_tags.min' => 'Please tap anything we could improve, or “All good”.',
        ];
    }
}
