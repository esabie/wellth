<?php

namespace App\Support;

class Faqs
{
    /**
     * @return list<array{question: string, answer: string}>
     */
    public function all(): array
    {
        $minimumOrderQuantity = app(ShopSettings::class)->minimumOrderQuantity();

        return [
            [
                'question' => 'Are your shots 100% natural?',
                'answer' => 'Our shots are prepared using natural ingredients. Please check the individual product descriptions for details about specific ingredients and any additional components.',
            ],
            [
                'question' => 'What ingredients do you use in your shots?',
                'answer' => 'Our shots are made using carefully selected ingredients, depending on the blend. Please refer to each product\'s description on our website for its specific ingredients. If you have any questions about a particular blend, feel free to contact us.',
            ],
            [
                'question' => 'How do I place an order?',
                'answer' => 'Placing an order is easy! Simply browse our available shots, select your preferred blends and quantities, and follow the ordering instructions on our website. You can also contact us through our official communication channels for assistance.',
            ],
            [
                'question' => 'Do you have shots available or do we always have to preorder?',
                'answer' => 'We currently operate strictly on a preorder basis, and all orders are completed within one week of placement. We appreciate your patience and look forward to serving you!',
            ],
            [
                'question' => 'How do I store my shots?',
                'answer' => 'Fresh natural shots last 3 days in the refrigerator. Without preservatives, they are best kept frozen and can last up to 3 months.',
            ],
            [
                'question' => 'Is there a minimum purchase limit?',
                'answer' => "Yes, we have a minimum order quantity of {$minimumOrderQuantity} shots per purchase. Thank you for your understanding!",
            ],
            [
                'question' => 'Can I cancel or modify my order after placing it?',
                'answer' => 'If you need to make changes to your order, please contact us as soon as possible. We will do our best to accommodate your request, depending on the stage of preparation. Orders that have already been prepared may not be eligible for changes or cancellation.',
            ],
            [
                'question' => 'Can I schedule my delivery for a specific date?',
                'answer' => 'You may request a preferred delivery date when placing your order. We will do our best to accommodate your request, subject to preparation time and delivery availability.',
            ],
            [
                'question' => 'Can I order shots as gifts for someone?',
                'answer' => 'Absolutely! Our natural shots can make a thoughtful gift for friends, family, or colleagues. Please contact us before ordering to discuss the available options and delivery arrangements.',
            ],
        ];
    }
}
