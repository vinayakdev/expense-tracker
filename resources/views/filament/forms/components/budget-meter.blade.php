@php
    use App\Models\Budget;
    use App\Models\Transaction;
    use Filament\Facades\Filament;
    use Illuminate\Support\Carbon;

    $categoryId = $get('category_id');
    $account = Filament::getTenant();

    $budget = null;
    $spent = 0;
    $percent = 0;
    $remaining = 0;
    $currency = $account?->currency ?? auth()->user()->reporting_currency ?? '$';
    $isOver = false;
    $monthLabel = '';

    if ($categoryId && $account) {
        $budget = Budget::where('category_id', $categoryId)
            ->where('user_id', auth()->id())
            ->where('account_id', $account->id)
            ->first();

        if ($budget) {
            $date = filled($get('transacted_at')) ? Carbon::parse($get('transacted_at')) : now();
            $monthLabel = $date->format('F Y');

            $spent = (float) Transaction::where('category_id', $categoryId)
                ->where('account_id', $account->id)
                ->where('type', 'expense')
                ->whereNull('deleted_at')
                ->whereYear('transacted_at', $date->year)
                ->whereMonth('transacted_at', $date->month)
                ->sum('amount');

            $percent = $budget->amount > 0
                ? min(100, round(($spent / (float) $budget->amount) * 100))
                : 0;
            $remaining = (float) $budget->amount - $spent;
            $isOver = $spent > (float) $budget->amount;
        }
    }

    $calloutColor = $isOver ? 'danger' : ($percent >= 80 ? 'warning' : 'success');
    $calloutIcon = $isOver ? 'heroicon-o-exclamation-triangle' : ($percent >= 80 ? 'heroicon-o-exclamation-circle' : 'heroicon-o-check-circle');
@endphp

@if($budget)
    <x-filament::callout :color="$calloutColor" :icon="$calloutIcon">
        <x-slot name="heading">
            Budget &middot; {{ $monthLabel }}
        </x-slot>

        <x-slot name="description">
            <span class="font-semibold">{{ $currency }} {{ number_format($spent, 2) }}</span>
            <span class="text-gray-400 dark:text-gray-500"> / {{ $currency }} {{ number_format((float) $budget->amount, 2) }}</span>
        </x-slot>

        <x-slot name="footer">
            <div class="space-y-1.5 w-full">
                <div class="w-full h-2 rounded-full bg-gray-200 dark:bg-white/10 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-300 {{ $isOver ? 'bg-danger-500' : ($percent >= 80 ? 'bg-warning-500' : 'bg-success-500') }}"
                        style="width: {{ $percent }}%"
                    ></div>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span>{{ $percent }}% used</span>
                    @if($isOver)
                        <span>{{ $currency }} {{ number_format(abs($remaining), 2) }} over budget</span>
                    @else
                        <span>{{ $currency }} {{ number_format($remaining, 2) }} remaining</span>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-filament::callout>
@endif
