<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttributeMappingResource\Pages;
use App\Models\AttributeMapping;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttributeMappingResource extends Resource
{
    protected static ?string $model = AttributeMapping::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?int $navigationSort = 2; // Adjust to position it in your sidebar

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('source_label')
                    ->label('Source Label (From Crawler)')
                    ->required()
                    ->disabled() // Admin cannot change the source
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('magento_attribute_code')
                    ->label('Magento Attribute Code')
                    ->helperText('Enter the exact attribute_code you created in Magento.')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('magento_attribute_type')
                    ->label('Magento Attribute Type')
                    ->options([
                        'select' => 'Select (Dropdown with options)',
                        'text' => 'Text (Single line)',
                        'textarea' => 'Text Area (Multiple lines)',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('source_label')
                    ->label('Source Label')
                    ->searchable(),
                Tables\Columns\TextColumn::make('magento_attribute_code')
                    ->label('Magento Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('magento_attribute_type')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_mapped')
                    ->label('Is Mapped?')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // This adds the tabs to the list view, making the workflow obvious for the admin
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributeMappings::route('/'),
            'create' => Pages\CreateAttributeMapping::route('/create'),
            'edit' => Pages\EditAttributeMapping::route('/{record}/edit'),
        ];
    }
}
