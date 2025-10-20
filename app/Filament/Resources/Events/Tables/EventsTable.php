<?php

namespace App\Filament\Resources\Events\Tables;

use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Database\Eloquent\Builder;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Event')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('ticketPackages.name')
                    ->badge()
                    ->label('Package'),
                TextColumn::make('capacity')
                    ->label('Total Capacity')
                    ->state(fn($record) => $record->capacity),
                TextColumn::make('total_tickets_sold')
                    ->label('Tickets Sold')
                    ->state(fn($record) => $record->total_tickets_sold)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn($state) => $state ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query->when(isset($data['value']), fn($q) => $q->where('status', $data['value']));
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Edit Event')
                        ->icon('heroicon-o-pencil-square'),

                    Action::make('toggleStatus')
                        ->label(fn($record) => $record->status ? 'Deactivate' : 'Activate')
                        ->icon(fn($record) => $record->status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn($record) => $record->status ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['status' => !$record->status]);
                        }),

                    DeleteAction::make()
                        ->label('Delete Event')
                        ->icon('heroicon-o-trash')
                        ->color('danger'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
