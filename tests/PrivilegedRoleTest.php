<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

test('with no privileged role named, everything may be handed on and nothing is a last holder', function (): void {
    config()->set('filament-bouncer.privileged_role');

    $privileged = resolve(PrivilegedRole::class);

    expect($privileged->mayBeHandedOutBy(null))->toBeTrue()
        ->and($privileged->isLastHolder(signIn()))->toBeFalse();
});

test('a privileged role that was never created has no last holder', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    expect(resolve(PrivilegedRole::class)->isLastHolder(signIn()))->toBeFalse();
});

test('a privileged role nobody holds has no last holder either', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');
    Models::role()->newQuery()->create(['name' => 'super-admin']);

    expect(resolve(PrivilegedRole::class)->isLastHolder(signIn()))->toBeFalse();
});

test('the privileged role answers to the name the configuration gives it and to no other', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    $privileged = resolve(PrivilegedRole::class);

    expect($privileged->isNamed('super-admin'))->toBeTrue()
        ->and($privileged->isNamed('editor'))->toBeFalse();
});

test('with no privileged role named, no name is the privileged one', function (): void {
    config()->set('filament-bouncer.privileged_role');

    expect(resolve(PrivilegedRole::class)->isNamed('super-admin'))->toBeFalse();
});

test('somebody who already holds the privileged role may hand it on', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    $holder = signIn();
    Bouncer::assign('super-admin')->to($holder);
    Bouncer::refresh();

    expect(resolve(PrivilegedRole::class)->mayBeHandedOutBy($holder))->toBeTrue();
});

test('somebody who never held the privileged role may not hand it on', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');
    Models::role()->newQuery()->create(['name' => 'super-admin']);

    expect(resolve(PrivilegedRole::class)->mayBeHandedOutBy(signIn()))->toBeFalse();
});

test('holding some other role is not holding the privileged one', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    $editor = signIn();
    Bouncer::assign('editor')->to($editor);
    Bouncer::refresh();

    expect(resolve(PrivilegedRole::class)->mayBeHandedOutBy($editor))->toBeFalse();
});

test('with nobody at the keyboard the privileged role is not handed on', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    expect(resolve(PrivilegedRole::class)->mayBeHandedOutBy(null))->toBeFalse();
});

test('an editor that cannot hold roles at all may not hand the privileged role on', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    expect(resolve(PrivilegedRole::class)->mayBeHandedOutBy(Post::forceCreate([])))->toBeFalse();
});

test('the only holder of the privileged role is its last holder', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    $holder = signIn();
    Bouncer::assign('super-admin')->to($holder);
    Bouncer::refresh();

    expect(resolve(PrivilegedRole::class)->isLastHolder($holder))->toBeTrue();
});

test('with two holders neither of them is the last one', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');

    $first = signIn();
    $second = signIn();

    Bouncer::assign('super-admin')->to($first);
    Bouncer::assign('super-admin')->to($second);
    Bouncer::refresh();

    $privileged = resolve(PrivilegedRole::class);

    expect($privileged->isLastHolder($first))->toBeFalse()
        ->and($privileged->isLastHolder($second))->toBeFalse();
});

test('the sole holder of another role is not the last holder of the privileged one', function (): void {
    config()->set('filament-bouncer.privileged_role', 'super-admin');
    Models::role()->newQuery()->create(['name' => 'super-admin']);

    $editor = signIn();
    Bouncer::assign('editor')->to($editor);
    Bouncer::refresh();

    expect(resolve(PrivilegedRole::class)->isLastHolder($editor))->toBeFalse();
});
