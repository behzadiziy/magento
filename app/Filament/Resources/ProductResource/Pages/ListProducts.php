<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use App\Models\Product;
use App\Enums\ProductStatus;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Resources\Components\Tab;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use App\Filament\Imports\ProductImporter;
use Filament\Forms\Components\FileUpload;
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


            Actions\Action::make('importProducts')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('attachment')
                        ->label('Excel File')
                        ->required()
                        // **STEP 1: Explicitly set the disk for the upload.**
                        ->disk('local')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                ])
                ->action(function (array $data) {
                    $filePath = $data['attachment'];

                    try {
                        // **STEP 2: Use the same disk to resolve the file's full path.**
                        $fullPath = Storage::disk('local')->path($filePath);

                        // Note: Filament's FileUpload now provides the path including the livewire-tmp directory
                        // so we don't need to add it manually.

                        Excel::import(new ProductsImport, $fullPath);

                        Notification::make()
                            ->title('Products Imported Successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('An error occurred: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
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
