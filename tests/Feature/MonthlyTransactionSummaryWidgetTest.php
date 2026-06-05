<?php

use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Resources\Transactions\Widgets\MonthlyTransactionSummary;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-06-15 12:00:00');

    $this->user = User::factory()->create();
    $this->account = Account::factory()->create([
        'user_id' => $this->user->id,
        'currency' => 'USD',
    ]);
    $this->expenseCategory = Category::factory()->expense()->create(['user_id' => $this->user->id]);
    $this->incomeCategory = Category::factory()->income()->create(['user_id' => $this->user->id]);

    actingAs($this->user);
    Filament::setTenant($this->account);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::bootCurrentPanel();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('transaction page renders the monthly transaction summary widget', function () {
    Livewire::test(ListTransactions::class)
        ->assertSeeLivewire(MonthlyTransactionSummary::class);
});

test('monthly summary totals are scoped to the current tenant account', function () {
    $otherAccount = Account::factory()->create(['user_id' => $this->user->id]);

    Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->expenseCategory->id,
        'type' => 'expense',
        'amount' => 500,
        'transacted_at' => '2026-06-05',
    ]);
    Transaction::withoutEvents(fn () => Transaction::factory()->create([
        'account_id' => $otherAccount->id,
        'category_id' => $this->expenseCategory->id,
        'type' => 'expense',
        'amount' => 9999,
        'transacted_at' => '2026-06-05',
    ]));

    Livewire::test(MonthlyTransactionSummary::class)
        ->assertSee('Jan 2026 - Jun 2026')
        ->assertSee('USD 500.00')
        ->assertDontSee('USD 9,999.00');
});

test('monthly summary renders the current six month expense window', function () {
    Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->expenseCategory->id,
        'type' => 'expense',
        'amount' => 300,
        'transacted_at' => '2026-05-10',
    ]);
    Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->expenseCategory->id,
        'type' => 'expense',
        'amount' => 1000,
        'transacted_at' => '2026-04-12',
    ]);

    Livewire::test(MonthlyTransactionSummary::class)
        ->assertSee('All spends')
        ->assertSee('Jan 2026 - Jun 2026')
        ->assertSeeInOrder(['Jan 26', 'Feb 26', 'Mar 26', 'Apr 26', 'May 26', 'Jun 26'])
        ->assertSee('USD 300.00')
        ->assertSee('USD 1,000.00');
});

test('monthly summary renders selected month insights', function () {
    $foodCategory = Category::factory()->expense()->create([
        'user_id' => $this->user->id,
        'name' => 'Food',
    ]);

    $travelCategory = Category::factory()->expense()->create([
        'user_id' => $this->user->id,
        'name' => 'Travel',
    ]);

    Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $foodCategory->id,
        'type' => 'expense',
        'amount' => 400,
        'description' => 'Groceries',
        'transacted_at' => '2026-06-10',
    ]);
    Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $foodCategory->id,
        'type' => 'expense',
        'amount' => 500,
        'description' => 'Dinner',
        'transacted_at' => '2026-06-11',
    ]);
    Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $travelCategory->id,
        'type' => 'expense',
        'amount' => 800,
        'description' => 'Flight',
        'transacted_at' => '2026-06-12',
    ]);

    Livewire::test(MonthlyTransactionSummary::class)
        ->assertSee('Highest expense')
        ->assertSee('Flight')
        ->assertSee('Travel')
        ->assertSee('Top category')
        ->assertSee('Food')
        ->assertSee('USD 900.00')
        ->assertSee('USD 800.00');
});

test('selecting a month updates the custom transaction feed', function () {
    $foodCategory = Category::factory()->expense()->create([
        'user_id' => $this->user->id,
        'name' => 'Food',
        'color' => '#ec4899',
        'icon' => '!',
    ]);

    Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $foodCategory->id,
        'type' => 'expense',
        'amount' => 203,
        'description' => 'Zomato',
        'transacted_at' => '2026-06-10',
    ]);
    Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $foodCategory->id,
        'type' => 'expense',
        'amount' => 151,
        'description' => 'Swiggy',
        'transacted_at' => '2026-05-30',
    ]);

    Livewire::test(ListTransactions::class)
        ->assertSet('selectedMonthKey', '2026-06')
        ->assertSee('June 2026')
        ->assertSee('Zomato')
        ->assertDontSee('Swiggy')
        ->call('selectMonthFromGraph', '2026-05')
        ->assertSet('selectedMonthKey', '2026-05')
        ->assertSee('May 2026')
        ->assertSee('Swiggy')
        ->assertSee('Food')
        ->assertSee('USD 151.00')
        ->assertDontSee('Zomato');
});

test('monthly summary dispatches the selected month', function () {
    Livewire::test(MonthlyTransactionSummary::class)
        ->call('selectMonth', '2026-05')
        ->assertSet('selectedMonthKey', '2026-05')
        ->assertDispatched('transaction-month-selected', monthKey: '2026-05');
});

test('monthly summary opens the window that contains the selected month', function () {
    Livewire::test(MonthlyTransactionSummary::class, ['selectedMonthKey' => '2025-12'])
        ->assertSet('monthPage', 1)
        ->assertSee('Jul 2025 - Dec 2025')
        ->assertSeeInOrder(['Jul 25', 'Aug 25', 'Sep 25', 'Oct 25', 'Nov 25', 'Dec 25']);
});

test('can paginate to the previous six month expense window', function () {
    Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->expenseCategory->id,
        'type' => 'expense',
        'amount' => 700,
        'transacted_at' => '2025-12-10',
    ]);
    Transaction::factory()->create([
        'account_id' => $this->account->id,
        'category_id' => $this->expenseCategory->id,
        'type' => 'expense',
        'amount' => 400,
        'transacted_at' => '2026-06-10',
    ]);

    Livewire::test(MonthlyTransactionSummary::class)
        ->assertSet('monthPage', 0)
        ->assertSee('Jan 2026 - Jun 2026')
        ->assertSee('USD 400.00')
        ->call('showOlderMonths')
        ->assertSet('monthPage', 1)
        ->assertSee('Jul 2025 - Dec 2025')
        ->assertSeeInOrder(['Jul 25', 'Aug 25', 'Sep 25', 'Oct 25', 'Nov 25', 'Dec 25'])
        ->assertSee('USD 700.00')
        ->assertDontSee('USD 400.00')
        ->call('showNewerMonths')
        ->assertSet('monthPage', 0);
});
