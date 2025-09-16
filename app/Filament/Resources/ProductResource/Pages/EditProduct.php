<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['unit']) && isset($data['SI'])) {
            $data['unit'] = $data['unit'] . ' ' . $data['SI'];
        } elseif (isset($data['SI'])) {
            $data['unit'] = $data['SI'];
        }

        return $data;
    }
}
