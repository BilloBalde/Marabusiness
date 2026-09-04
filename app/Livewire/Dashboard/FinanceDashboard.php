<?php

namespace App\Livewire\Dashboard\Pages;

use App\Models\Order;
use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Models\FinancialTransaction;
use App\Models\CommissionSetting;
use App\Models\PaymentGatewayFee;
use App\Models\WireTransferFee;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinanceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string $view = 'livewire.dashboard.finance-dashboard';
    
    public $dateRange = 'month';
    public $startDate;
    public $endDate;
    public $selectedVendorId = null;
    
    // Commission settings
    public $commissionRate;
    public $commissionType = 'percentage';
    public $paymentMethodFeeSettings = [];
    
    // Payout form
    public $payoutVendorId;
    public $payoutMethod = 'bank_wire';
    public $payoutNotes = '';
    public $showingPayoutModal = false;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
        
        // Load default commission settings
        $globalCommission = CommissionSetting::whereNull('vendor_id')->first();
        if ($globalCommission) {
            $this->commissionRate = $globalCommission->commission_rate;
            $this->commissionType = $globalCommission->commission_type;
        }
        
        // Load payment method fees
        $this->paymentMethodFeeSettings = PaymentGatewayFee::where('is_active', true)
            ->get()
            ->keyBy('gateway_name')
            ->toArray();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.finance');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.finance');
    }

    public function getHeading(): string
    {
        return __('filament.nav.finance');
    }

    // Get statistics
    public function getStatsOverview(): array
    {
        $revenue = $this->getTotalRevenue();
        $commissions = $this->getTotalCommissions();
        $gatewayFees = $this->getTotalGatewayFees();
        $netRevenue = $revenue - $commissions - $gatewayFees;

        return [
            Stat::make('Total Revenue', $this->formatCurrency($revenue))
                ->description('Gross revenue from all orders')
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),
                
            Stat::make('Total Commissions', $this->formatCurrency($commissions))
                ->description('Commission to admin')
                ->color('primary')
                ->icon('heroicon-o-banknotes'),
                
            Stat::make('Gateway Fees', $this->formatCurrency($gatewayFees))
                ->description('Payment processing fees')
                ->color('warning')
                ->icon('heroicon-o-credit-card'),
                
            Stat::make('Net Revenue', $this->formatCurrency($netRevenue))
                ->description('After commissions & fees')
                ->color('success')
                ->icon('heroicon-o-chart-bar'),
        ];
    }

    // Get vendor statistics
    public function getVendorStats($vendorId = null)
    {
        $query = Order::query()
            ->where('status', 'delivered')
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);
            
        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }
        
        $orders = $query->get();
        
        $totalAmount = $orders->sum('grand_total');
        $totalCommission = 0;
        $totalGatewayFees = 0;
        
        foreach ($orders as $order) {
            $commission = $this->calculateCommission($order->grand_total, $order->vendor_id);
            $gatewayFee = $this->calculateGatewayFee($order->grand_total, $order->payment_method);
            
            $totalCommission += $commission;
            $totalGatewayFees += $gatewayFee;
        }
        
        $netAmount = $totalAmount - $totalCommission - $totalGatewayFees;
        
        return [
            'total_amount' => $totalAmount,
            'commission' => $totalCommission,
            'gateway_fees' => $totalGatewayFees,
            'net_amount' => $netAmount,
            'order_count' => $orders->count(),
        ];
    }

    public function getTotalRevenue(): float
    {
        return Order::where('status', 'delivered')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->when($this->selectedVendorId, fn($q) => $q->where('vendor_id', $this->selectedVendorId))
            ->sum('grand_total');
    }

    public function getTotalCommissions(): float
    {
        $orders = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->when($this->selectedVendorId, fn($q) => $q->where('vendor_id', $this->selectedVendorId))
            ->get();
            
        $totalCommission = 0;
        foreach ($orders as $order) {
            $totalCommission += $this->calculateCommission($order->grand_total, $order->vendor_id);
        }
        
        return $totalCommission;
    }

    public function getTotalGatewayFees(): float
    {
        $orders = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->when($this->selectedVendorId, fn($q) => $q->where('vendor_id', $this->selectedVendorId))
            ->get();
            
        $totalFees = 0;
        foreach ($orders as $order) {
            $totalFees += $this->calculateGatewayFee($order->grand_total, $order->payment_method);
        }
        
        return $totalFees;
    }

    public function calculateCommission($amount, $vendorId = null): float
    {
        // Get commission setting for vendor or global
        $commissionSetting = CommissionSetting::where('vendor_id', $vendorId)
            ->orWhereNull('vendor_id')
            ->orderBy('vendor_id', 'desc') // Vendor-specific first
            ->first();
            
        if (!$commissionSetting) {
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

    public function calculateGatewayFee($amount, $paymentMethod): float
    {
        $feeSetting = PaymentGatewayFee::where('gateway_name', $paymentMethod)
            ->where('is_active', true)
            ->first();
            
        if (!$feeSetting) {
            return 0;
        }
        
        $fee = 0;
        
        if ($feeSetting->percentage_fee) {
            $fee += ($amount * $feeSetting->percentage_fee) / 100;
        }
        
        if ($feeSetting->fixed_fee) {
            $fee += $feeSetting->fixed_fee;
        }
        
        // Apply min/max limits
        if ($feeSetting->minimum_fee && $fee < $feeSetting->minimum_fee) {
            $fee = $feeSetting->minimum_fee;
        }
        
        if ($feeSetting->maximum_fee && $fee > $feeSetting->maximum_fee) {
            $fee = $feeSetting->maximum_fee;
        }
        
        return $fee;
    }

    public function calculateWireFee($amount, $vendorId): float
    {
        $vendor = Vendor::find($vendorId);
        if (!$vendor) {
            return 0;
        }
        
        $wireFee = WireTransferFee::where('country', $vendor->country)
            ->where('currency', $vendor->currency->code ?? 'USD')
            ->where('is_active', true)
            ->first();
            
        if (!$wireFee) {
            return 0;
        }
        
        $fee = 0;
        
        if ($wireFee->fee_type === 'percentage') {
            $fee = ($amount * $wireFee->percentage_fee) / 100;
        } else {
            $fee = $wireFee->fixed_fee;
        }
        
        return $fee;
    }

    public function getVendorsWithBalance()
    {
        return Vendor::with(['currency'])
            ->whereHas('orders', function($query) {
                $query->where('status', 'delivered')
                      ->where('payment_status', 'paid')
                      ->whereDoesntHave('financialTransactions', function($q) {
                          $q->where('transaction_type', FinancialTransaction::TYPE_PAYOUT);
                      });
            })
            ->get()
            ->map(function($vendor) {
                $pendingOrders = $vendor->orders()
                    ->where('status', 'delivered')
                    ->where('payment_status', 'paid')
                    ->whereDoesntHave('financialTransactions', function($q) {
                        $q->where('transaction_type', FinancialTransaction::TYPE_PAYOUT);
                    })
                    ->get();
                
                $totalAmount = $pendingOrders->sum('grand_total');
                $totalCommission = 0;
                $totalGatewayFees = 0;
                
                foreach ($pendingOrders as $order) {
                    $totalCommission += $this->calculateCommission($order->grand_total, $vendor->id);
                    $totalGatewayFees += $this->calculateGatewayFee($order->grand_total, $order->payment_method);
                }
                
                $wireFee = $this->calculateWireFee($totalAmount - $totalCommission - $totalGatewayFees, $vendor->id);
                $netAmount = $totalAmount - $totalCommission - $totalGatewayFees - $wireFee;
                
                return [
                    'vendor' => $vendor,
                    'pending_orders' => $pendingOrders,
                    'total_amount' => $totalAmount,
                    'commission' => $totalCommission,
                    'gateway_fees' => $totalGatewayFees,
                    'wire_fee' => $wireFee,
                    'net_amount' => $netAmount,
                ];
            })
            ->filter(fn($item) => $item['net_amount'] > 0)
            ->values();
    }

    public function getRecentPayouts()
    {
        return VendorPayout::with(['vendor', 'processedBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function getPaymentMethodDistribution()
    {
        return Order::select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(grand_total) as total'))
            ->where('status', 'delivered')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->groupBy('payment_method')
            ->get()
            ->map(fn($item) => [
                'method' => $item->payment_method,
                'count' => $item->count,
                'total' => $item->total,
                'percentage' => $this->getTotalRevenue() > 0 ? ($item->total / $this->getTotalRevenue() * 100) : 0,
            ]);
    }

    public function saveCommissionSettings()
    {
        $this->validate([
            'commissionRate' => 'required|numeric|min:0',
            'commissionType' => 'required|in:percentage,fixed',
        ]);

        CommissionSetting::updateOrCreate(
            ['vendor_id' => null],
            [
                'commission_type' => $this->commissionType,
                'commission_rate' => $this->commissionRate,
                'is_active' => true,
            ]
        );

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Commission settings saved successfully.',
        ]);
    }

    public function initiatePayout($vendorId)
    {
        $this->payoutVendorId = $vendorId;
        $this->showingPayoutModal = true;
    }

    public function processPayout()
    {
        $this->validate([
            'payoutVendorId' => 'required|exists:vendors,id',
            'payoutMethod' => 'required|in:bank_wire,orange_money,stripe_transfer',
            'payoutNotes' => 'nullable|string|max:500',
        ]);

        $vendor = Vendor::find($this->payoutVendorId);
        $pendingData = $this->getVendorsWithBalance()->firstWhere('vendor.id', $this->payoutVendorId);

        if (!$pendingData || $pendingData['net_amount'] <= 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No pending balance for this vendor.',
            ]);
            return;
        }

        DB::beginTransaction();

        try {
            // Create payout record
            $payout = VendorPayout::create([
                'vendor_id' => $vendor->id,
                'amount' => $pendingData['total_amount'],
                'currency' => $vendor->currency->code ?? 'USD',
                'commission_amount' => $pendingData['commission'],
                'gateway_fees' => $pendingData['gateway_fees'],
                'wire_fees' => $pendingData['wire_fee'],
                'net_amount' => $pendingData['net_amount'],
                'payout_method' => $this->payoutMethod,
                'payout_details' => [
                    'method' => $this->payoutMethod,
                    'vendor_bank' => $vendor->bank_account_info ?? null,
                    'vendor_orange_money' => $vendor->orange_money_number ?? null,
                    'notes' => $this->payoutNotes,
                ],
                'status' => VendorPayout::STATUS_PENDING,
                'reference_number' => 'PAYOUT-' . strtoupper(uniqid()),
                'processed_by' => auth()->id(),
                'notes' => $this->payoutNotes,
            ]);

            // Create financial transactions for each pending order
            foreach ($pendingData['pending_orders'] as $order) {
                FinancialTransaction::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendor->id,
                    'transaction_type' => FinancialTransaction::TYPE_PAYOUT,
                    'amount' => $order->grand_total,
                    'currency' => $vendor->currency->code ?? 'USD',
                    'description' => 'Payout for order #' . $order->order_number,
                    'reference_number' => $payout->reference_number,
                    'gateway_fee' => $this->calculateGatewayFee($order->grand_total, $order->payment_method),
                    'commission_fee' => $this->calculateCommission($order->grand_total, $vendor->id),
                    'wire_fee' => $this->calculateWireFee($order->grand_total, $vendor->id),
                    'net_amount' => $order->grand_total - 
                        $this->calculateGatewayFee($order->grand_total, $order->payment_method) -
                        $this->calculateCommission($order->grand_total, $vendor->id) -
                        $this->calculateWireFee($order->grand_total, $vendor->id),
                    'status' => FinancialTransaction::STATUS_PENDING,
                    'metadata' => [
                        'payout_id' => $payout->id,
                        'order_number' => $order->order_number,
                    ],
                ]);
            }

            DB::commit();

            $this->showingPayoutModal = false;
            $this->reset(['payoutVendorId', 'payoutMethod', 'payoutNotes']);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Payout initiated successfully. Reference: ' . $payout->reference_number,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to process payout: ' . $e->getMessage(),
            ]);
        }
    }

    public function completePayout($payoutId)
    {
        $payout = VendorPayout::find($payoutId);
        
        if (!$payout) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Payout not found.',
            ]);
            return;
        }

        DB::beginTransaction();

        try {
            // Update payout status
            $payout->update([
                'status' => VendorPayout::STATUS_COMPLETED,
                'processed_at' => now(),
            ]);

            // Update financial transactions
            FinancialTransaction::where('reference_number', $payout->reference_number)
                ->update([
                    'status' => FinancialTransaction::STATUS_PROCESSED,
                    'processed_at' => now(),
                ]);

            DB::commit();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Payout marked as completed.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to complete payout: ' . $e->getMessage(),
            ]);
        }
    }

    public function formatCurrency($amount): string
    {
        // You might want to use the system's base currency
        return '$' . number_format($amount, 2);
    }

    public function updatedDateRange($value)
    {
        switch ($value) {
            case 'today':
                $this->startDate = now()->startOfDay()->toDateString();
                $this->endDate = now()->endOfDay()->toDateString();
                break;
            case 'week':
                $this->startDate = now()->startOfWeek()->toDateString();
                $this->endDate = now()->endOfWeek()->toDateString();
                break;
            case 'month':
                $this->startDate = now()->startOfMonth()->toDateString();
                $this->endDate = now()->endOfMonth()->toDateString();
                break;
            case 'quarter':
                $this->startDate = now()->startOfQuarter()->toDateString();
                $this->endDate = now()->endOfQuarter()->toDateString();
                break;
            case 'year':
                $this->startDate = now()->startOfYear()->toDateString();
                $this->endDate = now()->endOfYear()->toDateString();
                break;
        }
    }
}