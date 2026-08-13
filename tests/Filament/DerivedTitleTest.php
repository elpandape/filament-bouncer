<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Filament\Forms\DerivedTitle;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\CreateRole;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Pages\EditRole;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\TextInput;
use Silber\Bouncer\Database\Models;

use function Pest\Livewire\livewire;

pest()->extend(TestCase::class);

beforeEach(function (): void {
    signInAsRoleManager();
});

test('composes the title Bouncer would compose while the name is being typed', function (): void {
    livewire(CreateRole::class)
        ->fillForm(['name' => 'atencion-al-cliente'])
        ->assertSchemaStateSet([DerivedTitle::FIELD => 'Atencion al cliente']);
});

test('keeps the title closed while it follows the name', function (): void {
    livewire(CreateRole::class)
        ->assertSchemaStateSet([DerivedTitle::CUSTOM => false])
        ->assertSchemaComponentExists(DerivedTitle::FIELD, checkComponentUsing: fn (TextInput $field): bool => $field->isReadOnly());
});

test('hands the title over when asked, and it stops following', function (): void {
    livewire(CreateRole::class)
        ->fillForm(['name' => 'soporte'])
        ->callAction(TestAction::make('customiseTitle')->schemaComponent(DerivedTitle::FIELD))
        ->assertSchemaStateSet([DerivedTitle::CUSTOM => true])
        ->assertSchemaComponentExists(DerivedTitle::FIELD, checkComponentUsing: fn (TextInput $field): bool => ! $field->isReadOnly())
        ->fillForm([DerivedTitle::FIELD => 'Atención a usuarios', 'name' => 'atencion'])
        ->assertSchemaStateSet([DerivedTitle::FIELD => 'Atención a usuarios']);
});

test('gives the derived title back when the takeover is undone', function (): void {
    livewire(CreateRole::class)
        ->fillForm(['name' => 'soporte'])
        ->callAction(TestAction::make('customiseTitle')->schemaComponent(DerivedTitle::FIELD))
        ->fillForm([DerivedTitle::FIELD => 'Atención a usuarios'])
        ->callAction(TestAction::make('customiseTitle')->schemaComponent(DerivedTitle::FIELD))
        ->assertSchemaStateSet([
            DerivedTitle::CUSTOM => false,
            DerivedTitle::FIELD => 'Soporte',
        ]);
});

test('opens a role whose title was written by hand already handed over', function (): void {
    $role = Models::role()->newQuery()->create(['name' => 'soporte', 'title' => 'Atención a usuarios']);

    livewire(EditRole::class, ['record' => $role->getRouteKey()])
        ->assertSchemaStateSet([DerivedTitle::CUSTOM => true]);
});

test('opens a role carrying the derived title still following', function (): void {
    $role = Models::role()->newQuery()->create(['name' => 'soporte']);

    livewire(EditRole::class, ['record' => $role->getRouteKey()])
        ->assertSchemaStateSet([DerivedTitle::CUSTOM => false]);
});
