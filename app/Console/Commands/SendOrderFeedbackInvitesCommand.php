<?php

namespace App\Console\Commands;

use App\Actions\SendOrderFeedbackInvite;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

#[Signature('orders:send-feedback-invites {--due : Only orders scheduled for automatic invite (new deliveries)} {--dry-run : List matching orders without sending SMS} {--limit=100 : Maximum orders to process} {--resend : Include orders that already received an invite}')]
#[Description('Text delivered customers a unique feedback link for orders that have not submitted feedback yet')]
class SendOrderFeedbackInvitesCommand extends Command
{
    public function handle(SendOrderFeedbackInvite $sendInvite): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $resend = (bool) $this->option('resend');
        $dueOnly = (bool) $this->option('due');

        if ($dueOnly && $resend) {
            $this->error('Cannot combine --due with --resend.');

            return self::FAILURE;
        }

        $query = Order::query()
            ->orderBy('delivered_at')
            ->limit($limit);

        if ($dueOnly) {
            $query->dueForAutomaticFeedbackInvite();
        } else {
            $query
                ->where('status', OrderStatus::Delivered)
                ->whereDoesntHave('feedback')
                ->whereNotNull('customer_phone')
                ->where('customer_phone', '!=', '');

            if (! $resend) {
                $query->whereNull('feedback_invited_at');
            }
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->info($dueOnly
                ? 'No scheduled feedback invites are due.'
                : 'No delivered orders need a feedback invite.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? 'Would invite' : 'Inviting')." {$orders->count()} order(s).");

        $sent = 0;
        $failed = 0;

        foreach ($orders as $order) {
            if ($dryRun) {
                $this->line("{$order->uuid} · {$order->customer_phone}");

                continue;
            }

            try {
                $sendInvite->handle($order);
                $sent++;
                $this->line("Sent · {$order->uuid}");
            } catch (InvalidArgumentException|Throwable $exception) {
                $failed++;
                $this->error("Failed · {$order->uuid}: {$exception->getMessage()}");
            }
        }

        if (! $dryRun) {
            $this->info("Done. Sent {$sent}, failed {$failed}.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
