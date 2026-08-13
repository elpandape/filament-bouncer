<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Actions\ProbeAbility;
use ElPandaPe\FilamentBouncer\Store\Declaration;
use ElPandaPe\FilamentBouncer\Store\Diagnosis;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * There is no delete action here, and none is to be added. The resource refuses the answer to every
 * question Filament asks about deleting one of these, so a button put here would lead to a wall.
 */
final class EditAbility extends EditRecord
{
    protected static string $resource = AbilityResource::class;

    public function getSubheading(): string
    {
        return Declaration::of($this->getRecord())->note();
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ProbeAbility::make(),
            ViewAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Why `forceFill` is in CreateAbility.
        $record->forceFill($data)->save();

        // What was saved may have made or unmade a twin, and the diagnosis remembers within the
        // request.
        resolve(Diagnosis::class)->forget();

        return $record;
    }
}
