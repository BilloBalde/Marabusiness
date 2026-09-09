<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\VendorProduct;
use App\Models\VendorProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Get user's orders with counts
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            $query = Order::with(['vendor.currency', 'items.product'])
                ->where('user_id', $user->id);

            // Search by order number
            if ($request->has('search') && !empty($request->search)) {
                $query->where('order_number', 'like', '%' . $request->search . '%');
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Get status counts before pagination
            $statusCounts = [
                'all' => Order::where('user_id', $user->id)->count(),
                'new' => Order::where('user_id', $user->id)->where('status', 'new')->count(),
                'pending' => Order::where('user_id', $user->id)->where('status', 'pending')->count(),
                'processing' => Order::where('user_id', $user->id)->where('status', 'processing')->count(),
                'shipped' => Order::where('user_id', $user->id)->where('status', 'shipped')->count(),
                'delivered' => Order::where('user_id', $user->id)->where('status', 'delivered')->count(),
                'cancelled' => Order::where('user_id', $user->id)->where('status', 'cancelled')->count(),
            ];

            // Pagination
            $perPage = $request->get('per_page', 15);
            $orders = $query->latest()->paginate($perPage);

            // Transform orders
            $orders->getCollection()->transform(function ($order) {
                return $this->formatOrder($order);
            });

            return response()->json([
                'success' => true,
                'data' => $orders,
                'meta' => [
                    'total' => $statusCounts['all'],
                    'counts' => $statusCounts,
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
     * Get single order details
     */
    public function show($orderId)
    {
        try {
            $user = Auth::user();

            $order = Order::with([
                'vendor.currency',
                'items.product',
                'address',
                'paiements',
                'latestShipment'
            ])->where('user_id', $user->id)
              ->findOrFail($orderId);

            return response()->json([
                'success' => true,
                'data' => $this->formatOrder($order, true),
                'message' => 'Order retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Cancel order
     */
    public function cancel($orderId, Request $request)
    {
        try {
            $user = Auth::user();

            $order = Order::where('user_id', $user->id)
                ->whereIn('status', ['new', 'pending'])
                ->findOrFail($orderId);

            DB::beginTransaction();

            $order->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->reason,
                'cancelled_at' => now()
            ]);

            foreach ($order->items as $item) {
                $this->restoreStock($order, $item);
            }

            DB::commit();

            // Refresh order with relationships
            $order->load(['vendor.currency', 'items.product', 'address', 'latestShipment']);

            return response()->json([
                'success' => true,
                'data' => $this->formatOrder($order, true),
                'message' => 'Order cancelled successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore stock - IDENTICAL to your Livewire implementation
     */
    private function restoreStock($order, $item)
    {
        // Check if this is a variation (same logic as your Livewire)
        if ($item->variation_json && isset($item->variation_json['variation_id'])) {
            // Update variation stock
            $variation = VendorProductVariation::where('vendor_product_id', $item->vendor_product_id)
                ->where('id', $item->variation_json['variation_id'])
                ->first();
            
            if ($variation) {
                $variation->stock += $item->quantity;
                $variation->save();
                
                // Also update parent vendor product stock (sum of all variations)
                $totalVariationStock = VendorProductVariation::where('vendor_product_id', $item->vendor_product_id)
                    ->sum('stock');
                
                $vp = VendorProduct::find($item->vendor_product_id);
                if ($vp) {
                    $vp->stock = $totalVariationStock;
                    $vp->save();
                }
            }
        } else {
            // Regular product - use vendor_id and product_id, NOT vendor_product_id
            $vp = VendorProduct::where('vendor_id', $order->vendor_id)
                ->where('product_id', $item->product_id)
                ->first();
            
            if ($vp) {
                $vp->stock += $item->quantity;
                $vp->save();
            }
        }
    }

    /**
     * Track order
     */
    public function track($orderId)
    {
        try {
            $user = Auth::user();

            $order = Order::where('user_id', $user->id)
                ->with(['latestShipment'])
                ->findOrFail($orderId);

            $tracking = [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'estimated_delivery' => $order->estimated_delivery?->toDateString(),
                'shipment' => null
            ];

            if ($order->latestShipment) {
                $tracking['shipment'] = [
                    'carrier' => $order->latestShipment->carrier,
                    'tracking_number' => $order->latestShipment->tracking_number,
                    'status' => $order->latestShipment->status,
                    'current_location' => $order->latestShipment->current_location,
                    'tracking_url' => $this->getTrackingUrl(
                        $order->latestShipment->carrier,
                        $order->latestShipment->tracking_number
                    )
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $tracking,
                'message' => 'Tracking info retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking info',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order invoice
     */
    public function invoice($orderId)
    {
        try {
            $user = Auth::user();

            $order = Order::with([
                'vendor',
                'items.product',
                'address'
            ])->where('user_id', $user->id)
              ->findOrFail($orderId);

            // Generate invoice URL (you might have a dedicated invoice generator)
            $invoiceUrl = url("/api/v1/orders/{$orderId}/invoice/download");

            return response()->json([
                'success' => true,
                'data' => [
                    'invoice_url' => $invoiceUrl,
                    'order' => $this->formatOrder($order, true)
                ],
                'message' => 'Invoice retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoice($orderId)
    {
        try {
            $user = Auth::user();

            $order = Order::where('user_id', $user->id)->findOrFail($orderId);

            // Generate PDF logic here
            // Return PDF file download

            return response()->json([
                'success' => true,
                'message' => 'Invoice download not implemented yet'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format order for API response
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
            'rate_to_usd' => $order->vendor->currency->rate_to_usd ?? 1,
            'total_paid' => (float) $order->total_paid,
            'total_remaining' => (float) $order->total_remaining,
            // Money the buyer says they have paid and the vendor has not confirmed
            // yet. Without this the app cannot tell "not paid" from "paid, waiting
            // on the vendor" — both read as payment_status 'pending' — so it offers
            // the payment button again to someone who has already handed over cash,
            // and they can pay twice. The web has this guard
            // (Order::declaredAwaitingConfirmation, used by PaiementModal); this is
            // the same signal, exposed so the app can use it too.
            'declared_awaiting_confirmation' => (float) $order->declaredAwaitingConfirmation(),
            'shipping_amount' => (float) $order->shipping_amount,
            'currency' => $order->currency?->code ?? $order->vendor?->currency?->code ?? 'USD',
            'vendor_id' => $order->vendor_id,
            'vendor_name' => $order->vendor?->store_name,
            'items_count' => $order->items->count(),
            'tracking_number' => $order->tracking_number,
            'carrier' => $order->shipping_carrier,
            'estimated_delivery' => $order->estimated_delivery?->toDateString(),
            // Add cancellation fields
            'cancelled_at' => $order->cancelled_at?->toDateTimeString(),
            'cancellation_reason' => $order->cancellation_reason,
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
                    'variation' => $item->variation_json,
                    'has_review' => $item->has_review ?? false,
                ];
            })->toArray();

            // Shipping address
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

            // Payments
            //
            // 'status' used to be paiements.payment_status, which says 'paid' on a
            // declaration the vendor has not confirmed and that does not count
            // towards the balance — so the API reported a payment as paid while
            // reporting the order it belongs to as pending, on the same response.
            // Only confirmed_at decides whether money actually arrived, so that is
            // what these fields are derived from now.
            //
            // 'paid_at' was created_at: the moment the buyer *said* they paid,
            // presented as the moment it was paid. It now carries confirmed_at
            // (null until the vendor confirms), and the declaration time moved to
            // its own honestly-named field. The Dart model already parses paid_at
            // as a nullable DateTime, so a null is handled there.
            $formatted['paiements'] = $order->paiements?->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'method' => $payment->payment_method,
                    'status' => $payment->isConfirmed() ? 'confirmed' : 'awaiting_confirmation',
                    'confirmed' => $payment->isConfirmed(),
                    'transaction_id' => $payment->transaction_id,
                    'declared_at' => $payment->created_at->toDateTimeString(),
                    'paid_at' => $payment->confirmed_at?->toDateTimeString(),
                ];
            })->toArray();

            // Latest shipment
            if ($order->latestShipment) {
                $formatted['latest_shipment'] = [
                    'carrier' => $order->latestShipment->carrier,
                    'tracking_number' => $order->latestShipment->tracking_number,
                    'status' => $order->latestShipment->status,
                    'status_label' => $this->getShipmentStatusLabel($order->latestShipment->status),
                    'current_location' => $order->latestShipment->current_location,
                    'estimated_delivery_at' => $order->latestShipment->estimated_delivery_at?->toDateString(),
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
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
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

    private function getShipmentStatusLabel($status)
    {
        return match($status) {
            'pending' => 'En attente',
            'in_transit' => 'En transit',
            'shipped' => 'Expédié',
            'delivered' => 'Livré',
            default => ucfirst($status)
        };
    }

    private function getTrackingUrl($carrier, $trackingNumber)
    {
        $urls = [
            'dhl' => "https://www.dhl.com/global-en/home/tracking.html?tracking-id={$trackingNumber}",
            'ups' => "https://www.ups.com/track?loc=en_US&tracknum={$trackingNumber}",
            'fedex' => "https://www.fedex.com/fedextrack/?tracknumbers={$trackingNumber}",
            'chrono' => "https://www.chronopost.fr/tracking-no-cms/suivi-page?listeNumerosLT={$trackingNumber}",
        ];

        return $urls[$carrier] ?? null;
    }
}