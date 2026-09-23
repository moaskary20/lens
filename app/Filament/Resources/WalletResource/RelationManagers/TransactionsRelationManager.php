<?php

namespace App\Filament\Resources\WalletResource\RelationManagers;

use App\Support\Finance;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Transaction history';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('type')->label('Type')->badge()->formatStateUsing(fn (string $state): string => str_replace('_', ' ', $state)),
                TextColumn::make('amount')->label('Amount')->money(Finance::currency()),
                TextColumn::make('available_delta')->label('Available Δ')->numeric(decimalPlaces: 2),
                TextColumn::make('pending_delta')->label('Pending Δ')->numeric(decimalPlaces: 2),
                TextColumn::make('coupon_delta')->label('Coupon Δ')->numeric(decimalPlaces: 2),
                TextColumn::make('booking.reference')->label('Session')->placeholder('—'),
                TextColumn::make('notes')->label('Notes')->limit(48)->wrap(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'payment' => 'Payment',
                    'coupon' => 'Coupon',
                    'refund' => 'Refund',
                    'earning_hold' => 'Pending earnings',
                    'earning_release' => 'Available earnings',
                    'commission' => 'Commission',
                    'withdrawal' => 'Withdrawal',
                    'topup' => 'Top-up',
                    'hold_reversal' => 'Hold reversal',
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
