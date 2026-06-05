<x-filament-widgets::widget>
    @php
        $currency    = $this->getCurrency();
        $totalSpend  = $this->getTotalExpenses();
        $totalIncome = $this->getTotalIncome();
        $avgDaily    = $this->getAverageDailyExpense();
        $topExpense  = $this->getTopExpense();
        $byCategory  = $this->getExpensesByCategory();
        $recent      = $this->getRecentTransactions();
    @endphp

    <div class="relative p-6 space-y-6">

        {{-- Loading overlay --}}
        <div
            wire:loading.flex
            wire:target="previousMonth,nextMonth"
            class="absolute inset-0 z-10 hidden items-start justify-center pt-10 rounded-xl bg-white/60 backdrop-blur-[1px] dark:bg-gray-950/60"
        >
            <div class="inline-flex items-center gap-2.5 rounded-full bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-md ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-200 dark:ring-white/10">
                <x-filament::loading-indicator class="h-5 w-5" />
                Loading month…
            </div>
        </div>

        {{-- Header: Account › Month + navigation --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-sm font-medium text-gray-400 dark:text-gray-500 truncate">
                    {{ $this->getAccountName() }}
                </span>
                <x-heroicon-m-chevron-right class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 flex-shrink-0" />
                <span class="text-sm font-semibold text-primary-600 dark:text-primary-400">
                    {{ $this->getMonthLabel() }}
                </span>
            </div>
            <div class="flex items-center gap-1.5">
                <button
                    wire:click="previousMonth"
                    wire:loading.attr="disabled"
                    wire:target="previousMonth,nextMonth"
                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10 transition disabled:opacity-40"
                >
                    <x-heroicon-m-chevron-left class="w-3.5 h-3.5" />
                </button>
                <button
                    wire:click="nextMonth"
                    wire:loading.attr="disabled"
                    wire:target="previousMonth,nextMonth"
                    @disabled($this->isCurrentMonth())
                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10 transition disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    <x-heroicon-m-chevron-right class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>

        {{-- Spends + Income --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <div class="flex items-center gap-1.5 mb-1">
                    <x-heroicon-m-arrow-trending-up class="w-4 h-4 text-danger-500" />
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Spends</span>
                </div>
                <p class="text-3xl font-bold text-gray-950 dark:text-white leading-none">
                    {{ $currency }} {{ number_format($totalSpend, 0) }}
                </p>
                @if ($avgDaily > 0)
                    <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">
                        {{ $currency }} {{ number_format($avgDaily, 0) }} / day
                    </p>
                @endif
            </div>
            <div class="text-right">
                <div class="flex items-center justify-end gap-1.5 mb-1">
                    <x-heroicon-m-arrow-trending-down class="w-4 h-4 text-success-500" />
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Income</span>
                </div>
                <p class="text-2xl font-bold text-success-600 dark:text-success-400 leading-none">
                    {{ $currency }} {{ number_format($totalIncome, 0) }}
                </p>
                @if ($totalIncome > 0 && $totalSpend > 0)
                    @php $saved = $totalIncome - $totalSpend; @endphp
                    <p class="mt-1.5 text-xs {{ $saved >= 0 ? 'text-success-500' : 'text-danger-500' }}">
                        {{ $saved >= 0 ? 'Saved' : 'Overspent' }} {{ $currency }} {{ number_format(abs($saved), 0) }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Recent transactions --}}
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                Recent transactions
            </p>

            @if ($recent->isNotEmpty())
                @php $remaining = $this->getRemainingTransactionCount(); @endphp

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

                    @foreach ($recent as $tx)
                        @php
                            $color = $tx->category?->color ?? '#ef4444';
                            $icon  = $tx->category?->icon ?? null;
                        @endphp

                        <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-white/10 shadow-sm hover:shadow-md transition-shadow">
                            {{-- Colored icon band --}}
                            <div
                                class="h-12 flex items-center justify-center relative"
                                style="background-color: {{ $color }}22"
                            >
                                <div
                                    class="w-8 h-8 rounded-full flex items-center justify-center"
                                    style="background-color: {{ $color }}44"
                                >
                                    @if ($icon)
                                        <span class="text-base leading-none">{{ $icon }}</span>
                                    @else
                                        <x-heroicon-m-arrow-up-right class="w-3.5 h-3.5" style="color: {{ $color }}" />
                                    @endif
                                </div>
                            </div>

                            {{-- Details --}}
                            <div class="px-2.5 py-2 bg-white dark:bg-gray-950 space-y-0.5">
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate font-medium">
                                    {{ $tx->category?->name ?? 'Expense' }}
                                </p>
                                <p class="text-sm font-bold leading-tight text-danger-600 dark:text-danger-400">
                                    -{{ $currency }}&nbsp;{{ number_format($tx->amount, 0) }}
                                </p>
                                @if ($tx->description)
                                    <p class="text-[10px] text-gray-400 dark:text-gray-500 truncate">
                                        {{ $tx->description }}
                                    </p>
                                @endif
                                <p class="text-[10px] text-gray-400 dark:text-gray-600">
                                    {{ $tx->transacted_at->format('j M') }}
                                </p>
                            </div>
                        </div>
                    @endforeach

                    {{-- View rest card --}}
                    <a
                        href="{{ $this->getTransactionsUrl() }}"
                        class="rounded-xl border-2 border-dashed border-gray-200 dark:border-white/15 flex flex-col items-center justify-center gap-1.5 py-4 hover:border-primary-400 dark:hover:border-primary-500 hover:bg-primary-50/50 dark:hover:bg-primary-500/5 transition-all group"
                    >
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 dark:bg-white/8 group-hover:bg-primary-100 dark:group-hover:bg-primary-500/15 transition-colors">
                            <x-heroicon-m-arrow-right class="w-4 h-4 text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors" />
                        </span>
                        @if ($remaining > 0)
                            <span class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors text-center leading-snug px-2">
                                View rest {{ $remaining }}<br>{{ Str::plural('expense', $remaining) }}
                            </span>
                        @else
                            <span class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                View all
                            </span>
                        @endif
                    </a>

                </div>
            @else
                <div class="flex flex-col items-center justify-center py-8 text-gray-400 dark:text-gray-500">
                    <x-heroicon-o-banknotes class="w-10 h-10 mb-2 opacity-40" />
                    <p class="text-sm">No transactions for this month.</p>
                </div>
            @endif
        </div>

        {{-- Biggest expense + Avg daily + Category breakdown --}}
        @if ($byCategory->isNotEmpty() || $topExpense)
            <div class="border-t border-gray-100 dark:border-white/10 pt-5 space-y-5">

                @if ($topExpense)
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Biggest Expense</p>
                            <p class="mt-1.5 text-xl font-bold text-danger-600 dark:text-danger-400">
                                {{ $currency }} {{ number_format($topExpense->amount, 0) }}
                            </p>
                            @if ($topExpense->description)
                                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400 truncate">{{ $topExpense->description }}</p>
                            @endif
                            @if ($topExpense->category)
                                <span
                                    class="mt-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium text-white"
                                    style="background-color: {{ $topExpense->category->color ?? '#6366f1' }}"
                                >
                                    @if ($topExpense->category->icon) {{ $topExpense->category->icon }} @endif
                                    {{ $topExpense->category->name }}
                                </span>
                            @endif
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Avg Daily Spend</p>
                            <p class="mt-1.5 text-xl font-bold text-gray-950 dark:text-white">
                                {{ $currency }} {{ number_format($avgDaily, 0) }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">per day this month</p>
                        </div>
                    </div>
                @endif

                @if ($byCategory->isNotEmpty())
                    <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
                        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-white/10">
                            <h3 class="text-xs font-semibold text-gray-950 dark:text-white uppercase tracking-wide">Expenses by Category</h3>
                        </div>
                        <ul class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($byCategory as $row)
                                <li class="flex items-center gap-4 px-5 py-3">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $row['color'] }}"></span>
                                        @if ($row['icon'])
                                            <span class="text-sm leading-none">{{ $row['icon'] }}</span>
                                        @endif
                                        <span class="truncate text-sm text-gray-700 dark:text-gray-300">{{ $row['category'] }}</span>
                                    </div>
                                    <div class="flex-1 hidden sm:block">
                                        <div class="w-full bg-gray-100 dark:bg-white/10 rounded-full h-1">
                                            <div class="h-1 rounded-full" style="width: {{ $row['percentage'] }}%; background-color: {{ $row['color'] }}"></div>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $currency }} {{ number_format($row['total'], 0) }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $row['percentage'] }}%</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </div>
        @endif

    </div>
</x-filament-widgets::widget>
