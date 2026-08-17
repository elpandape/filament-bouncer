<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament;

use ElPandaPe\FilamentBouncer\Filament\Forms\RolesField;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

/**
 * Somewhere for the roles field to be rendered under one operation or another.
 *
 * Whether a field shows is answered against the schema it lives in, so asking the field on its
 * own says nothing: it takes a form with an operation to get an answer.
 */
final class RolesFieldHost extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public string $operation = 'create';

    public bool $wantedAnyway = false;

    public function form(Schema $schema): Schema
    {
        $field = RolesField::make();

        if ($this->wantedAnyway) {
            $field->visible(true);
        }

        return $schema->components([$field])->statePath('data')->operation($this->operation);
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>{{ $this->form }}</div>
        BLADE;
    }
}
