<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()->ColumnSpanFull(),
                TextInput::make('slug')
                    ->required()->ColumnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),
                FileUpload::make('image_url')
                    ->image()
                    ->disk('public')
                    ->maxSize(2048),
                Select::make('parent_id')
                    ->relationship('parent', 'name'),
            ]);
    }
}
