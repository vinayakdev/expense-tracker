@php
    use App\Models\Budget;
    use App\Models\Transaction;
    use Filament\Facades\Filament;

    $categoryId = $get('category_id');
    $account = Filament::getTenant();

    $budget = null;
    $spent = 0;
    $percent = 0;
    $remaining = 0;
    $currency = $account?->currency ?? auth()->user()->reporting_currency ?? '$';
    $isOver = false;
    $category = null;

    if ($categoryId && $account) {
        $budget = Budget::where('category_id', $categoryId)
            ->where('user_id', auth()->id())
            ->where('account_id', $account->id)
            ->first();

        if ($budget) {
            $spent = (float) Transaction::where('category_id', $categoryId)
                ->where('account_id', $account->id)
                ->where('type', 'expense')
                ->whereNull('deleted_at')
                ->sum('amount');

            $percent = $budget->amount > 0
                ? min(100, round(($spent / (float) $budget->amount) * 100))
                : 0;
            $remaining = (float) $budget->amount - $spent;
            $isOver = $spent > (float) $budget->amount;

            $category = $budget->category;
        }
    }
@endphp

@if($budget)
    <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-4 space-y-2">
        <div class="flex items-center justify-between text-sm">
            <span class="font-medium text-gray-700 dark:text-gray-300">
                Budget limit
            </span>
            <span class="font-semibold {{ $isOver ? 'text-danger-600 dark:text-danger-400' : 'text-gray-900 dark:text-white' }}">
                {{ $currency }} {{ number_format($spent, 2) }}
                <span class="text-gray-400 dark:text-gray-500 font-normal">/ {{ $currency }} {{ number_format((float) $budget->amount, 2) }}</span>
            </span>
        </div>

        <div class="w-full h-2.5 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
            <div
                class="h-full rounded-full transition-all duration-300 {{ $isOver ? 'bg-danger-500' : ($percent >= 80 ? 'bg-warning-500' : 'bg-success-500') }}"
                style="width: {{ $percent }}%"
            ></div>
        </div>

        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>{{ $percent }}% used</span>
            @if($isOver)
                <span class="text-danger-600 dark:text-danger-400 font-medium">
                    {{ $currency }} {{ number_format(abs($remaining), 2) }} over budget
                </span>
            @else
                <span class="text-success-600 dark:text-success-400">
                    {{ $currency }} {{ number_format($remaining, 2) }} remaining
                </span>
            @endif
        </div>
    </div>
@endif
