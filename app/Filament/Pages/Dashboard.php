<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\Events\Widgets\EventStats;
use App\Filament\Resources\Events\Widgets\EventParticipantChart;
use App\Filament\Resources\Participants\Widgets\ParticipantGenderChart;

class Dashboard extends Page
{
    protected static ?string $title = 'Dashboard Overview';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected string $view = 'filament.pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            EventStats::class,
            EventParticipantChart::class,
            ParticipantGenderChart::class,
        ];
    }
}
