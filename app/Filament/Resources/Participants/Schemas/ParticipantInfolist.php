<?php

namespace App\Filament\Resources\Participants\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class ParticipantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Participant Information')
                    ->description('Details about the participant.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Full Name')
                            ->weight('bold')
                            ->color('primary'),

                        TextEntry::make('gender')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'male' => 'primary',
                                'female' => 'gray',
                            })
                            ->label('Gender')
                            ->placeholder('-'),

                        TextEntry::make('age')
                            ->label('Age')
                            ->placeholder('-'),

                        TextEntry::make('job')
                            ->label('Occupation')
                            ->placeholder('-'),

                        TextEntry::make('address')
                            ->label('Address')
                            ->placeholder('-'),
                    ]),
                Section::make('Tickets Purchased')
                    ->description('List of events and tickets owned by this participant.')
                    ->schema([
                        RepeatableEntry::make('tickets')
                            ->label('Tickets')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('event.title')
                                        ->label('Event')
                                        ->color('primary')
                                        ->icon('heroicon-o-calendar-days'),

                                    TextEntry::make('ticketPackage.name')
                                        ->label('Ticket Package')
                                        ->icon('heroicon-o-ticket'),

                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->color(fn(string $state): string => match ($state) {
                                            'booked' => 'success',
                                            'pending' => 'warning',
                                            'cancelled' => 'danger',
                                        }),
                                ]),
                            ])
                            ->visible(fn($record) => $record->tickets->isNotEmpty())
                            ->placeholder('No tickets purchased yet.'),
                    ]),
            ]);
    }
}
