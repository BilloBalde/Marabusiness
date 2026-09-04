<?php

namespace App\Services\Shipping\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DhlTrackingClient
{
    protected Client $client;
    protected array $config;

    public function __construct()
    {
        $this->config = config('services.dhl');
        $this->client = new Client([
            'base_uri' => $this->config['base_url'],
            'timeout' => 30,
            'verify' => false, // Only if you have SSL issues
        ]);
    }

    public function track(string $trackingNumber): array
    {
        $url = $this->config['tracking_url'] ?? $this->config['base_url'] . '/track/shipments';
        
        try {
            // DHL Tracking API requires specific headers
            $response = $this->client->get($url, [
                'headers' => [
                    'DHL-API-Key' => $this->config['api_key'],
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'trackingNumber' => $trackingNumber,
                    'service' => 'express', // or 'parcel' depending on your service
                    'language' => 'en',
                    'offset' => 0,
                    'limit' => 10,
                ],
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            // Log detailed error
            \Log::error('DHL Tracking API Error', [
                'tracking_number' => $trackingNumber,
                'url' => $url,
                'status_code' => $e->getCode(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response',
                'config' => [
                    'has_api_key' => !empty($this->config['api_key']),
                    'has_account' => !empty($this->config['account_number']),
                    'environment' => $this->config['environment'] ?? 'not_set',
                ],
            ]);

            throw new \Exception("DHL Tracking failed: " . $e->getMessage(), $e->getCode());
        }
    }

    public function parseStatus(array $payload): array
    {
        // Parse DHL response
        if (isset($payload['shipments'][0])) {
            $shipment = $payload['shipments'][0];
            
            $status = $shipment['status']['status'] ?? 'unknown';
            $location = $shipment['status']['location'] ?? null;
            $estimatedDelivery = $shipment['estimatedTimeOfDelivery'] ?? null;
            
            return [
                'status' => $this->normalizeStatus($status),
                'current_location' => $location ? $location['address']['addressLocality'] . ', ' . $location['address']['countryCode'] : null,
                'estimated_delivery_at' => $estimatedDelivery,
                'events' => $shipment['events'] ?? [],
            ];
        }
        
        return [];
    }

    protected function normalizeStatus(string $dhlStatus): string
    {
        $statusMap = [
            'pre-transit' => 'pending',
            'transit' => 'in_transit',
            'delivered' => 'delivered',
            'exception' => 'exception',
            'unknown' => 'unknown',
        ];
        
        return $statusMap[strtolower($dhlStatus)] ?? 'unknown';
    }
}