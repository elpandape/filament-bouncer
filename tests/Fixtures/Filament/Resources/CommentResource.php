<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources;

use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Comment;
use Filament\Resources\Resource;

/**
 * A resource whose model has no policy, and which therefore contributes no abilities.
 */
final class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;
}
