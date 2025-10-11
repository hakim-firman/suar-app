<?php

namespace App\Filament\Resources\Events\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
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
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('capacity')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('status')
                    ->boolean(),
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
                Filter::make('capacity')
                    ->form([
                        TextInput::make('min_capacity')
                            ->label('Minimum Capacity')
                            ->numeric()
                            ->placeholder('e.g. 10'),
                        TextInput::make('max_capacity')
                            ->label('Maximum Capacity')
                            ->numeric()
                            ->placeholder('e.g. 100'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query
                            ->when($data['min_capacity'] ?? null, fn($q, $min) => $q->where('capacity', '>=', $min))
                            ->when($data['max_capacity'] ?? null, fn($q, $max) => $q->where('capacity', '<=', $max));
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
