<?php

namespace App\Livewire\Vendor\Dashboard\Pages;

use App\Models\Order;
use App\Models\VendorPayout;
use App\Models\FinancialTransaction;
use App\Services\FinanceCalculator;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class VendorFinanceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string $view = 'livewire.dashboard.vendor-finance-dashboard';
    
    public $dateRange = 'month';
    public $startDate;
    public $endDate;
    
    protected static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->hasRole('vendor');
    }
    
    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
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

    public function getVendor()
    {
        return Auth::user()->vendor;
    }

    public function getStatsOverview(): array
    {
        $vendor = $this->getVendor();
        $financeCalculator = new FinanceCalculator();
        
        $pendingOrders = $this->getPendingOrders();
        $pendingBalance = $financeCalculator->calculatePendingBalance($vendor->id);
        $totalPayouts = $this->getTotalPayouts();
        $revenue = $this->getTotalRevenue();

        return [
            Stat::make('Total Revenue', $this->formatCurrency($revenue))
                ->description('All-time sales')
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),
                
            Stat::make('Pending Balance', $this->formatCurrency($pendingBalance['net_amount']))
                ->description('Ready for payout')
                ->color('warning')
                ->icon('heroicon-o-clock'),
                
            Stat::make('Total Payouts', $this->formatCurrency($totalPayouts))
                ->description('Amount received')
                ->color('primary')
                ->icon('heroicon-o-banknotes'),
                
            Stat::make('Pending Orders', $pendingOrders->count())
                ->description('Awaiting delivery')
                ->color('info')
                ->icon('heroicon-o-shopping-cart'),
        ];
    }

    public function getPendingOrders()
    {
        $vendor = $this->getVendor();
        
        return Order::where('vendor_id', $vendor->id)
            ->where('status', 'delivered')
            ->where('payment_status', 'paid')
            ->whereDoesntHave('financialTransactions', function($q) {
                $q->where('transaction_type', 'payout');
            })
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get();
    }

    public function getTotalRevenue(): float
    {
        $vendor = $this->getVendor();
        
        return Order::where('vendor_id', $vendor->id)
            ->where('status', 'delivered')
            ->sum('grand_total');
    }

    public function getTotalPayouts(): float
    {
        $vendor = $this->getVendor();
        
        return VendorPayout::where('vendor_id', $vendor->id)
            ->where('status', 'completed')
            ->sum('net_amount');
    }

    public function getRecentPayouts()
    {
        $vendor = $this->getVendor();
        
        return VendorPayout::where('vendor_id', $vendor->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function getFinancialTransactions()
    {
        $vendor = $this->getVendor();
        
        return FinancialTransaction::where('vendor_id', $vendor->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }

    public function formatCurrency($amount): string
    {
        $vendor = $this->getVendor();
        $currency = $vendor->currency->symbol ?? '$';
        
        return $currency . number_format($amount, 2);
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