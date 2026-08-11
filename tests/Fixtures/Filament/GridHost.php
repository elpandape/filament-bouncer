<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament;

use ElPandaPe\FilamentBouncer\Catalog\Catalog;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Forms\AbilityGrid;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Silber\Bouncer\Database\Models;

/**
 * Somewhere for the grid to be rendered while it is the only thing that exists.
 *
 * A field only draws inside a schema, and the screens that will carry this one are not
 * written yet. It is not registered on the test panel on purpose: a page there would
 * declare an ability of its own and move every count the reconciliation asserts.
 */
final class GridHost extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public ?int $role = null;

    public bool $barren = false;

    public function mount(): void
    {
        $record = $this->record();

        $this->getSchema('form')?->fill($record instanceof Model
            ? ['abilities' => app(RoleAbilities::class)->toFormState($record)]
            : null);
    }

    public function form(Schema $schema): Schema
    {
        $grid = AbilityGrid::make('abilities')
            ->catalog($this->catalog())
            ->notes(static fn (Model $record): array => [$record->getMorphClass() => ['an-action' => 'A note this fixture puts there']]);

        $schema = $schema->components([$grid])->statePath('data');

        $record = $this->record();

        return $record instanceof Model ? $schema->model($record) : $schema;
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>{{ $this->form }}</div>
        BLADE;
    }

    private function catalog(): Catalog
    {
        return $this->barren
            ? new Catalog([], [])
            : app(CatalogRegistry::class)->current();
    }

    private function record(): ?Model
    {
        if ($this->role === null) {
            return null;
        }

        /** @var Model|null $role */
        $role = Models::role()->newQuery()->find($this->role);

        return $role;
    }
}
