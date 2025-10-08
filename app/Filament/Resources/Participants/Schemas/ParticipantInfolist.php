<?php

namespace App\Filament\Resources\Participants\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ParticipantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('address')
                    ->placeholder('-'),
                TextEntry::make('gender')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('age')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('job')
                    ->placeholder('-'),
            ]);
    }
}
