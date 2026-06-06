<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Filament\Resources\Transactions\Widgets\MonthlyTransactionSummary;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Throwable;

class ListTransactions extends ListRecords
{
    private const TransactionsPerPage = 15;

    protected static string $resource = TransactionResource::class;

    protected string $view = 'filament.resources.transactions.pages.list-transactions';

    public ?string $tableGrouping = 'transacted_at:desc';

    #[Url(as: 'month', except: '')]
    public string $selectedMonthKey = '';

    public int $visibleTransactionsCount = self::TransactionsPerPage;

    public function mount(): void
    {
        parent::mount();

        if (! preg_match('/^\d{4}-\d{2}$/', $this->selectedMonthKey)) {
            $this->selectedMonthKey = now()->format('Y-m');
        }
    }

    #[On('transaction-month-selected')]
    public function selectMonthFromGraph(string $monthKey): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            return;
        }

        $this->selectedMonthKey = $monthKey;
        $this->visibleTransactionsCount = self::TransactionsPerPage;

        $this->dispatch('transaction-month-loaded');
    }

    public function loadMoreTransactions(): void
    {
        if (! $this->hasMoreTransactions()) {
            return;
        }

        $this->visibleTransactionsCount += self::TransactionsPerPage;
    }

    public function getCurrency(): string
    {
        return Filament::getTenant()?->currency ?? '';
    }

    public function getSelectedMonthLabel(): string
    {
        return $this->selectedMonth()->format('F Y');
    }

    /** @return array<string, string> */
    public function getWidgetData(): array
    {
        return [
            'selectedMonthKey' => $this->selectedMonthKey,
        ];
    }

    public function editTransactionAction(): EditAction
    {
        return EditAction::make('editTransaction')
            ->record(fn (array $arguments): ?Transaction => $this->resolveTenantTransaction($arguments['transaction'] ?? null))
            ->slideOver()
            ->iconButton()
            ->icon('heroicon-m-pencil-square')
            ->color('gray')
            ->tooltip('Edit transaction')
            ->label('Edit transaction')
            ->after(function (): void {
                unset($this->groupedTransactions);
                unset($this->totalTransactionsInMonth);
            });
    }

    public function deleteTransactionAction(): DeleteAction
    {
        return DeleteAction::make('deleteTransaction')
            ->record(fn (array $arguments): ?Transaction => $this->resolveTenantTransaction($arguments['transaction'] ?? null))
            ->iconButton()
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->tooltip('Delete transaction')
            ->label('Delete transaction')
            ->after(function (): void {
                unset($this->groupedTransactions);
                unset($this->totalTransactionsInMonth);
            });
    }

    /** @return Collection<string, Collection<int, Transaction>> */
    #[Computed]
    public function groupedTransactions(): Collection
    {
        $query = $this->monthlyTransactionsQuery();

        if ($query === null) {
            return collect();
        }

        return $query
            ->with(['account', 'category'])
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->limit($this->visibleTransactionsCount)
            ->get()
            ->groupBy(fn (Transaction $transaction): string => $transaction->transacted_at?->format('D, M j') ?? 'No date');
    }

    #[Computed]
    public function totalTransactionsInMonth(): int
    {
        return $this->monthlyTransactionsQuery()?->count() ?? 0;
    }

    public function hasMoreTransactions(): bool
    {
        return $this->totalTransactionsInMonth > $this->visibleTransactionsCount;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MonthlyTransactionSummary::class,
        ];
    }

    private function resolveTenantTransaction(mixed $key): ?Transaction
    {
        $tenant = Filament::getTenant();

        if ($tenant === null || blank($key)) {
            return null;
        }

        foreach ($this->groupedTransactions as $transactions) {
            $found = $transactions->firstWhere('id', $key);
            if ($found !== null) {
                return $found;
            }
        }

        return Transaction::query()
            ->where('account_id', $tenant->getKey())
            ->find($key);
    }

    private function monthlyTransactionsQuery(): ?Builder
    {
        $tenant = Filament::getTenant();

        if ($tenant === null) {
            return null;
        }

        $selectedMonth = $this->selectedMonth();

        return Transaction::query()
            ->where('account_id', $tenant->getKey())
            ->whereBetween('transacted_at', [
                $selectedMonth->copy()->startOfMonth()->toDateString(),
                $selectedMonth->copy()->endOfMonth()->toDateString(),
            ]);
    }

    private function selectedMonth(): CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $this->selectedMonthKey)) {
            return now()->toImmutable()->startOfMonth();
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m', $this->selectedMonthKey)->startOfMonth();
        } catch (Throwable) {
            return now()->toImmutable()->startOfMonth();
        }
    }
}
