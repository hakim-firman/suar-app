<?php

namespace App\Filament\Resources\Events\RelationManagers;

use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                    ->minValue(0),
                TextInput::make('quota')
                    ->label('Quota')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->afterStateUpdated(function ($state, Set $set, Get $get, $record) {
                        $event = $record->event;
                        $soldTickets = $record->tickets()->count();

                        if ((int)$state < $soldTickets) {
                            Notification::make()
                                ->title("Quota cannot be lower than sold tickets ({$soldTickets}).")
                                ->danger()
                                ->send();

                            $set('quota', $soldTickets);
                            return;
                        }

                        $totalQuota = $event->ticketPackages()->sum('quota');
                        $totalSold = $event->ticketPackages->sum(fn($pkg) => $pkg->tickets()->count());

                        if ($totalSold >= $totalQuota) {
                            $event->update(['status' => 0]); 
                            Notification::make()
                                ->title("Event {$event->title} is now full and marked as inactive.")
                                ->warning()
                                ->send();
                        } else {
                            if ($event->status === 'inactive') {
                                $event->update(['status' => 1]); 
                                Notification::make()
                                    ->title("Event {$event->title} is now reactivated.")
                                    ->success()
                                    ->send();
                            }
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
                TextColumn::make('name')->searchable(),
                TextColumn::make('price')
                    ->label('Price')
                    ->numeric()
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state)),
                TextColumn::make('quota')
                    ->label('Quota')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sold_tickets')
                    ->label('Sold')
                    ->getStateUsing(fn($record) => $record->sold_tickets),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn($state) => $state ? 'success' : 'danger'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil'),

                    Action::make('toggle_status')
                        ->label(fn($record) => $record->status ? 'Deactivate' : 'Activate')
                        ->icon(fn($record) => $record->status ? 'heroicon-o-x-mark' : 'heroicon-o-check')
                        ->color(fn($record) => $record->status ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['status' => ! $record->status]);
                            Notification::make()
                                ->title('Status updated successfully.')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->label('Delete')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->delete();
                            Notification::make()
                                ->title('Ticket package deleted successfully.')
                                ->success()
                                ->send();
                        }),
                ])
            ]);
    }
}
