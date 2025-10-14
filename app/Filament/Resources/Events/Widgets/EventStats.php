<?php

namespace App\Filament\Resources\Events\Widgets;

use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EventStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalEvents = Event::count();
        $activeEvents = Event::where('status', 1)->count();
        $ticketsSold = \App\Models\Ticket::count();

        return [
            Stat::make('Total Events', $totalEvents)
                ->description('All events in the system')
                ->icon('heroicon-o-calendar')
                ->color('gray'),

            Stat::make('Active Events', $activeEvents)
                ->description('Currently active events')
                ->icon('heroicon-o-bolt')
                ->color('success'),

            Stat::make('Tickets Sold', $ticketsSold)
                ->description('Total tickets sold across all events')
                ->icon('heroicon-o-ticket')
                ->color('primary'),
        ];
    }
}
