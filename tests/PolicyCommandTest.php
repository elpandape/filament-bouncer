<?php

declare(strict_types=1);

use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Comment;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\User;
use ElPandaPe\FilamentBouncer\Tests\Fixtures\Policies\OpenRolePolicy;
use ElPandaPe\FilamentBouncer\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\Process\Process;

pest()->extend(TestCase::class);

function policyPath(string $class): string
{
    return app_path('Policies/'.$class.'.php');
}

afterEach(function (): void {
    File::deleteDirectory(app_path('Policies'));
    File::delete(base_path('stubs/filament-bouncer.policy.stub'));
});

test('it writes a policy for every resource of the panel that has none', function (): void {
    Artisan::call('filament-bouncer:policy');

    expect(File::exists(policyPath('CommentPolicy')))->toBeTrue()
        ->and(File::exists(policyPath('PostPolicy')))->toBeFalse()
        ->and(Artisan::output())->toContain('written');
});

test('the policy it writes declares the six actions the catalogue then offers', function (): void {
    Artisan::call('filament-bouncer:policy');

    $written = File::get(policyPath('CommentPolicy'));

    expect($written)
        ->toContain('namespace App\Policies;')
        ->toContain('use ElPandaPe\FilamentBouncer\Policies\BouncerPolicy;')
        ->toContain('use '.Comment::class.';')
        ->toContain('class CommentPolicy extends BouncerPolicy')
        ->toContain("return \$this->allows(\$user, 'viewAny', Comment::class);")
        ->toContain('public function view(User $user, Comment $comment): bool')
        ->toContain('public function deleteAny(User $user): bool');
});

test('it leaves a policy that is already there alone', function (): void {
    File::ensureDirectoryExists(app_path('Policies'));
    File::put(policyPath('CommentPolicy'), '<?php // written by hand');

    Artisan::call('filament-bouncer:policy');

    expect(File::get(policyPath('CommentPolicy')))->toBe('<?php // written by hand')
        ->and(Artisan::output())->toContain('already there');
});

test('forcing it overwrites what was there', function (): void {
    File::ensureDirectoryExists(app_path('Policies'));
    File::put(policyPath('CommentPolicy'), '<?php // written by hand');

    Artisan::call('filament-bouncer:policy', ['--force' => true]);

    expect(File::get(policyPath('CommentPolicy')))->toContain('extends BouncerPolicy');
});

test('it writes for a model named on the command line, resource or not', function (): void {
    Artisan::call('filament-bouncer:policy', ['model' => [Post::class]]);

    expect(File::exists(policyPath('PostPolicy')))->toBeTrue();
});

test('it says so when every resource already has one', function (): void {
    Gate::policy(Comment::class, OpenRolePolicy::class);

    Artisan::call('filament-bouncer:policy', ['--panel' => 'test']);

    expect(Artisan::output())->toContain('already has a policy')
        ->and(File::isDirectory(app_path('Policies')))->toBeFalse();
});

test('with no user model to name, it falls back to the model everything descends from', function (): void {
    config()->set('auth.providers.users.model');

    Artisan::call('filament-bouncer:policy');

    expect(File::get(policyPath('CommentPolicy')))
        ->toContain('use Illuminate\Database\Eloquent\Model;')
        ->toContain('public function viewAny(Model $user): bool');
});

test('an application keeping its own stub wins', function (): void {
    File::ensureDirectoryExists(base_path('stubs'));
    File::put(base_path('stubs/filament-bouncer.policy.stub'), "<?php\n\nnamespace {{ namespace }};\n\nclass {{ class }} {}\n");

    Artisan::call('filament-bouncer:policy');

    expect(File::get(policyPath('CommentPolicy')))->toBe("<?php\n\nnamespace App\Policies;\n\nclass CommentPolicy {}\n");
});

test('it points at the command that brings the new abilities into being', function (): void {
    Artisan::call('filament-bouncer:policy');

    expect(Artisan::output())->toContain('filament-bouncer:reconcile');
});

test('what it writes is loadable PHP, which is not a given', function (string $class): void {
    Artisan::call('filament-bouncer:policy', ['model' => [Comment::class, User::class]]);

    // Asked of PHP itself rather than of the tokeniser, because two parameters alike
    // tokenise perfectly well and only fall over when the file is compiled.
    $lint = new Process([PHP_BINARY, '-l', policyPath($class)]);
    $lint->run();

    expect($lint->getExitCode())->toBe(0, $lint->getOutput());
})->with(['CommentPolicy', 'UserPolicy']);

test('the policy for the user model itself does not name both parameters alike', function (): void {
    Artisan::call('filament-bouncer:policy', ['model' => [User::class]]);

    expect(File::get(policyPath('UserPolicy')))
        ->toContain('public function view(User $user, User $model): bool')
        ->toContain("return \$this->allows(\$user, 'view', \$model);");
});
