<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class MonthlyOverviewWidget extends Widget
{
    protected string $view = 'filament.widgets.monthly-overview-widget';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    #[Url(as: 'period')]
    public string $period = '';

    public function mount(): void
    {
        // Only set the default when the URL carries no period — this way
        // #[Url] can populate $period before mount() runs and we won't
        // overwrite it.
        if (! $this->period) {
            $this->period = now()->format('Y-m');
        }
    }

    private function selectedDate(): Carbon
    {
        return Carbon::createFromFormat('Y-m', $this->period)->startOfMonth();
    }

    public function previousMonth(): void
    {
        $this->period = $this->selectedDate()->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->period = $this->selectedDate()->addMonth()->format('Y-m');
    }

    public function getMonthLabel(): string
    {
        $date = $this->selectedDate();

        return $date->year === now()->year
            ? $date->format('F')
            : $date->format('F Y');
    }

    public function isCurrentMonth(): bool
    {
        return $this->period === now()->format('Y-m');
    }

    public function getAccountName(): string
    {
        return Filament::getTenant()?->name ?? 'Account';
    }

    public function getCurrency(): string
    {
        return Filament::getTenant()?->currency ?? '';
    }

    public function getTransactionsUrl(): string
    {
        return route('filament.app.resources.transactions.index', [
            'tenant' => Filament::getTenant(),
        ]);
    }

    #[Computed]
    public function expenses(): Collection
    {
        $date = $this->selectedDate();

        return Transaction::query()
            ->where('account_id', Filament::getTenant()->id)
            ->where('type', 'expense')
            ->whereYear('transacted_at', $date->year)
            ->whereMonth('transacted_at', $date->month)
            ->with('category')
            ->get();
    }

    #[Computed]
    public function income(): Collection
    {
        $date = $this->selectedDate();

        return Transaction::query()
            ->where('account_id', Filament::getTenant()->id)
            ->where('type', 'income')
            ->whereYear('transacted_at', $date->year)
            ->whereMonth('transacted_at', $date->month)
            ->with('category')
            ->get();
    }

    public function getTotalExpenses(): float
    {
        return (float) $this->expenses->sum('amount');
    }

    public function getTotalIncome(): float
    {
        return (float) $this->income->sum('amount');
    }

    public function getAverageDailyExpense(): float
    {
        if ($this->expenses->isEmpty()) {
            return 0.0;
        }

        return (float) $this->expenses->sum('amount') / $this->selectedDate()->daysInMonth;
    }

    public function getTopExpense(): ?Transaction
    {
        return $this->expenses->sortByDesc('amount')->first();
    }

    /** @return Collection<int, Transaction> */
    public function getRecentTransactions(): Collection
    {
        $date = $this->selectedDate();

        return Transaction::query()
            ->where('account_id', Filament::getTenant()->id)
            ->where('type', 'expense')
            ->whereYear('transacted_at', $date->year)
            ->whereMonth('transacted_at', $date->month)
            ->with('category')
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();
    }

    public function getRemainingTransactionCount(): int
    {
        $date = $this->selectedDate();

        $total = Transaction::query()
            ->where('account_id', Filament::getTenant()->id)
            ->where('type', 'expense')
            ->whereYear('transacted_at', $date->year)
            ->whereMonth('transacted_at', $date->month)
            ->count();

        return max(0, $total - 3);
    }

    /** @return Collection<int, array{category: string, total: float, color: string, icon: string, percentage: float}> */
    public function getExpensesByCategory(): Collection
    {
        if ($this->expenses->isEmpty()) {
            return collect();
        }

        $total = $this->expenses->sum('amount');

        return $this->expenses
            ->groupBy(fn (Transaction $t) => $t->category?->name ?? 'Uncategorized')
            ->map(fn (Collection $transactions, string $category) => [
                'category' => $category,
                'total' => (float) $transactions->sum('amount'),
                'color' => $transactions->first()->category?->color ?? '#6366f1',
                'icon' => $transactions->first()->category?->icon ?? '',
                'percentage' => $total > 0 ? round(($transactions->sum('amount') / $total) * 100, 1) : 0,
            ])
            ->sortByDesc('total')
            ->values();
    }
}
