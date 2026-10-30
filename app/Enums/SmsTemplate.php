<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum SmsTemplate: string implements HasDescription, HasLabel
{
    case OrderPlacedAdmin = 'order_placed_admin';
    case OrderPlacedCustomer = 'order_placed_customer';
    case PaymentProofAdmin = 'payment_proof_admin';
    case OrderPaidCustomer = 'order_paid_customer';
    case OrderShippedCustomer = 'order_shipped_customer';
    case ReferralReward = 'referral_reward';

    public function settingKey(): string
    {
        return 'sms_template_'.$this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::OrderPlacedAdmin => 'Order placed (admin)',
            self::OrderPlacedCustomer => 'Order placed (customer)',
            self::PaymentProofAdmin => 'Payment proof uploaded (admin)',
            self::OrderPaidCustomer => 'Payment confirmed (customer)',
            self::OrderShippedCustomer => 'Order shipped (customer)',
            self::ReferralReward => 'Referral reward (referrer)',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::OrderPlacedAdmin => 'Sent to the admin alert phone when a customer places an order.',
            self::OrderPlacedCustomer => 'Sent to the customer when they place an order, before MoMo proof.',
            self::PaymentProofAdmin => 'Sent to the admin alert phone when MoMo proof is uploaded.',
            self::OrderPaidCustomer => 'Sent to the customer when payment is confirmed in admin.',
            self::OrderShippedCustomer => 'Sent when an order is marked as shipped.',
            self::ReferralReward => 'Sent to the referrer when their code is used on a paid order.',
        };
    }

    public function defaultMessage(): string
    {
        return match ($this) {
            self::OrderPlacedAdmin => 'New order {order} from {name}. Total {currency} {total}. Waiting for MoMo proof.',
            self::OrderPlacedCustomer => 'Hi {first_name}, your Wellth order {order} ({currency} {total}) is awaiting MoMo payment. Pay and upload your screenshot to confirm: {payment_url}',
            self::PaymentProofAdmin => 'Payment proof uploaded for {order} ({currency} {total}) by {name}. Review and confirm in admin.',
            self::OrderPaidCustomer => "Hi {first_name}, Your payment for order {order} ({currency} {total}) is confirmed. We're preparing your fresh shots with care. Your order will be ready within 7 days. Thank you for choosing us!",
            self::OrderShippedCustomer => 'Hi {first_name}, your Wellth order {order} is on the way{city_suffix}. Thanks for choosing us!',
            self::ReferralReward => 'Someone used your code {promo_code}. You earned {points} points. Your balance is now {balance} points. Thanks for sharing!',
        };
    }

    /**
     * @return list<string>
     */
    public function placeholders(): array
    {
        return match ($this) {
            self::OrderPlacedAdmin, self::PaymentProofAdmin => ['{order}', '{name}', '{currency}', '{total}'],
            self::OrderPlacedCustomer => ['{first_name}', '{order}', '{currency}', '{total}', '{payment_url}'],
            self::OrderPaidCustomer => ['{first_name}', '{order}', '{currency}', '{total}'],
            self::OrderShippedCustomer => ['{first_name}', '{order}', '{city_suffix}'],
            self::ReferralReward => ['{promo_code}', '{points}', '{balance}'],
        };
    }

    public function placeholderHelp(): string
    {
        return 'Placeholders: '.implode(', ', $this->placeholders());
    }
}
