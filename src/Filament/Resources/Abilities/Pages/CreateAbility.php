<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Store\Diagnosis;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;

final class CreateAbility extends CreateRecord
{
    protected static string $resource = AbilityResource::class;

    /**
     * Opens with the rule that came in the URL, if one did.
     *
     * It is what "fence one like this" from the listing hands over. Only the three keys that make up
     * the rule of origin are read and nothing else: the request does not get to choose which fields
     * are seeded, so it cannot sow the form with a key the model never expected. The record is left
     * blank on purpose — it is the one thing whoever is fencing came to write.
     */
    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $seed = array_filter([
            'name' => $this->query('name'),
            'entity_type' => $this->query('entity_type'),
            'only_owned' => $this->query('only_owned') === '1',
        ], static fn (mixed $value): bool => $value !== null && $value !== false);

        $this->form->fill($seed);

        $this->callHook('afterFill');
    }

    /**
     * Written with `forceFill` rather than through the parent's constructor.
     *
     * Three of these columns are not fillable on Bouncer's own model, and under strict Eloquent an
     * assignment naming them throws rather than dropping them quietly — while an application that
     * unguards its models would write them either way. Neither is something a package can assume.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $ability = Models::ability();

        $ability->forceFill($data)->save();

        resolve(Diagnosis::class)->forget();

        return $ability;
    }

    private function query(string $key): ?string
    {
        $value = request()->query($key);

        return is_string($value) && filled($value) ? $value : null;
    }
}
