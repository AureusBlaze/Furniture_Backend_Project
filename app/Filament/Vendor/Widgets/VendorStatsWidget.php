<?php

namespace App\Filament\Vendor\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class VendorStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $vendorId = Auth::user()->vendor->id ?? null;

        if (!$vendorId) {
            return [];
        }

        return [
            Stat::make('My Products', Product::where('vendor_id', $vendorId)->count()),
            Stat::make('Total Orders', Order::whereHas('items.product', function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            })->count()),
        ];
    }
}
