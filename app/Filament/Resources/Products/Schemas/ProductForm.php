<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basics')
                    ->schema([
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('slug', Str::slug((string) $state));
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('GH₵')
                            ->minValue(0),
                        TextInput::make('compare_at_price')
                            ->numeric()
                            ->prefix('GH₵')
                            ->minValue(0),
                        FileUpload::make('image_path')
                            ->label('Image')
                            ->image()
                            ->directory('products')
                            ->disk('public')
                            ->visibility('public')
                            ->imageEditor(),
                        TextInput::make('stock_quantity')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->minValue(0),
                        TextInput::make('bottle_size')
                            ->maxLength(40)
                            ->placeholder('e.g. 60ml'),
                        TextInput::make('pack_units')
                            ->label('Bottles per pack')
                            ->helperText('Used for the minimum order count. Single shots stay at 1; a 10-bottle bouquet is 10.')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                        Toggle::make('is_active')
                            ->default(true)
                            ->required(),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Ingredients')
                    ->description('List what goes into the shot. These appear on the product detail page.')
                    ->schema([
                        Repeater::make('ingredients')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(120),
                                FileUpload::make('image')
                                    ->image()
                                    ->directory('ingredients')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->imageEditor(),
                            ])
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Story')
                    ->description('Powers the rich product detail page. Seeded formulas already include these; edit here to override what shoppers see.')
                    ->schema([
                        TextInput::make('short_description')
                            ->label('Blurb')
                            ->helperText('One short line under the product name.')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('About this shot')
                            ->helperText('Longer story copy on the detail page.')
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('taste')
                            ->label('Taste')
                            ->helperText('Flavor note shown in the story section.')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('ritual')
                            ->label('Daily ritual')
                            ->helperText('How and when to take the shot.')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Repeater::make('highlights')
                            ->label('Why you\'ll love it')
                            ->helperText('Bullet points on the detail page. Leave empty to keep formula defaults until other story fields are set.')
                            ->simple(
                                TextInput::make('highlight')
                                    ->required()
                                    ->maxLength(255),
                            )
                            ->defaultItems(0)
                            ->reorderable()
                            ->addActionLabel('Add highlight')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
