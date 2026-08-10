<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Policies;

use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Bouncer;

/**
 * Carries one of every method shape the catalogue has to tell apart: the actions it
 * wants, Laravel's own hook, a static helper, and a constructor.
 */
final readonly class PostPolicy
{
    public function __construct(private Bouncer $bouncer) {}

    public static function shouldAudit(): bool
    {
        return true;
    }

    public function before(): null
    {
        return null;
    }

    public function viewAny(Model $user): bool
    {
        return $this->allows($user, 'viewAny', Post::class);
    }

    public function view(Model $user, Post $post): bool
    {
        return $this->allows($user, 'view', $post);
    }

    public function create(Model $user): bool
    {
        return $this->allows($user, 'create', Post::class);
    }

    public function update(Model $user, Post $post): bool
    {
        return $this->allows($user, 'update', $post);
    }

    public function delete(Model $user, Post $post): bool
    {
        return $this->allows($user, 'delete', $post);
    }

    public function forceDelete(Model $user, Post $post): bool
    {
        return $this->allows($user, 'forceDelete', $post);
    }

    private function allows(Model $user, string $action, Post|string $subject): bool
    {
        return $this->bouncer->getClipboard()->check($user, $action, $subject);
    }
}
