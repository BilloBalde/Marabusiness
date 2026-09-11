<?php

namespace App\Services;

use App\Models\Order;
use App\Models\CommissionSetting;
// createFinancialTransactionsForOrder() has always used this class without importing
// it, so every call threw "Class App\Services\FinancialTransaction not found" —
// including the one Order::booted() makes when an order is marked delivered.
use App\Models\FinancialTransaction;
use App\Models\PaymentGatewayFee;
use App\Models\WireTransferFee;
use App\Models\Vendor;

class FinanceCalculator
{
    public function calculateOrderBreakdown($order)
    {
        $vendor = $order->vendor;

        // orders.grand_total is nullable, and calculateGatewayFee() below declares a
        // float parameter — passing the raw column through threw a TypeError on a null
        // total, in the same method that already crashed on a zero one.
        $grandTotal = (float) ($order->grand_total ?? 0);

        // Calculate commission
        $commission = $this->calculateCommission($grandTotal, $vendor->id);

        // Calculate gateway fee
        $gatewayFee = $this->calculateGatewayFee($grandTotal, $order->payment_method);

        // Calculate net amount before wire fee
        $amountBeforeWire = $grandTotal - $commission - $gatewayFee;
        
        // Calculate wire fee
        $wireFee = $this->calculateWireFee($amountBeforeWire, $vendor->id);
        
        // Final net amount for vendor
        $netAmount = $amountBeforeWire - $wireFee;
        
        return [
            'gross_amount' => $grandTotal,
            'commission' => $commission,
            'gateway_fee' => $gatewayFee,
            'wire_fee' => $wireFee,
            'net_amount' => $netAmount,
            'breakdown' => [
                'commission_percentage' => $this->asPercentageOf($commission, $grandTotal),
                'gateway_fee_percentage' => $this->asPercentageOf($gatewayFee, $grandTotal),
                'wire_fee_percentage' => $this->asPercentageOf($wireFee, $grandTotal),
                'net_percentage' => $this->asPercentageOf($netAmount, $grandTotal),
            ],
        ];
    }

    /**
     * These four percentages used to divide by grand_total directly, which threw a
     * DivisionByZeroError on any order totalling zero — and orders like that exist. The
     * method is called from both checkout paths and the payment modal, so the crash
     * surfaced as a 500 mid-order. Zero total means zero share, not an error.
     */
    private function asPercentageOf(float $part, $total): float
    {
        $total = (float) $total;

        return $total > 0 ? $part / $total * 100 : 0.0;
    }

