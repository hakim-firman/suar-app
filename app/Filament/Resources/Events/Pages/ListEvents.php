<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Exports\EventExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use App\Filament\Imports\EventImporter;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Events\EventResource;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Event')
                ->color('primary')
                ->exporter(EventExporter::class),
            ImportAction::make()
                ->label('Import Event')
                ->color('primary')
                ->importer(EventImporter::class),
            CreateAction::make()
                ->label('New Event'),
        ];
    }
}
