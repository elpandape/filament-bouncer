<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Tests\Fixtures\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Silber\Bouncer\Database\HasRolesAndAbilities;

final class User extends Authenticatable
{
    use HasRolesAndAbilities;
}
