<?php

namespace App\Filament\Resources\Participants\Tables;

use App\Models\Event;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class ParticipantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('gender')
                    ->badge()
                    ->label('Gender')
                    ->colors([
                        'primary' => 'male',
                        'gray' => 'female',
                    ]),
                TextColumn::make('age')
                    ->numeric()
                    ->label('Age')
                    ->sortable(),
                TextColumn::make('job')
                    ->label('Occupation')
                    ->searchable(),
                TextColumn::make('events.title')
                    ->badge()
                    ->label('Event Title'),
            ])
            ->filters([
                SelectFilter::make('gender')
                    ->label('Gender')
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                    ]),
                SelectFilter::make('event')
                    ->label('Event Title')
                    ->relationship('events', 'title')
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View Details')
                        ->icon('heroicon-o-eye'),

                    DeleteAction::make()
                        ->label('Delete Participant')
                        ->icon('heroicon-o-trash'),
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
