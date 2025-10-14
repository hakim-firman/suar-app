<?php

namespace App\Filament\Resources\Participants\Widgets;

use App\Models\Participant;
use Filament\Widgets\ChartWidget;

class ParticipantGenderChart extends ChartWidget
{
    protected ?string $heading = 'Participant Gender Chart';
    protected ?string $maxHeight = '275px';

    protected function getData(): array
    {
        $maleCount = Participant::where('gender', 'male')->count();
        $femaleCount = Participant::where('gender', 'female')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Gender Distribution',
                    'data' => [$maleCount, $femaleCount],
                    'backgroundColor' => ['#36A2EB', '#FF6384'],
                ],
            ],
            'labels' => ['Male', 'Female'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
