<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Support\Facades\View;

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

/**
 * Every `filament-bouncer::` name the code writes out in full, which is two families sharing a
 * prefix: keys into these files, and views. Only the literal ones are reachable — those composed
 * at runtime, an ailment's question or a declaration's note, are named by their enum case and end
 * up asserted by the screens that read them.
 *
 * @return array<int, string>
 */
function literalKeys(): array
{
    $keys = [];

    foreach ([__DIR__.'/../src', __DIR__.'/../resources/views'] as $root) {
        /** @var iterable<SplFileInfo> $files */
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $source = file_get_contents($file->getPathname());

            preg_match_all("/'filament-bouncer::([a-z]+)\.([a-zA-Z0-9_.\-]+)'/", $source === false ? '' : $source, $found, PREG_SET_ORDER);

            foreach ($found as $hit) {
                $keys[$hit[1].'.'.$hit[2]] = $hit[1].'.'.$hit[2];
            }
        }
    }

    return array_values($keys);
}

test('no screen shows a name where a word belongs', function (): void {
    $files = ['roles', 'abilities', 'actions', 'scopes', 'stances', 'console', 'titles'];
    $missing = [];

    foreach (literalKeys() as $name) {
        // What the code leaves half-written is a key it finishes at runtime, out of an enum case.
        if (str_ends_with($name, '.')) {
            continue;
        }

        // Asked before the files, and not by its prefix: a view lives under the same prefix a
        // language file uses, so `roles.ability-tags` is a view and `roles.relation.role` a key.
        if (View::exists('filament-bouncer::'.$name)) {
            continue;
        }

        [$group, $rest] = explode('.', $name, 2);

        // Outside those files there is nothing left for it to be but a view that does not
        // resolve, which is the same mistake caught the same way: a screen showing a name.
        if (! in_array($group, $files, true)) {
            $missing[] = 'view: '.$name;

            continue;
        }

        foreach (['en', 'es'] as $locale) {
            if (! in_array($rest, languageFile($locale, $group), true)) {
                $missing[] = $locale.': '.$name;
            }
        }
    }

    expect($missing)->toBeEmpty();
});
