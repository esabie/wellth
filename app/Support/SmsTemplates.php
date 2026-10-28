<?php

namespace App\Support;

use App\Enums\SmsTemplate;
use App\Models\Setting;

class SmsTemplates
{
    public function __construct(private ShopSettings $settings) {}

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $templates = [];

        foreach (SmsTemplate::cases() as $template) {
            $templates[$template->value] = $this->message($template);
        }

        return $templates;
    }

    public function message(SmsTemplate $template): string
    {
        return $this->settings->string(
            $template->settingKey(),
            $template->defaultMessage(),
        );
    }

    /**
     * @param  array<string, string|int|float|null>  $replacements
     */
    public function render(SmsTemplate $template, array $replacements = []): string
    {
        $message = $this->message($template);

        foreach ($replacements as $key => $value) {
            $placeholder = str_starts_with((string) $key, '{')
                ? (string) $key
                : '{'.$key.'}';

            $message = str_replace($placeholder, (string) ($value ?? ''), $message);
        }

        return trim(preg_replace('/[ \t]+/', ' ', $message) ?? $message);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function put(array $values): void
    {
        foreach (SmsTemplate::cases() as $template) {
            if (! array_key_exists($template->value, $values)) {
                continue;
            }

            $message = trim((string) $values[$template->value]);

            Setting::query()->updateOrCreate(
                ['key' => $template->settingKey()],
                ['value' => $message !== '' ? $message : $template->defaultMessage()],
            );
        }

        $this->settings->forgetCache();
    }
}
