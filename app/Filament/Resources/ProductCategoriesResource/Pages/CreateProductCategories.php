<?php

namespace App\Filament\Resources\ProductCategoriesResource\Pages;

use App\Filament\Resources\ProductCategoriesResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductCategories extends CreateRecord
{
    protected static string $resource = ProductCategoriesResource::class;
}
