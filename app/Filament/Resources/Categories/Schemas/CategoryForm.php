<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->required()
                    ->options([
                        'expense' => 'Expense',
                        'income' => 'Income',
                    ]),
                ColorPicker::make('color')
                    ->required()
                    ->default('#6366f1'),
                Select::make('icon')
                    ->label('Icon (emoji)')
                    ->searchable()
                    ->nullable()
                    ->options(self::emojiOptions())
                    ->placeholder('Pick an emoji'),
            ]);
    }

    /** @return array<string, string> */
    public static function emojiOptions(): array
    {
        return [
            // Food & Drink
            '🍔' => '🍔 Burger',
            '🍕' => '🍕 Pizza',
            '🍣' => '🍣 Sushi',
            '🍜' => '🍜 Noodles',
            '🥗' => '🥗 Salad',
            '☕' => '☕ Coffee',
            '🍺' => '🍺 Beer',
            '🍷' => '🍷 Wine',
            '🧃' => '🧃 Juice',
            '🍰' => '🍰 Cake',
            // Transport
            '🚗' => '🚗 Car',
            '🚕' => '🚕 Taxi',
            '🚌' => '🚌 Bus',
            '🚇' => '🚇 Metro',
            '✈️' => '✈️ Flight',
            '🚢' => '🚢 Cruise',
            '⛽' => '⛽ Fuel',
            '🚲' => '🚲 Bicycle',
            // Home & Living
            '🏠' => '🏠 Home',
            '🛋️' => '🛋️ Furniture',
            '💡' => '💡 Electricity',
            '💧' => '💧 Water',
            '📱' => '📱 Phone',
            '📺' => '📺 TV',
            '🌐' => '🌐 Internet',
            // Shopping & Clothing
            '🛍️' => '🛍️ Shopping',
            '👗' => '👗 Clothing',
            '👟' => '👟 Shoes',
            '👜' => '👜 Handbag',
            '💍' => '💍 Jewellery',
            // Health & Fitness
            '🏥' => '🏥 Hospital',
            '💊' => '💊 Medicine',
            '💪' => '💪 Gym',
            '🧘' => '🧘 Yoga',
            '🏃' => '🏃 Running',
            // Entertainment
            '🎮' => '🎮 Gaming',
            '🎬' => '🎬 Movies',
            '🎵' => '🎵 Music',
            '📚' => '📚 Books',
            '🎭' => '🎭 Theatre',
            '⚽' => '⚽ Sports',
            '🎯' => '🎯 Hobbies',
            // Education
            '🎓' => '🎓 Education',
            '✏️' => '✏️ Stationery',
            '🔬' => '🔬 Courses',
            // Personal Care & Beauty
            '💄' => '💄 Cosmetics',
            '💈' => '💈 Salon',
            '🧴' => '🧴 Skincare',
            // Finance & Income
            '💼' => '💼 Salary',
            '💻' => '💻 Freelance',
            '📈' => '📈 Investment',
            '🏦' => '🏦 Bank Interest',
            '🎁' => '🎁 Gift',
            '🤝' => '🤝 Bonus',
            '🏷️' => '🏷️ Discount / Cashback',
            // Travel
            '🏨' => '🏨 Hotel',
            '🗺️' => '🗺️ Tourism',
            '🎒' => '🎒 Backpacking',
            // Miscellaneous
            '📦' => '📦 Subscriptions',
            '🐾' => '🐾 Pets',
            '🌱' => '🌱 Plants',
            '🔧' => '🔧 Repairs',
            '🎀' => '🎀 Gifts Given',
            '💸' => '💸 Other Expense',
            '💰' => '💰 Other Income',
        ];
    }
}
