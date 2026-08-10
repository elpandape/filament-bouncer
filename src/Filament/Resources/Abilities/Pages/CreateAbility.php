<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Pages;

use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\AbilityResource;
use ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas\AbilityComposer;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Silber\Bouncer\Database\Models;

final class CreateAbility extends CreateRecord
{
    protected static string $resource = AbilityResource::class;

    public function form(Schema $schema): Schema
    {
        return AbilityComposer::configure($schema);
    }

    /**
     * Writes the row the composer described, and nothing the form was not asked about.
     *
     * The name and the model come from the catalogue entry rather than from the request,
     * so a narrowed rule can only ever be spelled the way the code spells the rule it
     * narrows. The row is force-filled because Bouncer's model guards its attributes and
     * the application, not this package, decides which model that is.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Thrown rather than branched on, because the form refuses to validate a pair the
        // catalogue cannot resolve: this is the analyser's question, not the reader's, and
        // a branch nothing can reach is a branch nothing can test.
        $found = AbilityComposer::ability($data['subject'] ?? null, $data['action'] ?? null);
        $ability = $found ?? throw new InvalidArgumentException('The catalogue declares no such ability.');

        /** @var Model $record */
        $record = Models::ability()->newQuery()->make();

        $record->forceFill([
            'name' => $ability->name,
            'title' => $data['title'],
            'entity_type' => $ability->entityMorphClass,
            'entity_id' => filled($data['entity_id'] ?? null) ? $data['entity_id'] : null,
            'only_owned' => (bool) ($data['only_owned'] ?? false),
        ])->save();

        return $record;
    }

    /**
     * A row nobody holds decides nothing, so the reader lands where they can hand it out
     * rather than back on a list where the new row looks finished.
     */
    protected function getRedirectUrl(): string
    {
        /** @var string $url */
        $url = self::getResource()::getUrl('edit', ['record' => $this->getRecord()]);

        return $url;
    }
}
