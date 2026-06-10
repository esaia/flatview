<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // FileUpload expects a plain path string; if the Vue editor stored a full
        // imageInterface array, extract the relative path so FilePond can handle it.
        if (!empty($data['project_image'])) {
            $img = $data['project_image'];
            if (is_array($img)) {
                // Get the URL from the array and strip the storage prefix to a relative path
                $url = $img[0]['url'] ?? ($img['url'] ?? null);
                if ($url) {
                    // Convert full URL back to storage-relative path (e.g. "irep/images/xxx.jpg")
                    $url = preg_replace('#^https?://[^/]+/storage/#', '', $url);
                }
                $data['project_image'] = $url;
            }
        }

        if (!empty($data['images_360'])) {
            $data['images_360'] = array_values(
                array_filter($data['images_360'], fn ($item) => !empty($item['url']))
            );
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['images_360'])) {
            $data['images_360'] = array_values(
                array_filter($data['images_360'], fn ($item) => !empty($item['url']))
            );
        }
        return $data;
    }
}
