<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Forms;

use ElPandaPe\FilamentBouncer\Store\Declaration;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Models;

/**
 * One rule, read from the other end: every role, and what each of them says about it.
 *
 * The roles screen asks "what may this role do"; this asks "who may do this", and the two
 * write the same row of the same table. That is the whole reason the field exists rather
 * than a list of names: a rule that only one screen can change is a rule people go
 * looking for on the wrong screen.
 *
 * There is deliberately no entry here for the rule being a grant or a denial. A row of
 * the abilities table is the thing itself; granting and forbidding live in the pivot and
 * belong to a holder, so the same row is quite properly granted to one role and forbidden
 * to another. An entry saying which one it "is" would be false about half of them.
 */
final class AbilityHolders extends Field
{
    protected string $view = 'filament-bouncer::forms.ability-holders';

    /**
     * Every role, whether or not it says anything about this rule.
     *
     * Roles that hold nothing are listed too, because the question the screen answers is
     * who may be given this — and a list of the roles that already have it cannot be used
     * to give it to one that has not.
     *
     * @return array<int, array{key: string, name: string, title: string|null}>
     */
    public function getRows(): array
    {
        if (! $this->recordOrNull() instanceof Model || $this->isWithheld()) {
            return [];
        }

        $rows = [];

        foreach (Models::role()->newQuery()->orderBy('name')->get() as $role) {
            /** @var string $name */
            $name = $role->getAttribute('name');

            /** @var string|null $title */
            $title = $role->getAttribute('title');

            /** @var int|string $key */
            $key = $role->getKey();

            $rows[] = ['key' => (string) $key, 'name' => $name, 'title' => $title];
        }

        return $rows;
    }

    /**
     * Whether the row is one nobody declares any more.
     *
     * Such a row is on its way out — the next `--prune` takes it — so offering it around
     * would be handing out something that will be gone by the next deploy, along with
     * every grant made here. The row still shows, because seeing that it is doomed is the
     * point; only the cells go.
     */
    public function isWithheld(): bool
    {
        $record = $this->recordOrNull();

        return $record instanceof Model && Declaration::of($record) === Declaration::Drifted;
    }

    /**
     * @return array<string, string>
     */
    public function getStances(): array
    {
        return app(Labels::class)->stances();
    }

    public function getNeutral(): string
    {
        return Stance::Neutral->value;
    }

    public function getWithheldLabel(): string
    {
        return __('filament-bouncer::abilities.form.withheld');
    }

    public function getEmptyLabel(): string
    {
        return __('filament-bouncer::abilities.form.holders_empty');
    }

    /**
     * The record, when there is a form around this field to ask.
     *
     * A field asked what it holds before it has been put in a schema throws rather than
     * answering, because the container is a typed property nothing has assigned yet. That
     * is the same answer as a creation screen's — there is no rule to read holders off —
     * and neither has any business waking the catalogue to find it out.
     */
    private function recordOrNull(): ?Model
    {
        $record = isset($this->container) ? $this->getRecord() : null;

        return $record instanceof Model ? $record : null;
    }
}
