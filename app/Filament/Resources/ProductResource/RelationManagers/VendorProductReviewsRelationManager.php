<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\VendorProductReview;
use App\Models\VendorProduct;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorProductReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendors';

    protected static ?string $title = 'Vendor Reviews';
    
    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $panel = Filament::getCurrentPanel()->getId();
                $user  = Filament::auth()->user();

                // 🔥 Vendor Panel → Only reviews for this vendor
                if ($panel === 'vendor') {
                    return $query->where('vendor_id', $user->vendor_id);
                }

                // 🔥 Admin Panel → all vendor products
                return $query;
            })

            ->columns([

                // ❌ REMOVE sortable() / searchable() → breaks SQLite
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor'),

                Tables\Columns\TextColumn::make('reviews_count')
                    ->label('Reviews')
                    ->counts('reviews'),

                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Avg Rating')
                    ->state(fn(VendorProduct $vp) => number_format($vp->averageRating(), 1) . ' ★'),
            ])

            // ❌ MUST REMOVE: sorting by relationship field breaks SQLite
            // ->defaultSort('vendor.name')

            ->actions([
                Tables\Actions\Action::make('ViewReviews')
                    ->label('View Reviews')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Vendor Reviews')
                    ->modalContent(function (VendorProduct $record) {
                        $reviews = $record->reviews()->with('user')->latest()->get();

                        return view('filament.vendor-product-reviews-modal', [
                            'reviews' => $reviews,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->headerActions([])
            ->actions([]);
    }
}
