<?php

namespace App\Filament\Vendor\Resources\Products\Pages;

use App\Filament\Vendor\Resources\Products\ProductResource;
use App\Models\Tag;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Filament\Actions\Action;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createTags')
                ->label('Create Tags')
                ->icon('heroicon-o-plus')
                ->modalHeading('Create Multiple Tags')
                ->schema([
                    Repeater::make('tags')
                        ->schema([
                            TextInput::make('name')
                                ->label('Tag Name')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->addActionLabel('Add another tag')
                        ->minItems(1),
                ])
                ->action(function (array $data): void {
                    if (! empty($data['tags'])) {
                        foreach ($data['tags'] as $tagData) {
                            Tag::firstOrCreate(
                                ['slug' => Str::slug($tagData['name'])],
                                ['name' => $tagData['name']]
                            );
                        }
                    }
                }),
        ];
    }
}
