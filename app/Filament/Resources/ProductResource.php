<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\ProductStatus;
use App\Services\MagentoService;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\SelectColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductResource\RelationManagers;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([

                    Section::make('Product Details')
                        ->columnSpan(2)
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->label('Product Name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('brand')
                                    ->label('Brand')
                                    ->maxLength(255),

                                TextInput::make('category')
                                    ->label('Category')
                                    ->maxLength(255),
                            ]),

                            Textarea::make('description')
                                ->rows(6),
                        ]),

                    Section::make('Status & Pricing')
                        ->columnSpan(1)
                        ->schema([
                            Select::make('status')
                                ->required()
                                ->options(ProductStatus::class)
                                ->default(ProductStatus::PendingReview),

                            TextInput::make('price')
                                ->required()
                                ->numeric(),

                            TextInput::make('stock_quantity')
                                ->label('Stock Quantity')
                                ->numeric()
                                ->default(0),

                            TextInput::make('source_url')
                                ->label('Source URL')
                                ->url()
                                ->maxLength(2048)
                                ->columnSpanFull(),
                        ]),
                ]),

                Section::make('Product Images')
                    ->collapsible()
                    ->schema([
                        FileUpload::make('images')
                            ->label('Gallery Images')
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->disk('public')
                            ->directory('products')
                            ->image()
                            ->imageEditor()
                            ->helperText('Add or reorder the product gallery images.')
                    ]),


                Section::make('Advanced & Crawler Data')
                    ->collapsible()
                    ->columns(2)
                    ->schema([

                        KeyValue::make('attributes')
                            ->label('Product Attributes')
                            ->keyLabel('Attribute Name')
                            ->valueLabel('Attribute Value')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->numeric()
                    ->sortable(),

                SelectColumn::make('status')
                    ->options(ProductStatus::class)
                    ->sortable()
                    ->afterStateUpdated(function (Product $record, $state) {
                        if ($state === ProductStatus::Synced->value) {
                            try {
                                // 1. Resolve the service from the container
                                $magentoService = app(MagentoService::class);

                                // 2. Call the service method with the model instance
                                $magentoService->createOrUpdateProduct($record);

                                // 3. Send a success notification (Good UX!)
                                Notification::make()
                                    ->title("Product '{$record->name}' synced successfully")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {

                                Notification::make()
                                    ->title('Sync Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }
                    }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
