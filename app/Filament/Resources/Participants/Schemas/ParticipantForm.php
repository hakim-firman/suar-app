<?php

namespace App\Filament\Resources\Participants\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ParticipantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('address'),
                Select::make('gender')
                    ->options(['male' => 'Male', 'female' => 'Female']),
                TextInput::make('age')
                    ->numeric(),
                TextInput::make('job'),
            ]);
    }
}
