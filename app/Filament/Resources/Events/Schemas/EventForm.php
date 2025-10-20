<?php

namespace App\Filament\Resources\Events\Schemas;

use Illuminate\Support\Str;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        if ($state) {
                            $prefix = Str::upper(Str::slug($state, '_'));
                            $random = rand(0, 999);
                            $set('code', "{$prefix}{$random}");
                        }
                    }),
                TextInput::make('code')
                    ->label('Code')
                    ->disabled()
                    ->dehydrated(true)
                    ->required(),
                Select::make('type')
                    ->label('Event Type')
                    ->multiple()
                    ->options([
                        'konser' => 'Konser',
                        'Solo concert' => 'Solo Concert',
                        'seminar' => 'Seminar',
                        'workshop' => 'Workshop',
                        'training' => 'Training',
                        'webinar' => 'Webinar',
                        'conference' => 'Conference',
                    ])
                    ->required(),
                TextInput::make('capacity')
                    ->minValue(0)
                    ->label('Capacity')
                    ->numeric()
                    ->afterStateUpdated(function ($state, callable $set, $record) {
                        if ($record) {
                            $totalQuota = $record->ticketPackages()->sum('quota');
                            if ($state < $totalQuota) {
                                Notification::make()
                                    ->title('Invalid Capacity')
                                    ->body("Capacity cannot be lower than total quota ({$totalQuota}).")
                                    ->danger()
                                    ->send();
                                $set('capacity', $totalQuota);
                            }
                        }
                    }),
                Textarea::make('description')
                    ->label('Description')
                    ->columnSpanFull(),
                Toggle::make('status')
                    ->label('Status')
                    ->default(true)
                    ->required(),
            ]);
    }
}
