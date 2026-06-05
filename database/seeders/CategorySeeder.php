<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /** @return array<int, array<string, string>> */
    public static function definitions(): array
    {
        return [
            // ── Expenses ──────────────────────────────────────────────
            ['name' => 'Food & Dining',       'type' => 'expense', 'icon' => '🍔', 'color' => '#f97316'],
            ['name' => 'Groceries',           'type' => 'expense', 'icon' => '🥗', 'color' => '#22c55e'],
            ['name' => 'Coffee & Drinks',     'type' => 'expense', 'icon' => '☕', 'color' => '#92400e'],
            ['name' => 'Transport',           'type' => 'expense', 'icon' => '🚗', 'color' => '#3b82f6'],
            ['name' => 'Fuel',                'type' => 'expense', 'icon' => '⛽', 'color' => '#ef4444'],
            ['name' => 'Rent & Housing',      'type' => 'expense', 'icon' => '🏠', 'color' => '#8b5cf6'],
            ['name' => 'Electricity',         'type' => 'expense', 'icon' => '💡', 'color' => '#eab308'],
            ['name' => 'Internet & Phone',    'type' => 'expense', 'icon' => '📱', 'color' => '#06b6d4'],
            ['name' => 'Shopping',            'type' => 'expense', 'icon' => '🛍️', 'color' => '#ec4899'],
            ['name' => 'Clothing',            'type' => 'expense', 'icon' => '👗', 'color' => '#f43f5e'],
            ['name' => 'Healthcare',          'type' => 'expense', 'icon' => '🏥', 'color' => '#10b981'],
            ['name' => 'Medicine',            'type' => 'expense', 'icon' => '💊', 'color' => '#14b8a6'],
            ['name' => 'Gym & Fitness',       'type' => 'expense', 'icon' => '💪', 'color' => '#f59e0b'],
            ['name' => 'Entertainment',       'type' => 'expense', 'icon' => '🎮', 'color' => '#a855f7'],
            ['name' => 'Movies & Shows',      'type' => 'expense', 'icon' => '🎬', 'color' => '#6366f1'],
            ['name' => 'Travel',              'type' => 'expense', 'icon' => '✈️', 'color' => '#0ea5e9'],
            ['name' => 'Hotel & Stay',        'type' => 'expense', 'icon' => '🏨', 'color' => '#64748b'],
            ['name' => 'Education',           'type' => 'expense', 'icon' => '🎓', 'color' => '#7c3aed'],
            ['name' => 'Books',               'type' => 'expense', 'icon' => '📚', 'color' => '#d97706'],
            ['name' => 'Subscriptions',       'type' => 'expense', 'icon' => '📦', 'color' => '#475569'],
            ['name' => 'Personal Care',       'type' => 'expense', 'icon' => '💄', 'color' => '#db2777'],
            ['name' => 'Salon & Grooming',    'type' => 'expense', 'icon' => '💈', 'color' => '#9d174d'],
            ['name' => 'Gifts Given',         'type' => 'expense', 'icon' => '🎁', 'color' => '#dc2626'],
            ['name' => 'Repairs & Maintenance', 'type' => 'expense', 'icon' => '🔧', 'color' => '#78716c'],
            ['name' => 'Pets',                'type' => 'expense', 'icon' => '🐾', 'color' => '#a16207'],
            ['name' => 'Other Expense',       'type' => 'expense', 'icon' => '💸', 'color' => '#6b7280'],
            // ── Income ────────────────────────────────────────────────
            ['name' => 'Salary',              'type' => 'income',  'icon' => '💼', 'color' => '#16a34a'],
            ['name' => 'Freelance',           'type' => 'income',  'icon' => '💻', 'color' => '#2563eb'],
            ['name' => 'Business',            'type' => 'income',  'icon' => '🤝', 'color' => '#0f766e'],
            ['name' => 'Investment Returns',  'type' => 'income',  'icon' => '📈', 'color' => '#15803d'],
            ['name' => 'Bank Interest',       'type' => 'income',  'icon' => '🏦', 'color' => '#1d4ed8'],
            ['name' => 'Gift Received',       'type' => 'income',  'icon' => '🎁', 'color' => '#b45309'],
            ['name' => 'Cashback / Refund',   'type' => 'income',  'icon' => '🏷️', 'color' => '#059669'],
            ['name' => 'Other Income',        'type' => 'income',  'icon' => '💰', 'color' => '#4ade80'],
        ];
    }

    public function run(): void
    {
        User::all()->each(function (User $user): void {
            foreach (self::definitions() as $definition) {
                Category::updateOrCreate(
                    ['user_id' => $user->id, 'name' => $definition['name']],
                    $definition,
                );
            }
        });
    }
}
