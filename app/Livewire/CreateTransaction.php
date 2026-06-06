<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CreateTransaction extends Component
{
    public Account $account;

    #[Validate('required|in:expense,income')]
    public string $type = 'expense';

    #[Validate('required|exists:categories,id')]
    public string $categoryId = '';

    #[Validate('required|numeric|min:0.01')]
    public string $amount = '';

    #[Validate('required|date|before_or_equal:today')]
    public string $transactedAt = '';

    #[Validate('nullable|string|max:255')]
    public ?string $description = null;

    public function mount(Account $account): void
    {
        abort_if($account->user_id !== auth()->id(), 403);

        $this->account = $account;
        $this->transactedAt = now()->toDateString();
    }

    public function updatedType(): void
    {
        $this->categoryId = '';
    }

    /**
     * @return array<string, Collection<int, Category>>
     */
    #[Computed]
    public function groupedCategories(): array
    {
        $categories = Category::where('user_id', auth()->id())
            ->where('type', $this->type)
            ->orderBy('name')
            ->get();

        $thirtyDaysAgo = now()->subDays(30)->toDateString();
        $sixtyDaysAgo = now()->subDays(60)->toDateString();

        $scores = Transaction::selectRaw(
            'category_id, SUM(CASE WHEN transacted_at >= ? THEN 3 WHEN transacted_at >= ? THEN 2 ELSE 1 END) as score',
            [$thirtyDaysAgo, $sixtyDaysAgo],
        )
            ->whereIn('category_id', $categories->pluck('id'))
            ->whereNotNull('transacted_at')
            ->groupBy('category_id')
            ->pluck('score', 'category_id');

        $topIds = $categories
            ->filter(static fn (Category $c) => $scores->get($c->id, 0) > 0)
            ->sortByDesc(static fn (Category $c) => $scores->get($c->id, 0))
            ->take(5)
            ->pluck('id')
            ->all();

        $result = [];

        if (! empty($topIds)) {
            $result['Top Picks'] = $categories
                ->filter(static fn (Category $c) => in_array($c->id, $topIds, true))
                ->sortByDesc(static fn (Category $c) => $scores->get($c->id, 0))
                ->values();
        }

        $remaining = $categories->filter(static fn (Category $c) => ! in_array($c->id, $topIds, true));

        if ($remaining->isNotEmpty()) {
            $result[ucfirst($this->type)] = $remaining->values();
        }

        return $result;
    }

    public function save(): void
    {
        $this->validate();

        Transaction::create([
            'account_id' => $this->account->id,
            'category_id' => $this->categoryId,
            'type' => $this->type,
            'amount' => $this->amount,
            'transacted_at' => $this->transactedAt,
            'description' => $this->description ?: null,
        ]);

        $this->redirect(
            route('filament.app.pages.dashboard', ['tenant' => $this->account->id]),
            navigate: false,
        );
    }

    public function render()
    {
        return view('livewire.create-transaction')
            ->layout('layouts.app');
    }
}
