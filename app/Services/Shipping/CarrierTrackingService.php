<?php

namespace App\Services\Shipping;

use App\Models\Shipment;
use App\Services\Shipping\Clients\DhlTrackingClient;
use Illuminate\Support\Carbon;

class CarrierTrackingService
{
    public function __construct(
        protected DhlTrackingClient $dhlClient,
    ) {
    }

    public function sync(Shipment $shipment): Shipment
    {
        // Check what credentials you're using
        \Log::info('DHL Credentials Check:', [
            'api_key' => config('services.dhl.api_key'),
            'api_secret' => config('services.dhl.api_secret'),
            'account_number' => config('services.dhl.account_number'),
            'environment' => config('services.dhl.environment'), // sandbox or production
        ]);
        $payload = match ($shipment->carrier) {
            'dhl' => $this->dhlClient->track($shipment->tracking_number),
            default => throw new \RuntimeException("Carrier {$shipment->carrier} not supported for sync."),
        };

        $normalized = match ($shipment->carrier) {
            'dhl' => $this->dhlClient->parseStatus($payload),
            default => [],
        };

        $shipment->fill([
            'status' => $normalized['status'] ?? $shipment->status,
            'current_location' => $normalized['current_location'] ?? $shipment->current_location,
            'estimated_delivery_at' => isset($normalized['estimated_delivery_at'])
                ? Carbon::parse($normalized['estimated_delivery_at'])
                : $shipment->estimated_delivery_at,
            'payload' => $payload,
            'last_synced_at' => now(),
        ])->save();

        return $shipment->refresh();
    }
}
