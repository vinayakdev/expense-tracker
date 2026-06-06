<x-filament-widgets::widget>
    @php
        $currency = $this->getCurrency();
        $summary = $this->summary;
        $months = $summary['months'];
        $selectedMonth = $months->firstWhere('month_key', $selectedMonthKey);
        $insights = $this->selectedMonthInsights;
    @endphp

    <div class="relative rounded-xl border border-gray-200 bg-white p-2.5 shadow-sm dark:border-white/10 dark:bg-gray-950">
        <div class="grid gap-2 xl:grid-cols-[minmax(31rem,1fr)_minmax(40rem,42rem)] xl:items-stretch">
            <section
                x-data="{ navigating: false }"
                x-on:transaction-month-loaded.window="navigating = false"
                class="flex min-w-0 items-center gap-2 rounded-lg bg-gray-50 px-2 py-2 ring-1 ring-gray-200 dark:bg-white/5 dark:ring-white/10"
            >
                <button
                    type="button"
                    x-on:click="if (navigating) return; navigating = true; $dispatch('transaction-month-loading')"
                    :disabled="navigating"
                    wire:click="showOlderMonths"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-gray-600 shadow-sm ring-1 ring-gray-200 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-gray-950 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/10"
                    aria-label="Show previous six months"
                >
                    <x-heroicon-m-chevron-left class="h-4 w-4" />
                </button>

                <div class="min-w-0 flex-1 overflow-x-auto">
                    <div class="flex min-w-max items-center justify-start gap-1.5 py-1">
                        @foreach ($months as $month)
                            <button
                                type="button"
                                wire:key="transaction-month-summary-{{ $month['month_key'] }}"
                                x-on:click="if (navigating) return; navigating = true; $dispatch('transaction-month-loading')"
                                wire:click="selectMonth('{{ $month['month_key'] }}')"
                                class="flex h-20 w-14 shrink-0 flex-col items-center justify-end gap-1 rounded-lg px-1 py-1 transition {{ $month['month_key'] === $selectedMonthKey ? 'bg-amber-50 ring-1 ring-inset ring-amber-300 dark:bg-amber-400/10 dark:ring-amber-400/30' : 'hover:bg-white dark:hover:bg-white/5' }}"
                                title="{{ $currency }} {{ number_format($month['expense_total'], 2) }}"
                                aria-pressed="{{ $month['month_key'] === $selectedMonthKey ? 'true' : 'false' }}"
                            >
                                <span class="h-3 max-w-full truncate text-center text-[10px] font-semibold leading-none text-gray-800 dark:text-gray-200">
                                    @if ($month['expense_total'] > 0)
                                        {{ $this->compactExpenseAmount($month['expense_total']) }}
                                    @endif
                                </span>

                                <span class="flex h-9 items-end">
                                    <span
                                        class="w-3 rounded bg-amber-500 transition-all dark:bg-amber-400"
                                        style="height: {{ max($month['expense_percentage'], $month['expense_total'] > 0 ? 8 : 0) }}%"
                                    ></span>
                                </span>

                                <span class="max-w-full truncate text-center text-[10px] leading-none text-gray-500 dark:text-gray-400">
                                    {{ $month['month_label'] }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <button
                    type="button"
                    x-on:click="if (navigating) return; navigating = true; $dispatch('transaction-month-loading')"
                    :disabled="navigating || @js(! $this->canShowNewerMonths())"
                    wire:click="showNewerMonths"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-gray-600 shadow-sm ring-1 ring-gray-200 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-gray-950 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/10"
                    aria-label="Show newer six months"
                >
                    <x-heroicon-m-chevron-right class="h-4 w-4" />
                </button>
            </section>

            <div class="relative grid min-w-0 gap-2 md:grid-cols-3">
                <section
                    wire:loading.class="opacity-35"
                    wire:target="selectMonth,showOlderMonths,showNewerMonths"
                    class="min-w-0 rounded-lg bg-white px-3 py-2 ring-1 ring-gray-200 transition-opacity dark:bg-gray-950 dark:ring-white/10"
                >
                    <p class="truncate text-[10px] font-semibold uppercase text-amber-700 dark:text-amber-300">
                        {{ $this->getSelectedMonthLabel() }}
                    </p>
                    <h2 class="truncate text-sm font-semibold text-gray-950 dark:text-white">All spends</h2>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $this->getWindowLabel() }}</p>

                    <div class="mt-2 flex items-end justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-[11px] text-gray-500 dark:text-gray-400">Spend</p>
                            <p class="truncate text-sm font-bold text-amber-800 dark:text-amber-200">
                                {{ $currency }} {{ number_format((float) ($selectedMonth['expense_total'] ?? 0), 2) }}
                            </p>
                        </div>

                        <div class="shrink-0 text-right">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">Txns</p>
                            <p class="text-sm font-bold text-gray-950 dark:text-white">{{ $insights['expense_count'] }}</p>
                        </div>
                    </div>
                </section>

                <section
                    wire:loading.class="opacity-35"
                    wire:target="selectMonth,showOlderMonths,showNewerMonths"
                    class="min-w-0 rounded-lg bg-amber-50 px-3 py-2 ring-1 ring-amber-200 transition-opacity dark:bg-amber-400/10 dark:ring-amber-400/20"
                >
                    <p class="truncate text-[10px] font-semibold uppercase text-amber-700 dark:text-amber-300">Highest expense</p>
                    <p class="mt-1 truncate text-sm font-bold text-gray-950 dark:text-white">
                        {{ $currency }} {{ number_format($insights['highest_expense_amount'], 2) }}
                    </p>
                    <p class="truncate text-xs text-gray-600 dark:text-gray-300">
                        {{ $insights['highest_expense_name'] }}
                    </p>
                    <p class="truncate text-[11px] text-gray-500 dark:text-gray-400">
                        {{ $insights['highest_expense_category'] }}
                    </p>
                </section>

                <section
                    wire:loading.class="opacity-35"
                    wire:target="selectMonth,showOlderMonths,showNewerMonths"
                    class="min-w-0 rounded-lg bg-amber-50 px-3 py-2 ring-1 ring-amber-200 transition-opacity dark:bg-amber-400/10 dark:ring-amber-400/20"
                >
                    <p class="truncate text-[10px] font-semibold uppercase text-amber-700 dark:text-amber-300">Top category</p>
                    <p class="mt-1 truncate text-sm font-bold text-gray-950 dark:text-white">
                        {{ $insights['top_category_name'] }}
                    </p>
                    <p class="truncate text-xs text-gray-600 dark:text-gray-300">
                        {{ $currency }} {{ number_format($insights['top_category_total'], 2) }}
                    </p>
                    <p class="truncate text-[11px] text-gray-500 dark:text-gray-400">
                        Most spent category
                    </p>
                </section>

                <div
                    wire:loading.flex
                    wire:target="selectMonth,showOlderMonths,showNewerMonths"
                    class="absolute inset-0 hidden items-center justify-center rounded-lg bg-white/65 backdrop-blur-[1px] dark:bg-gray-950/65"
                >
                    <div class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-200 dark:ring-white/10">
                        <x-filament::loading-indicator class="h-4 w-4" />
                        Updating summary
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
