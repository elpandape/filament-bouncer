<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Tests\TestCase;

pest()->extend(TestCase::class);

/**
 * @param  array<array-key, mixed>  $items
 * @return array<int, string>
 */
function languageKeys(array $items, string $prefix = ''): array
{
    $keys = [];

    foreach ($items as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

        $keys = [...$keys, ...(is_array($value) ? languageKeys($value, $path) : [$path])];
    }

    return $keys;
}

/**
 * @return array<int, string>
 */
function languageFile(string $locale, string $file): array
{
    /** @var array<string, mixed> $items */
    $items = require __DIR__.'/../lang/'.$locale.'/'.$file.'.php';

    return languageKeys($items);
}

test('a file ships the same keys in both tongues', function (string $file): void {
    expect(languageFile('es', $file))->toEqualCanonicalizing(languageFile('en', $file));
})->with(['roles', 'abilities', 'actions', 'scopes', 'stances', 'console', 'titles']);

test('the grid says everything it needs to, in both tongues', function (): void {
    $keys = [
        'form.forbidden_count', 'form.model_count', 'form.manage',
        'grid.subject', 'grid.clear', 'grid.undeclared', 'grid.preset_read',
        'grid.note_legend', 'grid.hint',
        'summary.granted', 'summary.forbidden', 'summary.neutral',
    ];

    foreach (['en', 'es'] as $locale) {
        expect(languageFile($locale, 'roles'))->toContain(...$keys);
    }
});

test('the words of the shapes the grid no longer has are gone', function (): void {
    foreach (['en', 'es'] as $locale) {
        expect(languageFile($locale, 'roles'))
            ->not->toContain('form.collapse')
            ->not->toContain('presets.label')
            ->not->toContain('presets.all')
            ->not->toContain('presets.none');
    }
});
