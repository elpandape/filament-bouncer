<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Filament\Resources;

use ElPandaPe\FilamentBouncer\Tests\Fixtures\Models\Post;
use Filament\Resources\Resource;

final class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $recordTitleAttribute = 'title';
}
