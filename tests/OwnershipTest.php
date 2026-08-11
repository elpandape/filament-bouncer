<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Exceptions\InvalidOwnership;
use ElPandaPe\FilamentBouncer\Support\Ownership;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Models;

pest()->extend(TestCase::class);

test('the packaged configuration says nobody owns anything', function (): void {
    expect(config('filament-bouncer.ownership'))->toBe([]);
});

test('a model nobody named owns nothing, and the guess never runs', function (): void {
    $user = signIn();
    $post = Post::query()->where('id', Post::query()->create()->getKey())->sole();

    expect(Models::isOwnedBy($user, $post))->toBeFalse();
});

test('a model named in the configuration answers by its column', function (): void {
    Schema::table('posts', static function (Blueprint $table): void {
        $table->unsignedBigInteger('author_id')->nullable();
    });

    config()->set('filament-bouncer.ownership', [Post::class => 'author_id']);
    Ownership::register();

    $user = signIn();

    expect(Models::isOwnedBy($user, Post::query()->forceCreate(['author_id' => $user->getKey()])))->toBeTrue()
        ->and(Models::isOwnedBy($user, Post::query()->forceCreate(['author_id' => null])))->toBeFalse();
});

test('a record loaded without that column answers no instead of throwing', function (): void {
    Schema::table('posts', static function (Blueprint $table): void {
        $table->unsignedBigInteger('author_id')->nullable();
    });

    config()->set('filament-bouncer.ownership', [Post::class => 'author_id']);
    Ownership::register();

    $user = signIn();
    $post = Post::query()->forceCreate(['author_id' => $user->getKey()]);
    $sinColumna = Post::query()->select('id')->where('id', $post->getKey())->sole();

    expect(Models::isOwnedBy($user, $sinColumna))->toBeFalse();
});

test('an ability held down to what its holder owns follows that column', function (): void {
    Schema::table('posts', static function (Blueprint $table): void {
        $table->unsignedBigInteger('author_id')->nullable();
    });

    config()->set('filament-bouncer.ownership', [Post::class => 'author_id']);
    Ownership::register();

    $user = signIn();
    Bouncer::allow($user)->toOwn(Post::class)->to('update');
    Bouncer::refresh();

    expect(Gate::forUser($user)->allows('update', Post::query()->forceCreate(['author_id' => $user->getKey()])))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', Post::query()->forceCreate(['author_id' => null])))->toBeFalse();
});

test('a mangled entry stops the application instead of quietly opening a door', function (): void {
    config()->set('filament-bouncer.ownership', [Post::class => ['author_id']]);

    expect(static fn () => Ownership::register())->toThrow(InvalidOwnership::class);
});