    /**
     * La commission de la plateforme sur un montant, pour une boutique.
     *
     * Une ligne propre à la boutique l'emporte sur la règle globale
     * (vendor_id null), et c'est la règle globale qui s'applique à tout le
     * reste.
     *
     * Deux corrections par rapport à la version d'avant, toutes deux visibles
     * sur l'argent :
     *
     * 1. Le filtre is_active se fait dans la requête, pas après. Avant, la ligne
     *    de la boutique était choisie puis rejetée si elle était inactive, et la
     *    méthode renvoyait 0 — sans jamais retomber sur la règle globale.
     *    Désactiver l'exception d'une boutique dans l'admin voulait donc dire
     *    « cette boutique ne paie plus rien », là où l'écran laisse entendre
     *    « cette boutique repasse au taux par défaut ».
     *
     * 2. Le OR est parenthésé. `where(a)->orWhereNull(b)` collé aux autres
     *    conditions les fait fuir hors du OR dès qu'on en ajoute une ; ici la
     *    condition d'activité en serait sortie.
     *
     * Ce que cette méthode ne fait toujours pas : consulter payment_method.
     * La colonne existe, le modèle la documente (« null for all, or 'stripe',
     * 'orange_money' »), et rien ne la lit — une commission propre à un moyen de
     * paiement s'appliquerait donc à tous. La corriger demande de faire
     * descendre le moyen de paiement jusqu'ici, ce qui touche six appelants ;
     * c'est un lot à part, pas un effet de bord de celui-ci.
     */
    public function calculateCommission($amount, $vendorId = null): float
    {
        $commissionSetting = CommissionSetting::query()
            ->where('is_active', true)
            ->where(function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId)
                    ->orWhereNull('vendor_id');
            })
            // La ligne de la boutique d'abord, la règle globale en repli.
            ->orderByRaw('CASE WHEN vendor_id IS NULL THEN 1 ELSE 0 END')
            ->first();

        if (! $commissionSetting) {
            return 0;
        }

        if ($commissionSetting->commission_type === 'percentage') {
            $commission = ($amount * $commissionSetting->commission_rate) / 100;
        } else {
            $commission = $commissionSetting->commission_rate;
        }
        
        // Apply min/max limits
        if ($commissionSetting->minimum_amount && $commission < $commissionSetting->minimum_amount) {
            $commission = $commissionSetting->minimum_amount;
        }
        
        if ($commissionSetting->maximum_amount && $commission > $commissionSetting->maximum_amount) {
            $commission = $commissionSetting->maximum_amount;
        }
        
        return $commission;
    }

    public function calculateGatewayFee(float $amount, string $paymentMethod): float
    {
        $feeSetting = PaymentGatewayFee::where('gateway_name', $paymentMethod)
            ->where('is_active', true)
            ->first();

        if (! $feeSetting) {
            return 0;
        }

        $fee = 0;

        if (in_array($feeSetting->fee_type, ['percentage', 'percentage_plus_fixed'], true)) {
            $fee += ($amount * (float) $feeSetting->percentage_fee) / 100;
        }

        if (in_array($feeSetting->fee_type, ['fixed', 'percentage_plus_fixed'], true)) {
            $fee += (float) $feeSetting->fixed_fee;
        }

        if (!is_null($feeSetting->minimum_fee) && $fee < (float) $feeSetting->minimum_fee) {
            $fee = (float) $feeSetting->minimum_fee;
        }

        if (!is_null($feeSetting->maximum_fee) && $fee > (float) $feeSetting->maximum_fee) {
            $fee = (float) $feeSetting->maximum_fee;
        }

        return (float) round($fee, 2);
    }


    private function applyGatewayMinMax(float $fee, PaymentGatewayFee $feeSetting, Order $order): float
    {
        $rateToUsd = (float) ($order->rate_to_usd ?? $order->vendor?->currency?->rate_to_usd);

        $min = (float) ($feeSetting->minimum_fee ?? 0);
        $max = (float) ($feeSetting->maximum_fee ?? 0);

        // Convert min/max from USD to order currency when needed
        if ($feeSetting->currency === 'USD' && $order->currency !== 'USD' && $rateToUsd > 0) {
            if ($min > 0) $min = $min / $rateToUsd;
            if ($max > 0) $max = $max / $rateToUsd;
        }

        if ($min > 0 && $fee < $min) $fee = $min;
        if ($max > 0 && $fee > $max) $fee = $max;

        return $fee;
    }


    public function calculateWireFee($amount, $vendorId): float
    {
        $vendor = Vendor::find($vendorId);
        if (!$vendor) {
            return 0;
        }
        
        $wireFee = WireTransferFee::query()
            ->where('is_active', true)
            ->where('currency', $vendor->currency->code ?? 'USD')
            ->where(function ($q) use ($vendor) {
                $q->where('country', $vendor->country)
                ->orWhereNull('country');
            })
            ->orderByRaw('country IS NULL') // country-specific first
            ->first();

            
        if (!$wireFee) {
            return 0;
        }
        
        if ($wireFee->fee_type === 'percentage') {
            $fee = ($amount * $wireFee->percentage_fee) / 100;
        } else {
            $fee = $wireFee->fixed_fee;
        }

        if ($wireFee->minimum_amount !== null && $fee < (float) $wireFee->minimum_amount) {
            $fee = (float) $wireFee->minimum_amount;
        }

        if ($wireFee->maximum_amount !== null && $fee > (float) $wireFee->maximum_amount) {
            $fee = (float) $wireFee->maximum_amount;
        }
        
        return $fee;
    }

    public function createFinancialTransactionsForOrder($order)
    {
        $breakdown = $this->calculateOrderBreakdown($order);
        $vendor = $order->vendor;
        $currency = $vendor->currency->code ?? 'USD';
        
        // Check if transactions already exist
        $existing = FinancialTransaction::where('order_id', $order->id)->count();
        if ($existing > 0) {
            return; // Already exists
        }
        
        // 1. Order Revenue
        FinancialTransaction::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'transaction_type' => FinancialTransaction::TYPE_ORDER,
            'amount' => $breakdown['gross_amount'],
            'currency' => $currency,
            'description' => "Order #{$order->order_number}",
            'reference_number' => $order->order_number . '-REV',
            'gateway_fee' => $breakdown['gateway_fee'],
            'commission_fee' => $breakdown['commission'],
            'wire_fee' => $breakdown['wire_fee'],
            'net_amount' => $breakdown['net_amount'],
            'status' => $order->payment_status === 'paid' 
                ? FinancialTransaction::STATUS_PROCESSED 
                : FinancialTransaction::STATUS_PENDING,
            'processed_at' => $order->payment_status === 'paid' ? now() : null,
            'metadata' => [
                'order_number' => $order->order_number,
                'customer_id' => $order->user_id,
                'payment_method' => $order->payment_method,
            ],
        ]);
        
        // 2. Commission (separate transaction for tracking)
        FinancialTransaction::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'transaction_type' => FinancialTransaction::TYPE_COMMISSION,
            'amount' => $breakdown['commission'] * -1,
            'currency' => $currency,
            'description' => "Commission for Order #{$order->order_number}",
            'reference_number' => $order->order_number . '-COMM',
            'gateway_fee' => 0,
            'commission_fee' => $breakdown['commission'],
            'wire_fee' => 0,
            'net_amount' => $breakdown['commission'] * -1,
            'status' => $order->payment_status === 'paid' 
                ? FinancialTransaction::STATUS_PROCESSED 
                : FinancialTransaction::STATUS_PENDING,
            'processed_at' => $order->payment_status === 'paid' ? now() : null,
            'metadata' => [
                'commission_percentage' => $breakdown['breakdown']['commission_percentage'],
            ],
        ]);
        
        // 3. Gateway Fee (separate transaction)
        if ($breakdown['gateway_fee'] > 0) {
            FinancialTransaction::create([
                'order_id' => $order->id,
                'vendor_id' => $vendor->id,
                'transaction_type' => FinancialTransaction::TYPE_GATEWAY_FEE,
                'amount' => $breakdown['gateway_fee'] * -1,
                'currency' => $currency,
                'description' => "Gateway fee for Order #{$order->order_number}",
                'reference_number' => $order->order_number . '-GATE',
                'gateway_fee' => $breakdown['gateway_fee'],
                'commission_fee' => 0,
                'wire_fee' => 0,
                'net_amount' => $breakdown['gateway_fee'] * -1,
                'status' => $order->payment_status === 'paid' 
                    ? FinancialTransaction::STATUS_PROCESSED 
                    : FinancialTransaction::STATUS_PENDING,
                'processed_at' => $order->payment_status === 'paid' ? now() : null,
                'metadata' => [
                    'gateway' => $order->payment_method,
                    'gateway_fee_percentage' => $breakdown['breakdown']['gateway_fee_percentage'],
                ],
            ]);
        }
    }

    public function calculatePendingBalance($vendorId): array
    {
        $orders = Order::where('vendor_id', $vendorId)
            ->where('status', 'delivered')
            ->where('payment_status', 'paid')
            ->whereDoesntHave('financialTransactions', function($q) {
                $q->where('transaction_type', 'payout');
            })
            ->get();
        
        $totalAmount = 0;
        $totalCommission = 0;
        $totalGatewayFees = 0;
        $totalWireFees = 0;
        
        foreach ($orders as $order) {
            $breakdown = $this->calculateOrderBreakdown($order);
            
            $totalAmount += $breakdown['gross_amount'];
            $totalCommission += $breakdown['commission'];
            $totalGatewayFees += $breakdown['gateway_fee'];
            $totalWireFees += $breakdown['wire_fee'];
        }
        
        $netAmount = $totalAmount - $totalCommission - $totalGatewayFees - $totalWireFees;
        
        return [
            'total_amount' => $totalAmount,
            'commission' => $totalCommission,
            'gateway_fees' => $totalGatewayFees,
            'wire_fees' => $totalWireFees,
            'net_amount' => $netAmount,
            'order_count' => $orders->count(),
        ];
    }
}