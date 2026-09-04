<?php
// app/Http/Controllers/Api/SuccessPageController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuccessPageController extends Controller
{
    /**
     * Get today's orders for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get today's orders with all relationships
            $orders = Order::with([
                'address', 
                'items.product', 
                'vendor.currency', 
                'latestShipment'
            ])
            ->where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->latest()
            ->get();

            // Format orders using the same formatter as OrderController
            $formattedOrders = $orders->map(function ($order) {
                return $this->formatOrder($order, true);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $formattedOrders,
                    'count' => $orders->count(),
                    'total_amount' => $orders->sum('grand_total_usd'),
                ],
                'message' => 'Orders retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format order (copied from OrderController)
     */
    private function formatOrder($order, $detailed = false)
    {
        $formatted = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'created_at' => $order->created_at->toDateTimeString(),
            'status' => $order->status,
            'status_label' => $this->getStatusLabel($order->status),
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'payment_status_label' => $this->getPaymentStatusLabel($order->payment_status),
            'grand_total' => (float) $order->grand_total,
            'total_paid' => (float) $order->total_paid,
            'total_remaining' => (float) $order->total_remaining,
            'shipping_amount' => (float) $order->shipping_amount,
            'currency' => $order->currency?->code ?? $order->vendor?->currency?->code ?? 'USD',
            'vendor_id' => $order->vendor_id,
            'vendor_name' => $order->vendor?->store_name,
            'items_count' => $order->items->count(),
            'tracking_number' => $order->tracking_number,
            'carrier' => $order->shipping_carrier,
            'estimated_delivery' => $order->estimated_delivery?->toDateString(),
        ];

        if ($detailed) {
            $formatted['items'] = $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'product_image' => $item->product?->images[0] ?? null,
                    'quantity' => $item->quantity,
                    'unit_amount' => (float) $item->unit_amount,
                    'total_amount' => (float) $item->total_amount,
                    'variation_json' => $item->variation_json,
                    'has_review' => $item->has_review ?? false,
                ];
            })->toArray();

            if ($order->address) {
                $formatted['shipping_address'] = [
                    'id' => $order->address->id,
                    'first_name' => $order->address->first_name,
                    'last_name' => $order->address->last_name,
                    'phone' => $order->address->phone,
                    'street_address' => $order->address->street_address,
                    'city' => $order->address->city,
                    'state' => $order->address->state,
                    'zip_code' => $order->address->zip_code,
                    'country' => $order->address->country,
                    'is_default' => $order->address->is_default ?? false,
                ];
            }

            $formatted['vendor'] = $order->vendor ? [
                'id' => $order->vendor->id,
                'store_name' => $order->vendor->store_name,
                'slug' => $order->vendor->slug,
                'currency_code' => $order->vendor->currency?->code ?? 'USD',
            ] : null;

            if ($order->latestShipment) {
                $formatted['latest_shipment'] = [
                    'carrier' => $order->latestShipment->carrier,
                    'tracking_number' => $order->latestShipment->tracking_number,
                    'status' => $order->latestShipment->status,
                    'current_location' => $order->latestShipment->current_location,
                ];
            }
        }

        return $formatted;
    }

    private function getStatusLabel($status)
    {
        return match($status) {
            'new' => 'Nouvelle',
            'pending' => 'En attente',
            'processing' => 'En traitement',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
            default => ucfirst($status)
        };
    }

    private function getPaymentStatusLabel($status)
    {
        return match($status) {
            'paid' => 'Payée',
            'partial' => 'Partielle',
            'pending' => 'En attente',
            'refunded' => 'Remboursée',
            default => ucfirst($status)
        };
    }
}