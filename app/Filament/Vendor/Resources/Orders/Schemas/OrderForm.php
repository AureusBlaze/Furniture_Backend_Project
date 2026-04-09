<?php

namespace App\Filament\Vendor\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('total_price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('$'),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('pending')
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->label('Cart')
                    ->grid(2)
                    ->relationship()
                    ->schema([
                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->default(1),
                        TextInput::make('unit_price')
                            ->numeric()
                            ->required()
                            ->prefix('$'),
                        TextInput::make('subtotal')
                            ->numeric()
                            ->required()
                            ->prefix('$'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }
}
