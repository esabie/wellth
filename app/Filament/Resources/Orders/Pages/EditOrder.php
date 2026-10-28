<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\RestoreOrderStock;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Support\LoyaltyPoints;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return [
            'status' => $data['status'],
            'shipping_notes' => $data['shipping_notes'] ?? null,
        ];
    }

    protected function afterSave(): void
    {
        $status = $this->record->status;

        if (! in_array($status, [OrderStatus::Cancelled, OrderStatus::Failed], true)) {
            return;
        }

        app(LoyaltyPoints::class)->releaseRedeem($this->record);
        app(RestoreOrderStock::class)->handle($this->record);
    }
}
