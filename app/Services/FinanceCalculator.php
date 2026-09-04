<?php

namespace App\Services;

use App\Models\Order;
use App\Models\CommissionSetting;
use App\Models\PaymentGatewayFee;
use App\Models\WireTransferFee;
use App\Models\Vendor;

class FinanceCalculator
{
    public function calculateOrderBreakdown($order)
    {
        $vendor = $order->vendor;
        
        // Calculate commission
        $commission = $this->calculateCommission($order->grand_total, $vendor->id);
        
        // Calculate gateway fee
        $gatewayFee = $this->calculateGatewayFee($order->grand_total, $order->payment_method);
        
        // Calculate net amount before wire fee
        $amountBeforeWire = $order->grand_total - $commission - $gatewayFee;
        
        // Calculate wire fee
        $wireFee = $this->calculateWireFee($amountBeforeWire, $vendor->id);
        
        // Final net amount for vendor
        $netAmount = $amountBeforeWire - $wireFee;
        
        return [
            'gross_amount' => $order->grand_total,
            'commission' => $commission,
            'gateway_fee' => $gatewayFee,
            'wire_fee' => $wireFee,
            'net_amount' => $netAmount,
            'breakdown' => [
                'commission_percentage' => $commission / $order->grand_total * 100,
                'gateway_fee_percentage' => $gatewayFee / $order->grand_total * 100,
                'wire_fee_percentage' => $wireFee / $order->grand_total * 100,
                'net_percentage' => $netAmount / $order->grand_total * 100,
            ],
        ];
    }

    public function calculateCommission($amount, $vendorId = null): float
    {
        // Get commission setting for vendor or global
        $commissionSetting = CommissionSetting::where('vendor_id', $vendorId)
            ->orWhereNull('vendor_id')
            ->orderBy('vendor_id', 'desc') // Vendor-specific first
            ->first();
            
        if (!$commissionSetting || !$commissionSetting->is_active) {
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