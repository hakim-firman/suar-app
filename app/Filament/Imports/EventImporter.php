<?php

namespace App\Filament\Imports;

use App\Models\Event;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;
use Illuminate\Support\Number;

class EventImporter extends Importer
{
    protected static ?string $model = Event::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('title')
                ->requiredMapping()
                ->example('Konser Feast')
                ->rules(['required', 'max:255']),
            ImportColumn::make('type')
                ->example('konser,seminar')
                ->rules(['nullable']),
            ImportColumn::make('capacity')
                ->numeric()
                ->example('500')
                ->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('description')
                ->example('Festival musik 2025'),
            ImportColumn::make('status')
                ->example('active')
                ->rules(['nullable', 'in:active,inactive']),
        ];
    }

    public function resolveRecord(): Event
    {
        return Event::firstOrNew([
            'title' => $this->data['title'],
        ]);
    }

    /**
     * Fill the resolved record. Must follow Filament v4 signature (no params).
     * Use $this->record and $this->data provided by the Importer base class.
     *
     * @return void
     */
    public function fillRecord(): void
    {
        /** @var Event $record */
        $record = $this->record;

        if (empty($record->code)) {
            $prefix = Str::upper(Str::slug($this->data['title'], '_'));
            $random = rand(0, 999);
            $code = "{$prefix}{$random}";

            while (Event::where('code', $code)->exists()) {
                $random = rand(0, 999);
                $code = "{$prefix}{$random}";
            }

            $record->code = $code;
        }

        $type = $this->data['type'] ?? $record->type;
        if (is_string($type)) {
            $type = array_filter(array_map('trim', explode(',', $type)));
            $type = $type === [] ? null : $type;
        }

        $capacity = array_key_exists('capacity', $this->data) ? $this->data['capacity'] : $record->capacity;

        $description = $this->data['description'] ?? $record->description;

        $statusRaw = $this->data['status'] ?? null;
        $status = match (strtolower($statusRaw ?? 'active')) {
            'active' => true,
            'inactive' => false,
            default => ($record->status ?? true),
        };

        $record->fill([
            'type' => $type,
            'capacity' => $capacity,
            'description' => $description,
            'status' => $status,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your event import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
