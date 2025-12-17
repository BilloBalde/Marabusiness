<?php

namespace App\Services;

use App\Models\Address;

class ZoneCalculatorService
{
    public function calculateZoneForAddress(Address $address, array $vendorLocation): ?string
    {
        if (!$address->latitude || !$address->longitude) {
            return null;
        }
        
        $distance = $this->calculateDistance(
            $address->latitude,
            $address->longitude,
            $vendorLocation['latitude'],
            $vendorLocation['longitude']
        );
        
        // Your business logic here
        if ($distance < 5) return 'Zone A';
        if ($distance < 15) return 'Zone B';
        return 'Zone C';
    }
    
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // Kilometers
        
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}