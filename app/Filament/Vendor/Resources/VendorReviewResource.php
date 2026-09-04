<?php

namespace App\Filament\Vendor\Resources;

use App\Models\VendorReview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class VendorReviewResource extends Resource
{
    protected static ?string $model = VendorReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Reviews';
    protected static ?string $navigationLabel = 'My Reviews';
    protected static ?string $pluralLabel = 'My Reviews';
    protected static ?string $slug = 'vendor-reviews';

    // Only show reviews for this vendor
    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        $vendorId = $user?->vendor?->id ?? $user?->vendor_id;

        return parent::getEloquentQuery()
            ->where('vendor_id', $vendorId)
            ->with(['user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Review Information')
                    ->schema([
                        Forms\Components\Placeholder::make('user_name')
                            ->label('Customer')
                            ->content(fn ($record): string => $record?->user?->name ?? 'N/A'),

                        Forms\Components\Placeholder::make('rating')
                            ->label('Rating')
                            ->content(fn ($record): string => $record ? '⭐ ' . $record->rating . '/5' : 'N/A'),

                        Forms\Components\Placeholder::make('created_at')
                            ->label('Submitted On')
                            ->content(fn ($record): string => $record?->created_at?->format('M d, Y H:i') ?? 'N/A'),

                        Forms\Components\Textarea::make('comment')
                            ->label('Review Comment')
                            ->disabled()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Moderation')
                    ->schema([
                        Forms\Components\Toggle::make('is_approved')
                            ->label('Approve this review')
                            ->helperText('Approved reviews will be visible to customers on your store page.')
                            ->required(),

                        Forms\Components\Placeholder::make('approval_note')
                            ->label('Note')
                            ->content('Once approved, this review will appear publicly and contribute to your overall rating.')
                            ->extraAttributes(['class' => 'text-sm text-gray-500']),
                    ])
                    ->visible(fn ($record): bool => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state): string => '⭐ ' . $state . '/5')
                    ->sortable(),

                Tables\Columns\TextColumn::make('comment')
                    ->label('Review')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_approved')
                    ->label('Approved')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_approved')
                    ->label('Approval Status')
                    ->options([
                        '1' => 'Approved',
                        '0' => 'Pending',
                    ]),

                Tables\Filters\SelectFilter::make('rating')
                    ->label('Rating')
                    ->options([
                        '5' => '5 Stars',
                        '4' => '4 Stars',
                        '3' => '3 Stars',
                        '2' => '2 Stars',
                        '1' => '1 Star',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Approve/Reject'),
                
                /* Tables\Actions\Action::make('view_customer')
                    ->label('View Customer')
                    ->icon('heroicon-o-user')
                    ->url(fn ($record): string => route('filament.vendor.resources.customers.edit', $record->user_id))
                    ->openUrlInNewTab(), */
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_approved' => true])),
                    
                    Tables\Actions\BulkAction::make('reject')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_approved' => false])),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => VendorReviewResource\Pages\ListVendorReviews::route('/'),
            'edit' => VendorReviewResource\Pages\EditVendorReview::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Filament::auth()->user();
        $vendorId = $user?->vendor?->id ?? $user?->vendor_id;

        $pendingCount = VendorReview::where('vendor_id', $vendorId)
            ->where('is_approved', false)
            ->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}