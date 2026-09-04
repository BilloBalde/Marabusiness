<?php

namespace App\Filament\Resources\PaymentGatewayFeeResource\Pages;

use App\Filament\Resources\PaymentGatewayFeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentGatewayFee extends EditRecord
{
    protected static string $resource = PaymentGatewayFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
