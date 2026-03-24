<?php

namespace App\Filament\Resources\Media\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('url')
                    ->url()
                    ->required(),
                TextInput::make('disk')
                    ->required()
                    ->default('public'),
                TextInput::make('type')
                    ->required()
                    ->default('image'),
                TextInput::make('size')
                    ->numeric(),
                TextInput::make('mime_type'),
            ]);
    }
}
