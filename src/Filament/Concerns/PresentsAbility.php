<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Concerns;

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityForm;
use ElPandaPe\FilamentBouncer\Store\Reach;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;

/**
 * The two things about a rule that are not columns of it.
 *
 * How far it reaches is spread over three columns and reads as none of them; what each
 * role says about it lives in another table entirely. Filament fills a form out of the
 * record's attributes and finds neither, so both are laid over the data on the way in and
 * lifted back out on the way to the record — otherwise the holders would be handed to a
 * mass assignment as a column that does not exist, which under strict Eloquent throws.
 */
trait PresentsAbility
{
    /**
     * @var array<string, string>
     */
    private array $holders = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function fillFacts(array $data, Model $ability): array
    {
        $data[AbilityForm::REACH] = Reach::reading($ability);
        $data[AbilityForm::HOLDERS] = $this->stances($ability);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function takeHolders(array $data): array
    {
        /** @var array<string, string> $holders */
        $holders = is_array($data[AbilityForm::HOLDERS] ?? null) ? $data[AbilityForm::HOLDERS] : [];

        $this->holders = $holders;

        unset($data[AbilityForm::HOLDERS], $data[AbilityForm::REACH]);

        return $data;
    }

    /**
     * Writes each role's stance through the store, one row at a time.
     *
     * A role named in the state that is no longer there is passed over rather than
     * created. Somebody else may well have deleted it while this screen sat open, and
     * bringing it back — empty, and holding whatever this form was about to grant it —
     * would be the worst of the three things that could happen.
     */
    protected function writeHolders(Model $ability): void
    {
        $abilities = app(RoleAbilities::class);

        foreach ($this->holders as $key => $stance) {
            $role = Models::role()->newQuery()->find($key);

            if (! $role instanceof Model) {
                continue;
            }

            $abilities->saveRow($role, $ability, Stance::tryFrom($stance) ?? Stance::Neutral);
        }
    }

    /**
     * @return array<string, string>
     */
    private function stances(Model $ability): array
    {
        $abilities = app(RoleAbilities::class);
        $stances = [];

        foreach (Models::role()->newQuery()->orderBy('name')->get() as $role) {
            /** @var int|string $key */
            $key = $role->getKey();

            $stances[(string) $key] = $abilities->stanceOnRow($role, $ability)->value;
        }

        return $stances;
    }
}
