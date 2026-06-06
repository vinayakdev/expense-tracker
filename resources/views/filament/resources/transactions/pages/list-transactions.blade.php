<x-filament-panels::page>
    @php
        $currency = $this->getCurrency();
        $groups = $this->groupedTransactions;
    @endphp

    <section
        x-data="{ isLoadingMonth: false }"
        x-on:transaction-month-loading.window="isLoadingMonth = true"
        x-on:transaction-month-loaded.window="isLoadingMonth = false"
        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-950"
    >
        <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/5 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wide text-primary-600 dark:text-primary-400">
                    {{ $this->getSelectedMonthLabel() }}
                </p>
                <h2 class="truncate text-base font-semibold text-gray-950 dark:text-white">
                    Monthly transactions
                </h2>
            </div>

            <div
                x-show="isLoadingMonth"
                x-transition.opacity.duration.150ms
                class="flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700 ring-1 ring-primary-200 dark:bg-primary-400/10 dark:text-primary-300 dark:ring-primary-400/30"
            >
                <x-filament::loading-indicator class="h-4 w-4" />
                <span>Loading transactions</span>
            </div>
        </div>

        <div class="relative">
            <div
                x-bind:class="{ 'opacity-45': isLoadingMonth }"
                class="transition-opacity"
            >
                @if ($groups->isNotEmpty())
                    <div class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($groups as $dateLabel => $transactions)
                            <div wire:key="transaction-page-group-{{ $selectedMonthKey }}-{{ str($dateLabel)->slug() }}">
                                <div class="bg-primary-50/70 px-4 py-2 text-xs font-semibold text-primary-800 dark:bg-primary-400/10 dark:text-primary-200">
                                    {{ $dateLabel }}
                                </div>

                                <div class="divide-y divide-gray-100 dark:divide-white/10">
                                    @foreach ($transactions as $transaction)
                                        @php
                                            $isIncome = $transaction->type === 'income';
                                            $categoryColor = $transaction->category?->color ?? ($isIncome ? '#16a34a' : '#6366f1');
                                        @endphp

                                        <article
                                            wire:key="transaction-page-item-{{ $transaction->getKey() }}"
                                            class="grid grid-cols-[2.5rem_minmax(0,1fr)_auto_auto] items-center gap-3 px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-white/[0.03]"
                                        >
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-semibold text-white shadow-sm"
                                                style="background-color: {{ $categoryColor }}"
                                            >
                                                @if ($transaction->category?->icon)
                                                    {{ $transaction->category->icon }}
                                                @else
                                                    {{ str($transaction->category?->name ?? '!')->substr(0, 1)->upper() }}
                                                @endif
                                            </div>

                                            <div class="min-w-0">
                                                <div class="flex min-w-0 items-center gap-2">
                                                    <h3 class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                                                        {{ $transaction->description ?: ($transaction->category?->name ?? 'Transaction') }}
                                                    </h3>

                                                    <span @class([
                                                        'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase',
                                                        'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20' => $isIncome,
                                                        'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/20' => ! $isIncome,
                                                    ])>
                                                        {{ $transaction->type }}
                                                    </span>
                                                </div>

                                                <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $transaction->category?->name ?? 'Uncategorized' }}
                                                </p>
                                            </div>

                                            <div class="text-right">
                                                <p @class([
                                                    'whitespace-nowrap text-sm font-bold',
                                                    'text-emerald-700 dark:text-emerald-300' => $isIncome,
                                                    'text-gray-950 dark:text-white' => ! $isIncome,
                                                ])>
                                                    {{ $isIncome ? '+' : '-' }}{{ $currency }} {{ number_format((float) $transaction->amount, 2) }}
                                                </p>
                                                <p class="mt-1 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $transaction->transacted_at?->format('d M y') }}
                                                </p>
                                            </div>

                                            <div class="flex items-center justify-end gap-1 rounded-full bg-gray-50 px-1.5 py-1 ring-1 ring-gray-200 dark:bg-white/5 dark:ring-white/10">
                                                {{ ($this->editTransactionAction)(['transaction' => $transaction->getKey()]) }}
                                                {{ ($this->deleteTransactionAction)(['transaction' => $transaction->getKey()]) }}
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($this->hasMoreTransactions())
                        <div
                            wire:key="transaction-load-more-{{ $selectedMonthKey }}-{{ $visibleTransactionsCount }}"
                            wire:intersect.once="loadMoreTransactions"
                            class="flex items-center justify-center border-t border-gray-200 px-4 py-4 dark:border-white/10"
                        >
                            <div class="inline-flex items-center gap-2 rounded-full bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">
                                <x-filament::loading-indicator class="h-4 w-4" />
                                Loading more transactions
                            </div>
                        </div>
                    @endif
                @else
                    <div class="flex min-h-52 items-center justify-center px-4 py-10 text-center">
                        <div>
                            <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400">
                                <x-heroicon-m-calendar-days class="h-5 w-5" />
                            </div>
                            <p class="mt-3 text-sm font-medium text-gray-950 dark:text-white">
                                No transactions for {{ $this->getSelectedMonthLabel() }}.
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <div
                x-show="isLoadingMonth"
                x-transition.opacity.duration.150ms
                class="absolute inset-0 flex items-start justify-center bg-white/60 pt-12 backdrop-blur-[1px] dark:bg-gray-950/60"
            >
                <div class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-200 dark:ring-white/10">
                    <x-filament::loading-indicator class="h-4 w-4" />
                    <span>Loading transactions</span>
                </div>
            </div>
        </div>
    </section>

    <x-filament-actions::modals />
</x-filament-panels::page>
