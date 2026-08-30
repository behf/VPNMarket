<?php

namespace Modules\MultiServer\Filament\Resources\ServerResource\Pages;

use Modules\MultiServer\Filament\Resources\ServerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServer extends EditRecord
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function fillForm(): void
    {
        $data = $this->record->toArray();
        $data['api_token'] = $this->record->getRawOriginal('api_token');
        $data['password'] = $this->record->getRawOriginal('password');
        $this->form->fill($data);
    }
}
