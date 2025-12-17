<?php

namespace App\Services\Shipping\Clients;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class DhlTrackingClient
{
    public function track(string $trackingNumber): array
    {
        $apiKey = config('services.dhl.api_key');
        $baseUrl = rtrim(config('services.dhl.base_url', 'https://api-eu.dhl.com'), '/');

        if (blank($apiKey)) {
            throw new \RuntimeException('DHL API key is not configured.');
        }

        if (blank($baseUrl)) {
            throw new \RuntimeException('DHL API base URL is not configured.');
        }

        $response = Http::baseUrl($baseUrl)
            ->withHeaders([
                'DHL-API-Key' => $apiKey,
            ])
            ->acceptJson()
            ->get('/track/shipments', [
                'trackingNumber' => $trackingNumber,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                sprintf('DHL API error: %s', $response->body())
            );
        }

        return $response->json();
    }

    public function parseStatus(array $payload): array
    {
        $firstShipment = Arr::get($payload, 'shipments.0', []);
        $latestEvent = Arr::get($firstShipment, 'events.0', []);

        return [
            'status' => Arr::get($firstShipment, 'status.statusCode', 'in_transit'),
            'current_location' => Arr::get($latestEvent, 'location.address.addressLocality'),
            'estimated_delivery_at' => Arr::get($firstShipment, 'estimatedTimeOfDelivery'),
        ];
    }
}
