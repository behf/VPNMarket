<?php

namespace Modules\Reseller\Filament\Resources\VpnServerResource\Pages;

use Modules\Reseller\Filament\Resources\VpnServerResource;
use Filament\Resources\Pages\EditRecord;

class EditVpnServer extends EditRecord
{
    protected static string $resource = VpnServerResource::class;

    protected function getActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
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
