<?php

namespace App\Filament\Resources\Transactions\Widgets;

use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class MonthlyTransactionSummary extends Widget
{
    protected string $view = 'filament.resources.transactions.widgets.monthly-transaction-summary';

    protected int|string|array $columnSpan = 'full';

    protected ?string $placeholderHeight = '7.5rem';

    public int $monthPage = 0;

    public string $selectedMonthKey = '';

    public function mount(?string $selectedMonthKey = null): void
    {
        $this->selectedMonthKey = filled($selectedMonthKey)
            ? $selectedMonthKey
            : CarbonImmutable::now()->format('Y-m');

        $this->monthPage = $this->monthPageForSelectedMonth();
    }

    public function showOlderMonths(): void
    {
        $this->monthPage++;
        $this->selectedMonthKey = $this->monthWindow()['end']->format('Y-m');

        $this->dispatchSelectedMonth();
    }

    public function showNewerMonths(): void
    {
        $this->monthPage = max($this->monthPage - 1, 0);
        $this->selectedMonthKey = $this->monthWindow()['end']->format('Y-m');

        $this->dispatchSelectedMonth();
    }

    public function canShowNewerMonths(): bool
    {
        return $this->monthPage > 0;
    }

    public function getCurrency(): string
    {
        return Filament::getTenant()?->currency ?? '';
    }

    public function getWindowLabel(): string
    {
        $window = $this->monthWindow();

        return $window['start']->format('M Y').' - '.$window['end']->format('M Y');
    }

    public function compactExpenseAmount(float $amount): string
    {
        if ($amount >= 100000) {
            return number_format($amount / 100000, 2).'L';
        }

        if ($amount >= 1000) {
            return number_format($amount / 1000, 1).'K';
        }

        return number_format($amount, $amount < 100 ? 1 : 0);
    }

    public function selectMonth(string $monthKey): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            return;
        }

        if (! $this->summary['months']->contains('month_key', $monthKey)) {
            return;
        }

        $this->selectedMonthKey = $monthKey;

        $this->dispatchSelectedMonth();
    }

    public function getSelectedMonthLabel(): string
    {
        return $this->selectedMonth()->format('F Y');
    }

    public function getMonthSelectionLabel(string $monthKey): string
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            return $this->getSelectedMonthLabel();
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m', $monthKey)->format('F Y');
        } catch (\Throwable) {
            return $this->getSelectedMonthLabel();
        }
    }

    public function getOlderMonthSelectionLabel(): string
    {
        return $this->monthWindowForPage($this->monthPage + 1)['end']->format('F Y');
    }

    public function getNewerMonthSelectionLabel(): string
    {
        return $this->monthWindowForPage(max($this->monthPage - 1, 0))['end']->format('F Y');
    }

    /** @return array{expense_total: float, max_expense_total: float, months: Collection<int, array{month_key: string, month_label: string, expense_total: float, expense_percentage: float}>} */
    #[Computed]
    public function summary(): array
    {
        $window = $this->monthWindow();
        $months = collect(range(0, 5))
            ->mapWithKeys(function (int $index) use ($window): array {
                $month = $window['start']->addMonths($index);

                return [
                    $month->format('Y-m') => [
                        'month_key' => $month->format('Y-m'),
                        'month_label' => $month->format('M y'),
                        'expense_total' => 0.0,
                        'expense_percentage' => 0.0,
                    ],
                ];
            });

        $tenant = Filament::getTenant();

        if ($tenant === null) {
            return $this->formatSummary($months);
        }

        $monthExpression = $this->monthExpression();

        Transaction::query()
            ->selectRaw("{$monthExpression} as month_key")
            ->selectRaw('SUM(amount) as expense_total')
            ->where('account_id', $tenant->getKey())
            ->where('type', 'expense')
            ->whereBetween('transacted_at', [
                $window['start']->startOfMonth()->toDateString(),
                $window['end']->endOfMonth()->toDateString(),
            ])
            ->whereNotNull('transacted_at')
            ->groupByRaw($monthExpression)
            ->get()
            ->each(static function (Transaction $transaction) use ($months): void {
                $month = $months->get($transaction->month_key);

                if ($month === null) {
                    return;
                }

                $month['expense_total'] = (float) $transaction->expense_total;
                $months->put($transaction->month_key, $month);
            });

        return $this->formatSummary($months->values());
    }

    /** @return array{expense_count: int, highest_expense_amount: float, highest_expense_name: string, highest_expense_category: string, top_category_name: string, top_category_total: float} */
    #[Computed]
    public function selectedMonthInsights(): array
    {
        $query = $this->selectedMonthExpenseQuery();

        if ($query === null) {
            return $this->emptyInsights();
        }

        $expenses = $query->with('category')->get();

        if ($expenses->isEmpty()) {
            return $this->emptyInsights();
        }

        $highestExpense = $expenses->sortByDesc('amount')->first();

        $topCategoryGroup = $expenses
            ->groupBy('category_id')
            ->map(fn (Collection $group): array => [
                'category' => $group->first()->category,
                'total' => $group->sum(fn (Transaction $t): float => (float) $t->amount),
            ])
            ->sortByDesc('total')
            ->first();

        return [
            'expense_count' => $expenses->count(),
            'highest_expense_amount' => (float) $highestExpense->amount,
            'highest_expense_name' => $highestExpense->description ?: ($highestExpense->category?->name ?? 'No expense'),
            'highest_expense_category' => $highestExpense->category?->name ?? 'Uncategorized',
            'top_category_name' => $topCategoryGroup['category']?->name ?? 'No category',
            'top_category_total' => (float) $topCategoryGroup['total'],
        ];
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable} */
    private function monthWindow(): array
    {
        return $this->monthWindowForPage($this->monthPage);
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable} */
    private function monthWindowForPage(int $monthPage): array
    {
        $end = CarbonImmutable::now()
            ->startOfMonth()
            ->subMonths($monthPage * 6);

        return [
            'start' => $end->subMonths(5),
            'end' => $end,
        ];
    }

    private function selectedMonth(): CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $this->selectedMonthKey)) {
            return CarbonImmutable::now()->startOfMonth();
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m', $this->selectedMonthKey)->startOfMonth();
        } catch (\Throwable) {
            return CarbonImmutable::now()->startOfMonth();
        }
    }

    private function monthExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "DATE_FORMAT(transacted_at, '%Y-%m')",
            'pgsql' => "TO_CHAR(transacted_at, 'YYYY-MM')",
            'sqlsrv' => "FORMAT(transacted_at, 'yyyy-MM')",
            default => "strftime('%Y-%m', transacted_at)",
        };
    }

    private function selectedMonthExpenseQuery(): ?Builder
    {
        $tenant = Filament::getTenant();

        if ($tenant === null) {
            return null;
        }

        $selectedMonth = $this->selectedMonth();

        return Transaction::query()
            ->where('account_id', $tenant->getKey())
            ->where('type', 'expense')
            ->whereBetween('transacted_at', [
                $selectedMonth->startOfMonth()->toDateString(),
                $selectedMonth->endOfMonth()->toDateString(),
            ]);
    }

    /** @return array{expense_count: int, highest_expense_amount: float, highest_expense_name: string, highest_expense_category: string, top_category_name: string, top_category_total: float} */
    private function emptyInsights(): array
    {
        return [
            'expense_count' => 0,
            'highest_expense_amount' => 0.0,
            'highest_expense_name' => 'No expense',
            'highest_expense_category' => 'Uncategorized',
            'top_category_name' => 'No category',
            'top_category_total' => 0.0,
        ];
    }

    private function dispatchSelectedMonth(): void
    {
        $this->dispatch('transaction-month-selected', monthKey: $this->selectedMonthKey);
    }

    private function monthPageForSelectedMonth(): int
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $this->selectedMonthKey)) {
            return 0;
        }

        try {
            $selectedMonth = CarbonImmutable::createFromFormat('Y-m', $this->selectedMonthKey)->startOfMonth();
        } catch (\Throwable) {
            return 0;
        }

        $currentMonth = CarbonImmutable::now()->startOfMonth();

        if ($selectedMonth->greaterThan($currentMonth)) {
            return 0;
        }

        return (int) floor($selectedMonth->diffInMonths($currentMonth) / 6);
    }

    /**
     * @param  Collection<int|string, array{month_key: string, month_label: string, expense_total: float, expense_percentage: float}>  $months
     * @return array{expense_total: float, max_expense_total: float, months: Collection<int, array{month_key: string, month_label: string, expense_total: float, expense_percentage: float}>}
     */
    private function formatSummary(Collection $months): array
    {
        $expenseTotal = (float) $months->sum('expense_total');
        $maxExpenseTotal = (float) ($months->max('expense_total') ?? 0);

        $months = $months
            ->map(static function (array $month) use ($maxExpenseTotal): array {
                $month['expense_percentage'] = $maxExpenseTotal > 0
                    ? round(($month['expense_total'] / $maxExpenseTotal) * 100, 1)
                    : 0.0;

                return $month;
            })
            ->values();

        return [
            'expense_total' => $expenseTotal,
            'max_expense_total' => $maxExpenseTotal,
            'months' => $months,
        ];
    }
}
