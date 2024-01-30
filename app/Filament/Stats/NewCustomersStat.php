<?php

namespace App\Filament\Stats;

use App\Models\User;
use Illuminate\Support\Carbon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class NewCustomersStat extends BaseWidget
{
    protected $newCustomersThisWeek;
    protected $newCustomersLastWeek;
    protected $monthlyChart;

    public function __construct()
    {
        $this->newCustomersThisWeek = $this->calculateNewCustomersThisWeek();
        $this->newCustomersLastWeek = $this->calculateNewCustomersLastWeek();
        $this->monthlyChart = $this->calculateMonthlyChart();
    }

    protected function getStats(): array
    {
        $percentage = $this->calculatePercentageChange();

        return [
            Stat::make('Nieuwe klanten deze week', $this->newCustomersThisWeek)
                ->icon('heroicon-o-light-bulb')
                ->description($this->getPercentageDescription($percentage))
                ->descriptionIcon($this->getPercentageIcon($percentage))
                ->chart($this->monthlyChart)
                ->color('success'),
        ];
    }

    protected function calculateNewCustomersThisWeek(): int
    {
        return User::where('created_at', '>=', now()->startOfWeek())
            ->where('created_at', '<=', now()->endOfWeek())
            ->where('role_id', 3)
            ->count();
    }

    protected function calculateNewCustomersLastWeek(): int
    {
        return User::where('created_at', '>=', now()->startOfWeek()->subWeek())
            ->where('created_at', '<=', now()->endOfWeek()->subWeek())
            ->where('role_id', 3)
            ->count();
    }

    protected function calculatePercentageChange(): float
    {
        $percentageChange = 0;

        if ($this->newCustomersLastWeek > 0) {
            $percentageChange = (($this->newCustomersThisWeek - $this->newCustomersLastWeek) / $this->newCustomersLastWeek) * 100;
        }

        return $percentageChange;
    }

    protected function calculateMonthlyChart(): array
    {
        $monthlyChart = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

            $newUsersMonthlyCount = User::where('role_id', 3)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $monthlyChart[] = $newUsersMonthlyCount;
        }

        return $monthlyChart;
    }

    protected function getPercentageDescription(float $percentageIncrease): string
    {
        return $percentageIncrease >= 0
            ? "{$percentageIncrease}% stijging"
            : "{$percentageIncrease}% daling";
    }

    protected function getPercentageIcon(float $percentageIncrease): string
    {
        return $percentageIncrease >= 0
            ? 'heroicon-m-arrow-trending-up'
            : 'heroicon-m-arrow-trending-down';
    }
}
