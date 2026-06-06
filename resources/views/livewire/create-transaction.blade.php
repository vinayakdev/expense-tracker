<div class="space-y-6">

    <div>
        <a
            href="{{ route('filament.app.pages.dashboard', ['tenant' => $account->id]) }}"
            class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 transition-colors"
        >
            <flux:icon.arrow-left class="size-4" />
            Back to dashboard
        </a>

        <flux:heading size="xl" class="mt-3">New Transaction</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
            {{ $account->name }}
        </flux:text>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6">
        <form wire:submit="save" class="space-y-5">

            <flux:radio.group
                wire:model.live="type"
                variant="segmented"
                class="w-full"
            >
                <flux:radio value="expense" label="Expense" icon="arrow-trending-down" class="flex-1" />
                <flux:radio value="income" label="Income" icon="arrow-trending-up" class="flex-1" />
            </flux:radio.group>

            <flux:field>
                <flux:label>Category</flux:label>
                <flux:select wire:model="categoryId" wire:key="category-select-{{ $type }}">
                    <option value="">Select a category…</option>
                    @foreach ($this->groupedCategories as $group => $categories)
                        <optgroup label="{{ $group }}">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">
                                    {{ $category->icon ? $category->icon . ' ' : '' }}{{ $category->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </flux:select>
                <flux:error name="categoryId" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Amount</flux:label>
                    <flux:input
                        type="number"
                        wire:model="amount"
                        min="0.01"
                        step="0.01"
                        placeholder="0.00"
                        :prefix="$account->currency"
                    />
                    <flux:error name="amount" />
                </flux:field>

                <flux:field>
                    <flux:label>Date</flux:label>
                    <flux:input
                        type="date"
                        wire:model="transactedAt"
                        max="{{ now()->toDateString() }}"
                    />
                    <flux:error name="transactedAt" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>
                    Description
                    <flux:badge size="sm" variant="pill" class="ml-1">Optional</flux:badge>
                </flux:label>
                <flux:input wire:model="description" placeholder="e.g. Lunch at cafe" />
                <flux:error name="description" />
            </flux:field>

            <flux:button type="submit" variant="primary" class="w-full">
                Save Transaction
            </flux:button>

        </form>
    </div>

</div>
