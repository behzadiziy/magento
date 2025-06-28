<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Enums\ProductStatus;
use Filament\Actions;
use App\Models\Product;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
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
