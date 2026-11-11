<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $role = $this->form->getRawState()['role'] ?? null;

        // Belt and braces with the field's own `in` rule: a role the signed-in staff
        // member could not have picked is never applied, whatever the request carried.
        if ($role && array_key_exists($role, UserResource::assignableRoles()) && $this->record instanceof \App\Models\User) {
            $this->record->syncRoles([$role]);
        }
    }

}
