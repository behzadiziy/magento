<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use App\Models\Product;
use App\Enums\ProductStatus;
use Filament\Resources\Components\Tab;
use App\Filament\Imports\ProductImporter;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\ImportAction::make()
                ->importer(ProductImporter::class),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(Product::count()),

            'unsynced' => Tab::make('Not Synchronized')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', "!=", ProductStatus::Synced->value))
                ->badge(Product::where('status', '!=', ProductStatus::Synced->value)->count()),
        ];
    }
}
