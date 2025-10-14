<?php

namespace App\Filament\Resources\Events\Widgets;

use App\Models\Event;
use Filament\Widgets\ChartWidget;

class EventParticipantChart extends ChartWidget
{
    protected ?string $heading = 'Event Participant Chart';
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $events = Event::withCount('participants')->get();
        return [
             'datasets' => [
                [
                    'label' => 'Number of Participants',
                    'data' => $events->pluck('participants_count')->toArray(),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $events->pluck('title')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
