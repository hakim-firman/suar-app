<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->required(),
                TextInput::make('code')
                    ->label('Code')
                    ->required(),
                Textarea::make('description')
                    ->label('Description')
                    ->columnSpanFull(),
                TextInput::make('type')
                    ->label('Type'),
                TextInput::make('capacity')
                    ->label('Capacity')
                    ->numeric(),
                Toggle::make('status')
                    ->label('Status')
                    ->default(true)
                    ->required(),
            ]);
    }
}
