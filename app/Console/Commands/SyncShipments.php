<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Services\Shipping\CarrierTrackingService;
use Illuminate\Console\Command;

class SyncShipments extends Command
{
    protected $signature = 'shipments:sync {shipment_id? : Sync a specific shipment id}';

    protected $description = 'Sync shipment tracking information from carriers';

    public function handle(CarrierTrackingService $service): int
    {
        $shipmentId = $this->argument('shipment_id');

        $shipments = Shipment::query()
            ->when($shipmentId, fn ($query) => $query->whereKey($shipmentId))
            ->when(!$shipmentId, fn ($query) => $query->where('status', '!=', 'delivered'))
            ->get();

        if ($shipments->isEmpty()) {
            $this->info('No shipments to sync.');
            return self::SUCCESS;
        }

        $shipments->each(function (Shipment $shipment) use ($service) {
            try {
                $service->sync($shipment);
                $this->info("Synced shipment {$shipment->id} ({$shipment->tracking_number}).");
            } catch (\Throwable $exception) {
                $this->error("Failed syncing shipment {$shipment->id}: {$exception->getMessage()}");
                report($exception);
            }
        });

        return self::SUCCESS;
    }
}
