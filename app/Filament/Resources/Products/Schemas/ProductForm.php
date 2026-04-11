<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Infolists\Components\ThreeDimensionalModelEntry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('vendor_id')
                    ->relationship('vendor', 'name')
                    ->required(),
                Select::make('tags')
                    ->multiple()
                    ->relationship('tags', 'name')
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('weight')
                    ->numeric(),
                TextInput::make('dimensions'),
                TextInput::make('material'),
                \Filament\Forms\Components\Repeater::make('media')
                    ->relationship('media')
                    ->schema([
                        \Filament\Forms\Components\FileUpload::make('url')
                            ->image()
                            ->disk('public')
                            ->maxSize(2048)
                            ->storeFileNamesIn('name')
                            ->required(),
                        \Filament\Forms\Components\Hidden::make('name'),
                        \Filament\Forms\Components\Toggle::make('is_primary')
                            ->label('Primary Thumbnail'),
                    ])
                    ->columnSpanFull(),
                Fieldset::make('AR Model')
                    ->visible(fn ($operation) => $operation === 'edit')
                    ->relationship('arModel')
                    ->schema([
                        \Filament\Forms\Components\FileUpload::make('file_url')
                            ->label('AR Model (.glb, .obj)')
                            ->disk('public')
                            ->acceptedFileTypes(['model/gltf-binary', 'model/gltf+json', 'application/octet-stream', '.glb', '.obj'])
                            ->maxSize(51200)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                ThreeDimensionalModelEntry::make('arModel.file_url')
                    ->label('3D Model Preview')
                    ->visible(fn ($operation) => $operation === 'edit')
                    ->columnSpanFull(),
            ]);
    }
}
