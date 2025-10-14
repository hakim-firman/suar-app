<?php

namespace App\Filament\Resources\Events\RelationManagers;

use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Filament\Resources\Events\EventResource;
use Filament\Resources\RelationManagers\RelationManager;

class TicketPackagesRelationManager extends RelationManager
{
    protected static string $relationship = 'ticketPackages';

    // protected static ?string $relatedResource = EventResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required(),
                TextInput::make('price')
                    ->label('Price')
                    ->numeric()
                    ->inputMode('decimal')
                    ->minValue(0),
                TextInput::make('quota')
                    ->label('Quota')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $event = $this->getOwnerRecord();
                        $existingQuota = $event->ticketPackages()->sum('quota');
                        $newTotal = $existingQuota + (int) $state;

                        if ($newTotal > $event->capacity) {
                            Notification::make()
                                ->title('Quota exceeds event capacity!')
                                ->danger()
                                ->send();

                            $set('quota', null);
                        }
                    }),
                Toggle::make('status')
                    ->label('Status')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check')
                    ->offIcon('heroicon-m-x-mark')
                    ->default(true)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('price')
                    ->numeric()
                    ->searchable(),
                TextColumn::make('quota')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('status')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
