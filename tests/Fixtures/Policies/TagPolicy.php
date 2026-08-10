<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Policies;

use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Bouncer;

final readonly class TagPolicy
{
    public function __construct(private Bouncer $bouncer) {}

    public function viewAny(Model $user): bool
    {
        return $this->bouncer->getClipboard()->check($user, 'viewAny', Tag::class);
    }
}
