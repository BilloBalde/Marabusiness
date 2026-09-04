<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LengoPayService
{
    public function createPayment(float $amount, string $returnUrl, string $callbackUrl, ?string $currency = null): array
    {
        $baseUrl    = rtrim(config('services.lengopay.base_url'), '/');
        $licenseKey = config('services.lengopay.license_key');
        $websiteId  = config('services.lengopay.website_id');
        $currency   = $currency ?: config('services.lengopay.currency', 'GNF');

        $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $licenseKey,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])->post($baseUrl . '/api/v1/payments', [
                'websiteid'     => $websiteId,
                'amount'        => $amount,
                'currency'      => $currency,
                'return_url'    => $returnUrl,
                'callback_url'  => $callbackUrl,
            ]);

        $response->throw();

        return $response->json();
    }
    public function getPaymentStatus(string $payId): array
    {
        $baseUrl    = rtrim(config('services.lengopay.base_url'), '/');
        $licenseKey = config('services.lengopay.license_key');

        $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $licenseKey,
                'Accept'        => 'application/json',
            ])
            ->get($baseUrl . '/api/v1/payments/' . $payId);

        $response->throw();

        return $response->json();
    }

    /**
     * Refund a payment
     * 
     * @param string $payId The LengoPay payment ID
     * @param float $amount The amount to refund (in the original currency)
     * @param string|null $reason The reason for the refund
     * @return array
     */
    public function refundPayment(string $payId, float $amount, ?string $reason = null): array
    {
        $baseUrl    = rtrim(config('services.lengopay.base_url'), '/');
        $licenseKey = config('services.lengopay.license_key');

        $payload = [
            'amount' => $amount,
        ];

        if ($reason) {
            $payload['reason'] = $reason;
        }

        $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $licenseKey,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])
            ->post($baseUrl . '/api/v1/payments/' . $payId . '/refund', $payload);

        if ($response->failed()) {
            throw new \Exception('LengoPay refund failed: ' . $response->body());
        }

        $data = $response->json();

        return [
            'success' => true,
            'refund_id' => $data['id'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'amount' => $data['amount'] ?? $amount,
            'message' => $data['message'] ?? 'Refund initiated successfully',
        ];
    }

    /**
     * Check refund status
     * 
     * @param string $refundId The LengoPay refund ID
     * @return array
     */
    public function getRefundStatus(string $refundId): array
    {
        $baseUrl    = rtrim(config('services.lengopay.base_url'), '/');
        $licenseKey = config('services.lengopay.license_key');

        $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $licenseKey,
                'Accept'        => 'application/json',
            ])
            ->get($baseUrl . '/api/v1/refunds/' . $refundId);

        if ($response->failed()) {
            throw new \Exception('Failed to get refund status: ' . $response->body());
        }

        return $response->json();
    }

}
