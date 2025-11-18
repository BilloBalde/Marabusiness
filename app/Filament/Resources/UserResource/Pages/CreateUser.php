<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $role = $this->form->getRawState()['role'] ?? null;

        if ($role && $this->record instanceof \App\Models\User) {
            $this->record->syncRoles([$role]);
        }
    }
}
