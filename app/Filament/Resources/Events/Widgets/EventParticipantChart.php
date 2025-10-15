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
        $colors = $this->generateColors($events->count());
        return [
            'datasets' => [
                [
                    'label' => 'Number of Participants',
                    'data' => $events->pluck('participants_count')->toArray(),
                    'backgroundColor' => $colors,
                    'borderColor' => '#1e293b',
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
    protected function generateColors(int $count): array
    {
        $palette = [
            'rgba(59, 130, 246, 0.7)',  // blue-500
            'rgba(34, 197, 94, 0.7)',   // green-500
            'rgba(239, 68, 68, 0.7)',   // red-500
            'rgba(234, 179, 8, 0.7)',   // yellow-500
            'rgba(168, 85, 247, 0.7)',  // purple-500
            'rgba(14, 165, 233, 0.7)',  // sky-500
            'rgba(236, 72, 153, 0.7)',  // pink-500
            'rgba(250, 204, 21, 0.7)',  // amber-400
            'rgba(16, 185, 129, 0.7)',  // teal-500
            'rgba(147, 51, 234, 0.7)',  // violet-600
        ];

        return array_slice(array_merge(...array_fill(0, ceil($count / count($palette)), $palette)), 0, $count);
    }
}
