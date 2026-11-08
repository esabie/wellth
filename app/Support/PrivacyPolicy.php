<?php

namespace App\Support;

class PrivacyPolicy
{
    /**
     * @return array{
     *     updated: string,
     *     intro: string,
     *     sections: list<array{heading: string, paragraphs?: list<string>, items?: list<string>, subsections?: list<array{heading: string, paragraphs?: list<string>, items?: list<string>}>}>
     * }
     */
    public function content(): array
    {
        return [
            'updated' => 'November 2026',
            'intro' => 'Wellth is committed to protecting your personal information. This Privacy Policy explains how we collect, use, and look after data when you shop at wellthgh.com, under the Ghana Data Protection Act, 2012 (Act 843).',
            'sections' => [
                [
                    'heading' => 'Data Collected',
                    'paragraphs' => [
                        'We collect information needed to fulfil your orders and run the shop securely:',
                    ],
                    'items' => [
                        'Order details: name, phone number, email address (when provided), and delivery address.',
                        'Payment details: Mobile Money payment screenshots or proof you upload, and order totals.',
                    ],
                ],
                [
                    'heading' => 'Purpose of Collection',
                    'paragraphs' => [
                        'We use your data strictly to:',
                    ],
                    'items' => [
                        'Process, prepare, and deliver your orders.',
                        'Confirm Mobile Money payments and send order updates by SMS or email when needed.',
                        'Provide customer support and respond to questions about your order.',
                        'Improve the shop experience and protect against fraud or abuse.',
                    ],
                ],
                [
                    'heading' => 'Data Sharing',
                    'paragraphs' => [
                        'We do not sell your personal data. We share information only when it is needed to complete your order or meet a legal duty:',
                    ],
                    'items' => [
                        'Delivery partners, so your shots can reach you.',
                        'Trusted service providers that help us send SMS, email, or host the website.',
                        'Law enforcement or regulators when required under a valid legal order.',
                    ],
                ],
                [
                    'heading' => 'Your Rights under Act 843',
                    'paragraphs' => [
                        'You have the right to access your data, ask us to correct inaccurate information, or request deletion of personal details we hold about you, subject to records we must keep for completed orders. Contact us at admin@wellthgh.com and we will help as quickly as we can.',
                    ],
                ],
                [
                    'heading' => 'Cookie Policy',
                    'subsections' => [
                        [
                            'heading' => 'What Are Cookies?',
                            'paragraphs' => [
                                'Cookies are small text files stored on your phone, tablet, or computer when you browse wellthgh.com. They help the shop remember your cart and keep basic preferences working.',
                            ],
                        ],
                        [
                            'heading' => 'How We Use Cookies',
                            'items' => [
                                'Essential cookies: keep your cart and checkout session working.',
                                'Performance cookies: help us understand which pages people open so we can improve the shop.',
                            ],
                        ],
                        [
                            'heading' => 'Managing Choices',
                            'paragraphs' => [
                                'You can block or delete cookies in your browser settings. Turning off essential cookies may stop the cart or checkout from working correctly.',
                            ],
                        ],
                    ],
                ],
                [
                    'heading' => 'Data Retention',
                    'paragraphs' => [
                        'We keep order records for as long as needed to fulfil purchases, handle support, and meet accounting or legal requirements. Technical visit logs are kept for a limited period to operate and secure the site.',
                    ],
                ],
                [
                    'heading' => 'Contact',
                    'paragraphs' => [
                        'Questions about this Privacy Policy or your data can be sent to admin@wellthgh.com or through Instagram at @wellth.gh.',
                    ],
                ],
            ],
        ];
    }
}
